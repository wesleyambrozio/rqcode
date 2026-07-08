<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/vendor/autoload.php';

$connection = env('DB_CONNECTION', 'mysql');
$dialect = $connection === 'pgsql' ? 'postgres' : 'mysql';
$migration = dirname(__DIR__) . "/database/migrations/001_central_saas_{$dialect}.sql";
$seed = dirname(__DIR__) . "/database/seeds/001_initial_{$dialect}.sql";
$withSeed = in_array('--seed', $argv, true);

foreach ([$migration, $withSeed ? $seed : null] as $file) {
    if (!$file) {
        continue;
    }
    if (!is_file($file)) {
        fwrite(STDERR, "Arquivo não encontrado: {$file}\n");
        exit(1);
    }

    Database::connection()->exec(file_get_contents($file));
    echo basename($file) . " aplicado.\n";
}

echo "Banco configurado.\n";
