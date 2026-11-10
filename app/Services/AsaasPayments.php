<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

final class AsaasPayments
{
    public function __construct(private PDO $db, private AsaasClient $api) {}

    private function lock(): string { return $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' for update'; }
    private function row(string $sql, array $args): ?array
    {
        $q = $this->db->prepare($sql); $q->execute($args); return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(int $entryId, string $customer, string $billingType, int $actor): array
    {
        if (!preg_match('/^cus_[a-zA-Z0-9_]+$/D', $customer) || !in_array($billingType, ['PIX', 'BOLETO', 'UNDEFINED'], true)) throw new RuntimeException('Cliente ou forma de cobrança inválidos.');
        $environment = $this->api->environment();
        $this->db->beginTransaction();
        try {
            $entry = $this->row('select * from financial_entries where id=?' . $this->lock(), [$entryId]);
            if (!$entry || $entry['direction'] !== 'receivable' || $entry['status'] !== 'pending' || (float)$entry['amount'] <= 0) throw new RuntimeException('Selecione um lançamento pendente a receber.');
            $charge = $this->row('select * from asaas_charges where entry_id=? and environment=?', [$entryId, $environment]);
            $fresh = !$charge;
            if ($fresh) {
                if ($entry['due_date'] < date('Y-m-d')) throw new RuntimeException('Atualize o vencimento antes de cobrar.');
                $reference = 'rqcode-' . bin2hex(random_bytes(16));
                $this->db->prepare('insert into asaas_charges(entry_id,environment,external_reference,customer_id,billing_type,amount,due_date,created_by) values(?,?,?,?,?,?,?,?)')->execute([$entryId,$environment,$reference,$customer,$billingType,$entry['amount'],$entry['due_date'],$actor]);
                $charge = $this->row('select * from asaas_charges where external_reference=?', [$reference]);
            }
            $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
        if ($charge['payment_id']) return $charge;
        // Persist the reservation before the POST. A timeout must never lead to a second automatic POST.
        if (!$fresh) {
            $found = $this->api->request('GET', '/payments', ['externalReference'=>$charge['external_reference'], 'limit'=>2]);
            if (count($found['data'] ?? []) !== 1) throw new RuntimeException('Tentativa anterior sem confirmação. Verifique no Asaas; nova cobrança automática bloqueada para evitar duplicidade.');
            $payment = $found['data'][0];
        } else {
            $payment = $this->api->request('POST', '/payments', ['customer'=>$charge['customer_id'], 'billingType'=>$charge['billing_type'], 'value'=>(float)$charge['amount'], 'dueDate'=>$charge['due_date'], 'description'=>mb_substr($entry['description'],0,500), 'externalReference'=>$charge['external_reference']]);
        }
        $this->db->beginTransaction();
        try {
            $current = $this->row('select * from asaas_charges where id=?' . $this->lock(), [$charge['id']]);
            // A webhook can arrive before the creation response; preserve its newer state.
            if (!$current['payment_id']) $this->bind($current, $payment);
            $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
        return $this->row('select * from asaas_charges where id=?', [$charge['id']]);
    }

    private function bind(array $charge, array $payment): void
    {
        if ($charge['payment_id'] && $charge['payment_id'] !== ($payment['id'] ?? '')) throw new RuntimeException('Identificador da cobrança incompatível.');
        if (!preg_match('/^pay_[a-zA-Z0-9_]+$/D', $payment['id'] ?? '') || ($payment['externalReference'] ?? '') !== $charge['external_reference'] || ($payment['customer'] ?? '') !== $charge['customer_id'] || abs((float)($payment['value'] ?? 0)-(float)$charge['amount']) > 0.001) throw new RuntimeException('Cobrança incompatível com o lançamento.');
        $url = $payment['invoiceUrl'] ?? null;
        if ($url && (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https' || !preg_match('/(^|\.)asaas\.com$/D', parse_url($url, PHP_URL_HOST) ?? ''))) $url = null;
        $this->db->prepare('update asaas_charges set payment_id=?, invoice_url=?, gateway_status=?, updated_at=current_timestamp where id=?')->execute([$payment['id'],$url,$payment['status'] ?? 'PENDING',$charge['id']]);
    }

    public function reconcile(int $id): void
    {
        $this->db->beginTransaction();
        try {
            $charge = $this->row('select * from asaas_charges where id=? and environment=?' . $this->lock(), [$id,$this->api->environment()]);
            if (!$charge || !$charge['payment_id']) throw new RuntimeException('Cobrança ainda sem identificador confirmado.');
            $this->apply($charge, $this->api->request('GET','/payments/' . rawurlencode($charge['payment_id'])));
            $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }

    private function apply(array $charge, array $payment): void
    {
        $this->bind($charge, $payment);
        $status = $payment['status'] ?? '';
        // CONFIRMED can be provisional (Pix precautionary hold). Only RECEIVED settles cash.
        $local = match ($status) {
            'RECEIVED', 'RECEIVED_IN_CASH' => 'paid',
            'REFUNDED' => 'cancelled',
            'PENDING', 'OVERDUE', 'CONFIRMED', 'REFUND_REQUESTED', 'REFUND_IN_PROGRESS', 'CHARGEBACK_REQUESTED', 'CHARGEBACK_DISPUTE', 'AWAITING_CHARGEBACK_REVERSAL' => 'pending',
            default => null,
        };
        if (!empty($payment['deleted'])) $local = 'cancelled';
        // Sandbox must never settle the real financial ledger.
        if ($local && $charge['environment'] === 'production') {
            $date = $payment['paymentDate'] ?? $payment['clientPaymentDate'] ?? null;
            if ($local === 'paid' && (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date))) throw new RuntimeException('Pagamento recebido sem data válida.');
            $this->db->prepare('update financial_entries set status=?, paid_at=? where id=?')->execute([$local,$local === 'paid' ? $date : null,$charge['entry_id']]);
        }
    }

    public function webhook(string $raw, string $token, string $expected): void
    {
        if (strlen($expected) < 32 || !hash_equals($expected, $token)) throw new RuntimeException('Webhook não autorizado.', 401);
        try { $event = json_decode($raw, true, 32, JSON_THROW_ON_ERROR); } catch (Throwable) { throw new RuntimeException('JSON inválido.',400); }
        if (!is_array($event) || !is_string($event['id'] ?? null) || $event['id'] === '' || strlen($event['id']) > 190 || !is_string($event['event'] ?? null) || !preg_match('/^PAYMENT_[A-Z_]+$/D',$event['event']) || !is_array($event['payment'] ?? null) || !is_string($event['payment']['id'] ?? null) || !preg_match('/^pay_[a-zA-Z0-9_]+$/D',$event['payment']['id'])) throw new RuntimeException('Evento inválido.',400);
        $this->db->beginTransaction();
        try {
            $env = $this->api->environment();
            $charge = $this->row('select * from asaas_charges where environment=? and (payment_id=? or external_reference=?)' . $this->lock(),[$env,$event['payment']['id'],$event['payment']['externalReference'] ?? '']);
            if (!$charge) { $this->db->commit(); return; } // Other products in the same Asaas account.
            if ($this->row('select event_id from asaas_events where environment=? and event_id=?',[$env,$event['id']])) { $this->db->commit(); return; }
            if ($charge['payment_id'] && $charge['payment_id'] !== $event['payment']['id']) throw new RuntimeException('Identificador incompatível.',409);
            // Read authoritative current state while holding the charge lock: delayed events cannot regress it.
            $payment = $this->api->request('GET','/payments/' . rawurlencode($event['payment']['id']));
            $this->apply($charge,$payment);
            $this->db->prepare('insert into asaas_events(environment,event_id,event_type,charge_id) values(?,?,?,?)')->execute([$env,$event['id'],$event['event'],$charge['id']]);
            $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
}
