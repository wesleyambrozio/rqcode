<?php
declare(strict_types=1);
namespace App\Services;

use RuntimeException;

final class AsaasClient
{
    public function __construct(private string $environment, private string $key, private mixed $transport = null)
    {
        if (!in_array($environment, ['sandbox', 'production'], true)) throw new RuntimeException('Ambiente Asaas inválido.');
    }

    public static function configured(): self
    {
        return new self((string) env('ASAAS_ENV', 'sandbox'), (string) env('ASAAS_API_KEY', ''));
    }

    public function environment(): string { return $this->environment; }

    public function request(string $method, string $path, array $data = []): array
    {
        if ($this->key === '') throw new RuntimeException('Configure ASAAS_API_KEY no ambiente.');
        if ($this->transport) return ($this->transport)($method, $path, $data);
        $base = $this->environment === 'production' ? 'https://api.asaas.com/v3' : 'https://api-sandbox.asaas.com/v3';
        $url = $base . $path . ($method === 'GET' && $data ? '?' . http_build_query($data) : '');
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['access_token: ' . $this->key, 'Content-Type: application/json', 'User-Agent: RQCODE/1.0']]);
        $caBundle = (string) env('ASAAS_CA_BUNDLE', '');
        if ($caBundle !== '') {
            if (!is_file($caBundle) || !is_readable($caBundle)) {
                curl_close($curl);
                throw new RuntimeException('Arquivo de certificados ASAAS_CA_BUNDLE indisponível.');
            }
            curl_setopt($curl, CURLOPT_CAINFO, $caBundle);
        }
        if ($method !== 'GET') curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_THROW_ON_ERROR));
        $raw = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if ($raw === false || $status < 200 || $status >= 300) throw new RuntimeException('Falha na comunicação com Asaas. HTTP ' . $status . '. Consulte a conta antes de repetir a cobrança.');
        try { $result = json_decode($raw, true, 32, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { throw new RuntimeException('Resposta inválida do Asaas.'); }
        if (!is_array($result)) throw new RuntimeException('Resposta inválida do Asaas.');
        return $result;
    }
}
