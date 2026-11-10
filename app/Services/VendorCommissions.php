<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

/**
 * Every product account belongs to one vendor at a time. Commissions are generated per received payment,
 * on the gross amount, either for the whole life of the account or for a limited number of installments.
 */
final class VendorCommissions
{
    public const TYPES = ['lifetime' => 'Vitalícia', 'installments' => 'Parcelas limitadas'];
    private const PAID = ['RECEIVED', 'RECEIVED_IN_CASH'];
    private const REVERSED = ['REFUNDED', 'DELETED', 'CHARGEBACK_REQUESTED', 'CHARGEBACK_DISPUTE'];

    public function __construct(private PDO $db) {}

    public static function validCode(mixed $code): ?string
    {
        return is_string($code) && preg_match('/^[A-Za-z0-9-]{1,40}$/D', $code) ? strtoupper($code) : null;
    }

    public function current(int $system, string $tenant): ?array
    {
        return $this->row('select * from vendor_assignments where system_id=? and tenant_external_id=? and ends_at is null order by id desc limit 1', [$system, $tenant]);
    }

    /**
     * Called for every account a product reports. Keeps the existing vendor; otherwise uses the sign-up code or the default vendor.
     */
    public function ensureAssignment(int $system, string $tenant, mixed $code): ?array
    {
        if ($current = $this->current($system, $tenant)) return $current;
        $code = self::validCode($code);
        $vendor = $code ? $this->row('select * from vendors where code=? and active=true', [$code]) : null;
        $source = $vendor ? 'signup_code' : 'default';
        $vendor ??= $this->row('select * from vendors where is_default=true and active=true order by id limit 1');
        if (!$vendor) return null;
        $this->insertAssignment($system, $tenant, $vendor, (float) $vendor['commission_default_percent'], $vendor['commission_type'], $vendor['commission_installments'] === null ? null : (int) $vendor['commission_installments'], $source, null, null);
        return $this->current($system, $tenant);
    }

    public function reassign(int $system, string $tenant, int $vendorId, float $percent, string $type, ?int $limit, int $actor, string $reason): void
    {
        $vendor = $this->row('select * from vendors where id=? and active=true', [$vendorId]);
        if (!$vendor) throw new RuntimeException('Vendedor inativo ou inexistente.', 422);
        if (!$this->row('select id from product_accounts where system_id=? and external_id=?', [$system, $tenant])) throw new RuntimeException('Conta não encontrada.', 404);
        $this->validateTerms($percent, $type, $limit);
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500) throw new RuntimeException('Informe o motivo da alteração.', 422);
        $this->db->prepare('update vendor_assignments set ends_at=current_timestamp where system_id=? and tenant_external_id=? and ends_at is null')->execute([$system, $tenant]);
        $this->insertAssignment($system, $tenant, $vendor, $percent, $type, $limit, 'manual', $actor, $reason);
    }

    public function validateTerms(float $percent, string $type, ?int $limit): void
    {
        if ($percent < 0 || $percent > 100) throw new RuntimeException('Percentual deve ficar entre 0 e 100.', 422);
        if (!isset(self::TYPES[$type])) throw new RuntimeException('Tipo de comissão inválido.', 422);
        if ($type === 'installments' && ($limit === null || $limit < 1 || $limit > 600)) throw new RuntimeException('Informe a quantidade de parcelas comissionadas.', 422);
    }

    /**
     * Must run inside the payment transaction. Idempotent per payment.
     */
    public function onPayment(array $subscription, array $payment): void
    {
        if (($subscription['environment'] ?? '') !== 'production') return;
        $status = (string) $payment['status'];
        $commission = $this->row("select c.*, f.status entry_status from commission_entries c left join financial_entries f on f.id=c.financial_entry_id where c.billing_payment_id=? and c.kind='commission'", [$payment['id']]);

        if (in_array($status, self::PAID, true)) {
            if ($commission) return;
            $assignment = $this->current((int) $subscription['system_id'], $subscription['tenant_external_id']);
            if (!$assignment) return;
            $count = (int) $this->value("select count(*) from commission_entries where assignment_id=? and kind='commission'", [$assignment['id']]);
            if ($assignment['commission_type'] === 'installments' && $count >= (int) $assignment['installments_limit']) return;
            $base = (float) $payment['amount'];
            $amount = round($base * (float) $assignment['commission_percent'] / 100, 2);
            if ($amount <= 0) return;
            $vendor = $this->row('select name from vendors where id=?', [$assignment['vendor_id']]);
            $entry = $this->financialEntry('Comissão '.$vendor['name'].' — '.$subscription['tenant_external_id'].' — parcela '.($count + 1), $amount, 'pending', 'payment='.$payment['payment_id']);
            $this->db->prepare('insert into commission_entries(assignment_id,vendor_id,system_id,tenant_external_id,billing_payment_id,kind,installment_number,base_amount,commission_percent,amount,financial_entry_id) values(?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$assignment['id'], $assignment['vendor_id'], $subscription['system_id'], $subscription['tenant_external_id'], $payment['id'], 'commission', $count + 1, $base, $assignment['commission_percent'], $amount, $entry]);
            return;
        }

        if (!$commission || !in_array($status, self::REVERSED, true)) return;
        if ($commission['entry_status'] !== 'paid') {
            // Not paid to the vendor yet: simply cancel the payable.
            $this->db->prepare("update financial_entries set status='cancelled' where id=?")->execute([$commission['financial_entry_id']]);
            return;
        }
        if ($this->row("select id from commission_entries where billing_payment_id=? and kind='reversal'", [$payment['id']])) return;
        $entry = $this->financialEntry('Estorno de comissão — '.$subscription['tenant_external_id'].' — parcela '.$commission['installment_number'], (float) $commission['amount'], 'pending', 'reversal of payment='.$payment['payment_id'], 'receivable');
        $this->db->prepare('insert into commission_entries(assignment_id,vendor_id,system_id,tenant_external_id,billing_payment_id,kind,installment_number,base_amount,commission_percent,amount,financial_entry_id) values(?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$commission['assignment_id'], $commission['vendor_id'], $commission['system_id'], $commission['tenant_external_id'], $payment['id'], 'reversal', $commission['installment_number'], -(float) $commission['base_amount'], $commission['commission_percent'], -(float) $commission['amount'], $entry]);
    }

    private function insertAssignment(int $system, string $tenant, array $vendor, float $percent, string $type, ?int $limit, string $source, ?int $actor, ?string $reason): void
    {
        $this->validateTerms($percent, $type, $limit);
        $this->db->prepare('insert into vendor_assignments(system_id,tenant_external_id,vendor_id,commission_percent,commission_type,installments_limit,source,assigned_by,reason) values(?,?,?,?,?,?,?,?,?)')
            ->execute([$system, $tenant, $vendor['id'], $percent, $type, $type === 'installments' ? $limit : null, $source, $actor, $reason]);
    }

    private function financialEntry(string $description, float $amount, string $status, string $notes, string $direction = 'payable'): int
    {
        $this->db->prepare('insert into financial_entries(description,category,direction,amount,due_date,paid_at,status,payment_method,notes) values(?,?,?,?,?,?,?,?,?)')
            ->execute([mb_substr($description, 0, 180), 'Comissões', $direction, $amount, date('Y-m-d', strtotime('first day of next month')), null, $status, null, $notes]);
        return (int) $this->db->lastInsertId();
    }

    private function row(string $sql, array $args = []): ?array
    {
        $q = $this->db->prepare($sql);
        $q->execute($args);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function value(string $sql, array $args): mixed
    {
        $q = $this->db->prepare($sql);
        $q->execute($args);
        return $q->fetchColumn();
    }
}
