<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use DateInterval;
use DateTimeImmutable;
use PDO;
use Throwable;

final class FinanceController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $entries = $db->query("select fe.*, coa.code account_code, coa.name account_name, pm.name payment_method_name
            from financial_entries fe
            left join chart_of_accounts coa on coa.id = fe.account_id
            left join payment_methods pm on pm.id = fe.payment_method_id
            order by fe.due_date asc, fe.id desc")->fetchAll();
        $accounts = $db->query("select * from chart_of_accounts where active = 1 order by direction desc, code")->fetchAll();
        $paymentMethods = $db->query("select * from payment_methods where active = 1 order by name")->fetchAll();
        $recurringRules = $db->query("select r.*, coa.code account_code, coa.name account_name, pm.name payment_method_name
            from financial_recurring_rules r
            join chart_of_accounts coa on coa.id = r.account_id
            left join payment_methods pm on pm.id = r.payment_method_id
            order by r.active desc, r.created_at desc")->fetchAll();
        $summary = [
            'receivable' => $this->sum("direction = 'receivable' and status = 'pending'"),
            'payable' => $this->sum("direction = 'payable' and status = 'pending'"),
            'paid' => $this->sum("status = 'paid'"),
            'overdue' => $this->sum("status = 'pending' and due_date < '" . date('Y-m-d') . "'"),
        ];
        $this->view('finance/index', compact('entries', 'accounts', 'paymentMethods', 'recurringRules', 'summary') + ['title' => 'Financeiro']);
    }

    public function store(): void
    {
        $db = Database::connection();
        $accountId = (int) $this->input('account_id');
        $account = $this->findAccount($accountId);
        $description = trim((string) $this->input('description'));
        $amount = (float) $this->input('amount');
        $dueDate = (string) $this->input('due_date');
        $paymentMethodId = $this->nullableId($this->input('payment_method_id'));
        $notes = trim((string) $this->input('notes'));
        $recurring = $this->input('recurring') === '1';

        if (!$account || $description === '' || $amount <= 0 || !$this->validDate($dueDate)) {
            $_SESSION['flash'] = 'Revise descrição, conta, valor e vencimento.';
            redirect('/financeiro');
        }

        $db->beginTransaction();
        try {
            if ($recurring) {
                $frequency = in_array($this->input('frequency'), ['weekly', 'monthly', 'quarterly', 'yearly'], true) ? $this->input('frequency') : 'monthly';
                $installments = max(2, min(120, (int) $this->input('installments', 12)));
                $rule = $db->prepare('insert into financial_recurring_rules (description, account_id, payment_method_id, direction, amount, frequency, start_date, installments, generated_count, notes) values (:description,:account_id,:payment_method_id,:direction,:amount,:frequency,:start_date,:installments,:generated_count,:notes)');
                $rule->execute(['description'=>$description,'account_id'=>$accountId,'payment_method_id'=>$paymentMethodId,'direction'=>$account['direction'],'amount'=>$amount,'frequency'=>$frequency,'start_date'=>$dueDate,'installments'=>$installments,'generated_count'=>$installments,'notes'=>$notes ?: null]);
                $recurrenceId = (int) $db->lastInsertId();
                $date = new DateTimeImmutable($dueDate);
                for ($number = 1; $number <= $installments; $number++) {
                    $this->insertEntry($description, $account, $paymentMethodId, $amount, $date->format('Y-m-d'), 'pending', $notes, $recurrenceId, $number, $installments);
                    $date = $this->nextDate($date, $frequency);
                }
            } else {
                $status = in_array($this->input('status'), ['pending', 'paid', 'cancelled'], true) ? $this->input('status') : 'pending';
                $this->insertEntry($description, $account, $paymentMethodId, $amount, $dueDate, $status, $notes);
            }
            $db->commit();
            $_SESSION['flash_success'] = $recurring ? 'Recorrência e parcelas criadas.' : 'Lançamento criado.';
        } catch (Throwable $exception) {
            if ($db->inTransaction()) $db->rollBack();
            $_SESSION['flash'] = 'Não foi possível salvar o lançamento.';
        }
        redirect('/financeiro');
    }

    public function storeAccount(): void
    {
        $direction = in_array($this->input('direction'), ['receivable', 'payable'], true) ? $this->input('direction') : 'payable';
        $data = ['code'=>trim((string)$this->input('code')),'name'=>trim((string)$this->input('name')),'direction'=>$direction,'group_name'=>trim((string)$this->input('group_name'))];
        if ($data['code'] !== '' && $data['name'] !== '' && $data['group_name'] !== '') {
            $statement = Database::connection()->prepare('insert into chart_of_accounts (code,name,direction,group_name) values (:code,:name,:direction,:group_name)');
            try { $statement->execute($data); $_SESSION['flash_success'] = 'Conta cadastrada.'; } catch (Throwable) { $_SESSION['flash'] = 'Código já utilizado ou dados inválidos.'; }
        }
        redirect('/financeiro');
    }

    public function storePaymentMethod(): void
    {
        $name = trim((string) $this->input('name'));
        $type = trim((string) $this->input('method_type', 'other'));
        if ($name !== '') {
            $statement = Database::connection()->prepare('insert into payment_methods (name,method_type) values (:name,:type)');
            try { $statement->execute(compact('name','type')); $_SESSION['flash_success'] = 'Forma de pagamento cadastrada.'; } catch (Throwable) { $_SESSION['flash'] = 'Forma de pagamento já cadastrada.'; }
        }
        redirect('/financeiro');
    }

    public function settle(): void
    {
        $statement = Database::connection()->prepare("update financial_entries set status = 'paid', paid_at = current_date where id = :id");
        $statement->execute(['id' => (int) $this->input('id')]);
        redirect('/financeiro');
    }

    private function insertEntry(string $description, array $account, ?int $paymentMethodId, float $amount, string $dueDate, string $status, string $notes, ?int $recurrenceId = null, ?int $number = null, ?int $total = null): void
    {
        $statement = Database::connection()->prepare('insert into financial_entries (description,category,account_id,direction,amount,due_date,paid_at,status,payment_method_id,recurrence_id,installment_number,total_installments,notes) values (:description,:category,:account_id,:direction,:amount,:due_date,:paid_at,:status,:payment_method_id,:recurrence_id,:installment_number,:total_installments,:notes)');
        $statement->execute(['description'=>$description,'category'=>$account['name'],'account_id'=>$account['id'],'direction'=>$account['direction'],'amount'=>$amount,'due_date'=>$dueDate,'paid_at'=>$status === 'paid' ? date('Y-m-d') : null,'status'=>$status,'payment_method_id'=>$paymentMethodId,'recurrence_id'=>$recurrenceId,'installment_number'=>$number,'total_installments'=>$total,'notes'=>$notes ?: null]);
    }

    private function findAccount(int $id): ?array
    {
        $statement = Database::connection()->prepare('select * from chart_of_accounts where id = :id and active = 1');
        $statement->execute(['id'=>$id]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function nextDate(DateTimeImmutable $date, string $frequency): DateTimeImmutable
    {
        return match ($frequency) {
            'weekly' => $date->add(new DateInterval('P7D')),
            'quarterly' => $date->add(new DateInterval('P3M')),
            'yearly' => $date->add(new DateInterval('P1Y')),
            default => $date->add(new DateInterval('P1M')),
        };
    }

    private function nullableId(mixed $value): ?int { return (int)$value > 0 ? (int)$value : null; }
    private function validDate(string $date): bool { $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date); return $parsed && $parsed->format('Y-m-d') === $date; }
    private function sum(string $where): float { return (float) Database::connection()->query("select coalesce(sum(amount),0) from financial_entries where {$where}")->fetchColumn(); }
}
