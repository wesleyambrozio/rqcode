<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (!str_starts_with(trim($line), '#')) putenv(trim($line));
    }
}

try {
    $payload = (new App\Services\FleetwaySyncService())->sync();
    printf("Fleetway sincronizado: %s contas, %s usuários ativos, data %s.\n", $payload['metrics']['accounts_total'], $payload['metrics']['active_users'], $payload['snapshot_date']);
} catch (Throwable $e) {
    fwrite(STDERR, "Falha na sincronização Fleetway: {$e->getMessage()}\n");
    exit(1);
}

