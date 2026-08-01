<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class FleetwaySyncService
{
    private const PATH = '/api/rqcode/v1/snapshot';

    public function sync(): array
    {
        $baseUrl = rtrim((string) env('FLEETWAY_INTEGRATION_URL', ''), '/');
        $secret = (string) env('FLEETWAY_INTEGRATION_SECRET', '');
        if ($baseUrl === '' || strlen($secret) < 32) {
            throw new RuntimeException('Configure FLEETWAY_INTEGRATION_URL e um segredo com pelo menos 32 caracteres.');
        }

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . "\nGET\n" . self::PATH, $secret);
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => 15,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\nX-RQCODE-Timestamp: {$timestamp}\r\nX-RQCODE-Signature: {$signature}\r\n",
        ]]);
        $body = @file_get_contents($baseUrl . self::PATH, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        if ($body === false || !str_contains($statusLine, ' 200 ')) {
            throw new RuntimeException('Fleetway respondeu com erro: ' . ($statusLine ?: 'sem resposta'));
        }
        $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        if (($payload['schema_version'] ?? null) !== 1 || !isset($payload['metrics'], $payload['snapshot_date'])) {
            throw new RuntimeException('Snapshot Fleetway inválido.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $system = $payload['system'] ?? [];
            $stmt = $db->prepare("INSERT INTO saas_systems (name, slug, base_url, database_type, active) VALUES (?, 'fleetway', ?, 'mariadb', 1) ON DUPLICATE KEY UPDATE name=VALUES(name), base_url=VALUES(base_url), active=1");
            $stmt->execute([$system['name'] ?? 'Fleetway', $system['base_url'] ?? $baseUrl]);
            $systemId = (int) $db->query("SELECT id FROM saas_systems WHERE slug='fleetway'")->fetchColumn();
            $m = $payload['metrics'];
            $stmt = $db->prepare('INSERT INTO metric_snapshots (system_id, snapshot_date, accounts_total, new_accounts, active_users, online_users, pending_payments, paid_payments) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE accounts_total=VALUES(accounts_total), new_accounts=VALUES(new_accounts), active_users=VALUES(active_users), online_users=VALUES(online_users), pending_payments=VALUES(pending_payments), paid_payments=VALUES(paid_payments)');
            $stmt->execute([$systemId, $payload['snapshot_date'], (int)($m['accounts_total'] ?? 0), (int)($m['new_accounts'] ?? 0), (int)($m['active_users'] ?? 0), (int)($m['online_users'] ?? 0), (int)($m['pending_payments'] ?? 0), (int)($m['paid_payments'] ?? 0)]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
        return $payload;
    }
}

