<?php

declare(strict_types=1);

use App\Services\AlertService;

require dirname(__DIR__) . '/vendor/autoload.php';

$dryRun = in_array('--dry-run', $argv, true);
try {
    $result = (new AlertService())->run($dryRun);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
    exit($result['failed'] > 0 ? 1 : 0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}
