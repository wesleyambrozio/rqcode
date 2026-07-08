<?php

declare(strict_types=1);

use App\Core\Database;
use App\Services\Mailer;

require dirname(__DIR__) . '/vendor/autoload.php';

$to = env('MAIL_TO_FINANCEIRO');
if (!$to) {
    fwrite(STDERR, "MAIL_TO_FINANCEIRO não configurado.\n");
    exit(1);
}

$today = date('Y-m-d');
$limit = date('Y-m-d', strtotime('+3 days'));
$statement = Database::connection()->prepare(
    "select * from financial_entries where status = 'pending' and due_date between :today and :limit order by due_date asc"
);
$statement->execute(['today' => $today, 'limit' => $limit]);
$entries = $statement->fetchAll();

if (!$entries) {
    echo "Nenhum vencimento nos próximos 3 dias.\n";
    exit(0);
}

$lines = ["Vencimentos próximos:", ""];
foreach ($entries as $entry) {
    $lines[] = "{$entry['due_date']} | {$entry['direction']} | {$entry['description']} | " . money($entry['amount']);
}

$sent = (new Mailer())->send($to, 'Central SaaS: vencimentos próximos', implode("\n", $lines));
echo $sent ? "Aviso enviado para {$to}.\n" : "Falha ao enviar e-mail.\n";
