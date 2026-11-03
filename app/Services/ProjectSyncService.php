<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class ProjectSyncService
{
    private const PATH = '/api/rqcode/v1/snapshot';

    public function sync(string $key): array
    {
        $prefix = strtoupper(preg_replace('/[^a-z0-9]+/i', '_', $key));
        $baseUrl = rtrim((string) env($prefix . '_INTEGRATION_URL', ''), '/');
        $secret = (string) env($prefix . '_INTEGRATION_SECRET', '');
        if ($baseUrl === '' || strlen($secret) < 32) throw new RuntimeException("Integração {$key} não configurada.");
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . "\nGET\n" . self::PATH, $secret);
        $context = stream_context_create(['http' => ['method'=>'GET','timeout'=>15,'ignore_errors'=>true,'header'=>"Accept: application/json\r\nX-RQCODE-Timestamp: {$timestamp}\r\nX-RQCODE-Signature: {$signature}\r\n"]]);
        $body = @file_get_contents($baseUrl . self::PATH, false, $context);
        $status = $http_response_header[0] ?? '';
        if ($body === false || !str_contains($status, ' 200 ')) throw new RuntimeException("{$key} respondeu com erro: " . ($status ?: 'sem resposta'));
        $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        if (!in_array(($payload['schema_version'] ?? null), [1, 2], true) || !isset($payload['metrics'], $payload['snapshot_date'])) throw new RuntimeException("Snapshot {$key} inválido.");
        $system=$payload['system']??[];$slug=(string)($system['slug']??$key);$metrics=$payload['metrics'];$db=Database::connection();$db->beginTransaction();
        try{
            $stmt=$db->prepare("INSERT INTO saas_systems(name,slug,base_url,database_type,active)VALUES(?,?,?,'mariadb',1)ON DUPLICATE KEY UPDATE name=VALUES(name),base_url=VALUES(base_url),database_type='mariadb',active=1");$stmt->execute([$system['name']??$key,$slug,$system['base_url']??$baseUrl]);
            $systemId=(int)$db->query('SELECT id FROM saas_systems WHERE slug='.$db->quote($slug))->fetchColumn();
            $stmt=$db->prepare('INSERT INTO metric_snapshots(system_id,snapshot_date,accounts_total,new_accounts,active_users,online_users,pending_payments,paid_payments)VALUES(?,?,?,?,?,?,?,?)ON DUPLICATE KEY UPDATE accounts_total=VALUES(accounts_total),new_accounts=VALUES(new_accounts),active_users=VALUES(active_users),online_users=VALUES(online_users),pending_payments=VALUES(pending_payments),paid_payments=VALUES(paid_payments)');
            $stmt->execute([$systemId,$payload['snapshot_date'],(int)($metrics['accounts_total']??0),(int)($metrics['new_accounts']??0),(int)($metrics['active_users']??0),(int)($metrics['online_users']??0),(int)($metrics['pending_payments']??0),(int)($metrics['paid_payments']??0)]);
            if (is_array($payload['users'] ?? null)) {
                $userStmt = $db->prepare('INSERT INTO system_users(system_id,external_id,tenant_external_id,tenant_name,name,email,role,status,registered_at,last_seen_at,referral_count,synced_at,updated_at) VALUES(:system_id,:external_id,:tenant_external_id,:tenant_name,:name,:email,:role,:status,:registered_at,:last_seen_at,:referral_count,current_timestamp,current_timestamp) ON DUPLICATE KEY UPDATE tenant_external_id=VALUES(tenant_external_id),tenant_name=VALUES(tenant_name),name=VALUES(name),email=VALUES(email),role=VALUES(role),status=VALUES(status),registered_at=VALUES(registered_at),last_seen_at=VALUES(last_seen_at),referral_count=VALUES(referral_count),synced_at=current_timestamp,updated_at=current_timestamp');
                foreach ($payload['users'] as $user) {
                    if (!is_array($user) || empty($user['external_id']) || trim((string) ($user['name'] ?? '')) === '') continue;
                    $userStmt->execute([
                        'system_id' => $systemId,
                        'external_id' => substr((string) $user['external_id'], 0, 120),
                        'tenant_external_id' => $this->nullableString($user['tenant_external_id'] ?? null, 120),
                        'tenant_name' => $this->nullableString($user['tenant_name'] ?? null, 180),
                        'name' => substr(trim((string) $user['name']), 0, 180),
                        'email' => $this->nullableString($user['email'] ?? null, 180),
                        'role' => $this->nullableString($user['role'] ?? null, 80),
                        'status' => substr(trim((string) ($user['status'] ?? 'active')), 0, 30) ?: 'active',
                        'registered_at' => $this->nullableDate($user['registered_at'] ?? null),
                        'last_seen_at' => $this->nullableDate($user['last_seen_at'] ?? null),
                        'referral_count' => max(0, (int) ($user['referral_count'] ?? 0)),
                    ]);
                }
            }
            $db->commit();
        }catch(\Throwable $e){$db->rollBack();throw $e;}
        return $payload;
    }

    private function nullableString(mixed $value, int $limit): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : substr($value, 0, $limit);
    }

    private function nullableDate(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') return null;
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
