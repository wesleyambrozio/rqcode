<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/vendor/autoload.php';

$db = Database::connection();
$required = ['knowledge_documents', 'system_users', 'support_ticket_messages', 'support_queues', 'ai_chat_configs'];
foreach ($required as $table) {
    $db->query("select count(*) from {$table}")->fetchColumn();
    echo "OK table {$table}\n";
}

$documents = (int) $db->query("select count(*) from knowledge_documents where status = 'published' and system_id in (select id from saas_systems where slug in ('checklist', 'fleetway'))")->fetchColumn();
if ($documents < 8) throw new RuntimeException("Esperados 8 documentos publicados; encontrados {$documents}.");

$badReferences = (int) $db->query('select count(*) from knowledge_documents d left join saas_systems s on s.id=d.system_id where s.id is null')->fetchColumn();
if ($badReferences !== 0) throw new RuntimeException('Documentos sem sistema válido.');

echo "OK {$documents} documentos publicados e vinculados\n";

$systems=(int)$db->query('select count(*) from saas_systems where active=1')->fetchColumn();
$queues=(int)$db->query('select count(distinct system_id) from support_queues where active=1')->fetchColumn();
$configs=(int)$db->query('select count(distinct system_id) from ai_chat_configs')->fetchColumn();
if($queues!==$systems||$configs!==$systems)throw new RuntimeException("Cobertura incompleta: sistemas={$systems}, filas={$queues}, IA={$configs}.");
$badQueueLinks=(int)$db->query('select count(*) from support_tickets t left join support_queues q on q.id=t.queue_id where t.queue_id is not null and (q.id is null or q.system_id<>t.system_id)')->fetchColumn();
if($badQueueLinks!==0)throw new RuntimeException('Chamados vinculados a filas de outro sistema.');
echo "OK filas e configuracoes de IA cobrem {$systems} sistemas\n";
