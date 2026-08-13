<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

final class AlertService
{
    public function run(bool $dryRun = false): array
    {
        $recipients = $this->recipients();
        if (!$recipients) throw new \RuntimeException('Configure ALERT_RECIPIENTS ou MAIL_TO_FINANCEIRO.');

        $result = ['dry_run'=>$dryRun,'recipients'=>$recipients,'financial'=>[],'new_accounts'=>[],'sent'=>0,'skipped'=>0,'failed'=>0];
        foreach ($recipients as $recipient) {
            $this->sendFinancialDigest($recipient, $dryRun, $result);
            $this->sendNewAccountsDigest($recipient, $dryRun, $result);
        }
        return $result;
    }

    private function sendFinancialDigest(string $recipient, bool $dryRun, array &$result): void
    {
        $days = max(0, min(30, (int) env('ALERT_DUE_DAYS', 7)));
        $statement = Database::connection()->prepare("select fe.*, coa.code account_code, coa.name account_name
            from financial_entries fe left join chart_of_accounts coa on coa.id=fe.account_id
            where fe.status='pending' and fe.due_date <= :limit order by fe.due_date, fe.id");
        $statement->execute(['limit'=>date('Y-m-d', strtotime("+{$days} days"))]);
        $entries = $statement->fetchAll();
        $entries = array_values(array_filter($entries, fn($entry) => !$this->wasSent('financial_due:'.$entry['id'].':'.$entry['due_date'], $recipient)));
        $result['financial'] = array_merge($result['financial'], array_map(fn($e)=>['id'=>(int)$e['id'],'due_date'=>$e['due_date'],'description'=>$e['description'],'amount'=>(float)$e['amount']], $entries));
        if (!$entries) { $result['skipped']++; return; }

        $lines = ["RQCODE — Aviso de compromissos financeiros", "", "Existem ".count($entries)." lançamento(s) vencido(s) ou com vencimento nos próximos {$days} dias:", ""];
        foreach ($entries as $entry) {
            $state = $entry['due_date'] < date('Y-m-d') ? 'VENCIDO' : 'A VENCER';
            $nature = $entry['direction'] === 'receivable' ? 'RECEBER' : 'PAGAR';
            $account = $entry['account_name'] ?: $entry['category'] ?: 'Sem conta';
            $lines[] = "[{$state}] {$entry['due_date']} | {$nature} | {$account} | {$entry['description']} | ".money($entry['amount']);
        }
        $lines[] = ""; $lines[] = 'Acesse: '.rtrim((string)config('app.url'),'/').'/financeiro';
        $subject = '[RQCODE] '.count($entries).' compromisso(s) financeiro(s) requerem atenção';
        $this->deliverGroup('financial_due', $entries, fn($e)=>'financial_due:'.$e['id'].':'.$e['due_date'], $recipient, $subject, implode("\n",$lines), $dryRun, $result);
    }

    private function sendNewAccountsDigest(string $recipient, bool $dryRun, array &$result): void
    {
        if (!filter_var(env('ALERT_NEW_ACCOUNTS', true), FILTER_VALIDATE_BOOLEAN)) return;
        $statement = Database::connection()->prepare("select ms.id, ms.snapshot_date, ms.new_accounts, ss.name system_name
            from metric_snapshots ms join saas_systems ss on ss.id=ms.system_id
            where ms.new_accounts > 0 and ms.snapshot_date >= :since and ss.slug <> 'sistema-exemplo' order by ms.snapshot_date, ss.name");
        $configuredSince=(string)env('ALERT_ACCOUNTS_SINCE',date('Y-m-d'));
        $since=preg_match('/^\d{4}-\d{2}-\d{2}$/',$configuredSince)?$configuredSince:date('Y-m-d');
        $statement->execute(['since'=>$since]);
        $snapshots = $statement->fetchAll();
        $snapshots = array_values(array_filter($snapshots, fn($row) => !$this->wasSent('new_accounts:'.$row['id'].':'.$row['new_accounts'], $recipient)));
        $result['new_accounts'] = array_merge($result['new_accounts'], array_map(fn($r)=>['system'=>$r['system_name'],'date'=>$r['snapshot_date'],'count'=>(int)$r['new_accounts']], $snapshots));
        if (!$snapshots) { $result['skipped']++; return; }
        $total = array_sum(array_column($snapshots,'new_accounts'));
        $lines = ["RQCODE — Novas contas de clientes", "", "Foram identificadas {$total} nova(s) conta(s):", ""];
        foreach ($snapshots as $row) $lines[] = "{$row['snapshot_date']} | {$row['system_name']} | +{$row['new_accounts']} conta(s)";
        $lines[] = ""; $lines[] = 'Acesse: '.rtrim((string)config('app.url'),'/').'/dashboard';
        $subject = "[RQCODE] {$total} nova(s) conta(s) de clientes";
        $this->deliverGroup('new_accounts', $snapshots, fn($r)=>'new_accounts:'.$r['id'].':'.$r['new_accounts'], $recipient, $subject, implode("\n",$lines), $dryRun, $result);
    }

    private function deliverGroup(string $type, array $items, callable $key, string $recipient, string $subject, string $body, bool $dryRun, array &$result): void
    {
        if ($dryRun) return;
        try {
            $sent = (new Mailer())->send($recipient, $subject, $body);
            foreach ($items as $item) $this->record($type, $key($item), $recipient, $subject, $sent ? 'sent' : 'failed', $sent ? null : 'Mailer retornou false.');
            $sent ? $result['sent']++ : $result['failed']++;
        } catch (Throwable $exception) {
            foreach ($items as $item) $this->record($type, $key($item), $recipient, $subject, 'failed', $exception->getMessage());
            $result['failed']++;
        }
    }

    private function wasSent(string $key, string $recipient): bool
    {
        $statement = Database::connection()->prepare("select count(*) from notification_logs where event_key=:event_key and recipient=:recipient and status='sent'");
        $statement->execute(['event_key'=>$key,'recipient'=>$recipient]);
        return (bool)$statement->fetchColumn();
    }

    private function record(string $type, string $key, string $recipient, string $subject, string $status, ?string $error): void
    {
        $db=Database::connection();
        if (env('DB_CONNECTION','mysql') === 'pgsql') {
            $sql="insert into notification_logs(event_type,event_key,recipient,subject,status,error_message,sent_at,updated_at) values(:type,:event_key,:recipient,:subject,:status,:error_message,".($status==='sent'?'current_timestamp':'null').",current_timestamp) on conflict(event_key,recipient) do update set status=excluded.status,error_message=excluded.error_message,sent_at=excluded.sent_at,updated_at=current_timestamp";
        } else {
            $sql="insert into notification_logs(event_type,event_key,recipient,subject,status,error_message,sent_at,updated_at) values(:type,:event_key,:recipient,:subject,:status,:error_message,".($status==='sent'?'current_timestamp':'null').",current_timestamp) on duplicate key update status=values(status),error_message=values(error_message),sent_at=values(sent_at),updated_at=current_timestamp";
        }
        $statement=$db->prepare($sql);$statement->execute(['type'=>$type,'event_key'=>$key,'recipient'=>$recipient,'subject'=>$subject,'status'=>$status,'error_message'=>$error]);
    }

    private function recipients(): array
    {
        $raw=(string)env('ALERT_RECIPIENTS',env('MAIL_TO_FINANCEIRO',''));
        return array_values(array_unique(array_filter(array_map('trim',preg_split('/[,;]/',$raw)?:[]),fn($email)=>filter_var($email,FILTER_VALIDATE_EMAIL))));
    }
}
