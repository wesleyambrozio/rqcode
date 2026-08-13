<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/vendor/autoload.php';

$pdo = Database::connection();
$dialect = env('DB_CONNECTION', 'mysql') === 'pgsql' ? 'postgres' : 'mysql';
$pdo->exec('create table if not exists schema_migrations (migration varchar(190) primary key, applied_at timestamp default current_timestamp)');

$tableExists = $dialect === 'postgres'
    ? (bool) $pdo->query("select to_regclass('public.financial_entries')")->fetchColumn()
    : (bool) $pdo->query("select count(*) from information_schema.tables where table_schema = database() and table_name = 'financial_entries'")->fetchColumn();

if ($tableExists) {
    $statement = $pdo->prepare('insert into schema_migrations (migration) values (:migration)');
    try { $statement->execute(['migration' => "001_central_saas_{$dialect}.sql"]); } catch (Throwable) {}
}

$applied = $pdo->query('select migration from schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob(dirname(__DIR__) . "/database/migrations/*_{$dialect}.sql") ?: [];
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) continue;
    if ($dialect === 'postgres') $pdo->beginTransaction();
    try {
        $sql = file_get_contents($file);
        if ($dialect === 'mysql') {
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $command) {
                try {
                    $pdo->exec($command);
                } catch (PDOException $exception) {
                    if ((int)($exception->errorInfo[1] ?? 0) !== 1060) throw $exception;
                }
            }
        } else {
            $pdo->exec($sql);
        }
        $statement = $pdo->prepare('insert into schema_migrations (migration) values (:migration)');
        $statement->execute(['migration' => $name]);
        if ($pdo->inTransaction()) $pdo->commit();
        echo "{$name} aplicado.\n";
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}

if (in_array('--seed', $argv, true)) {
    $seed = dirname(__DIR__) . "/database/seeds/001_initial_{$dialect}.sql";
    $pdo->exec(file_get_contents($seed));
    echo basename($seed) . " aplicado.\n";
}

echo "Banco configurado.\n";
