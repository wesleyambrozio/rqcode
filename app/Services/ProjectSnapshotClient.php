<?php
declare(strict_types=1);
namespace App\Services;

use RuntimeException;

/** Keeps the public HTTPS hostname/SNI even when the connection uses a private IP. */
final class ProjectSnapshotClient
{
    public const PATH = '/api/rqcode/v1/snapshot';

    public function __construct(private mixed $transport = null) {}

    public function fetch(string $baseUrl, string $secret, string $connectIp = ''): array
    {
        $parts = parse_url($baseUrl);
        $host = $parts['host'] ?? '';
        $scheme = $parts['scheme'] ?? '';
        if (!$host || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || !in_array($parts['path'] ?? '', ['', '/'], true)
            || ($scheme !== 'https' && !($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)))) {
            throw new RuntimeException('URL de integração inválida; use HTTPS.');
        }
        if (strlen($secret) < 32) throw new RuntimeException('Chave de integração indisponível.');
        if ($connectIp !== '' && !filter_var($connectIp, FILTER_VALIDATE_IP)) throw new RuntimeException('IP de conexão da integração inválido.');
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        $resolve = $connectIp === '' ? [] : [$host . ':' . $port . ':' . (str_contains($connectIp, ':') ? '[' . $connectIp . ']' : $connectIp)];
        $timestamp = (string) time();
        $headers = ['Accept: application/json', 'X-RQCODE-Timestamp: ' . $timestamp,
            'X-RQCODE-Signature: ' . hash_hmac('sha256', $timestamp . "\nGET\n" . self::PATH, $secret)];
        $url = rtrim($baseUrl, '/') . self::PATH;
        if ($this->transport) {
            [$status, $body] = ($this->transport)($url, $headers, $resolve);
        } else {
            if (!function_exists('curl_init')) throw new RuntimeException('Extensão cURL indisponível.');
            $curl = curl_init($url);
            curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => $headers, CURLOPT_RESOLVE => $resolve]);
            $body = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);
        }
        // Never log response bodies, headers or signed credentials on failures.
        if ($body === false || $status !== 200) throw new RuntimeException('Snapshot indisponível. HTTP ' . $status . '.');
        try { $payload = json_decode($body, true, 64, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { throw new RuntimeException('Resposta de integração inválida.'); }
        if (!is_array($payload) || !in_array($payload['schema_version'] ?? null, [1, 2], true)
            || !is_array($payload['metrics'] ?? null) || !is_string($payload['snapshot_date'] ?? null)) {
            throw new RuntimeException('Snapshot de integração inválido.');
        }
        return $payload;
    }
}
