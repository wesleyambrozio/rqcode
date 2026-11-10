<?php
require dirname(__DIR__).'/vendor/autoload.php';
$db=App\Core\Database::connection();$api=App\Services\AsaasClient::configured();
$service=new App\Services\AsaasSubscriptions($db,$api,(string)env('BILLING_DOCUMENT_KEY',''));
$q=$db->prepare('select system_id,tenant_external_id from billing_subscriptions where environment=? group by system_id,tenant_external_id');$q->execute([$api->environment()]);$ok=0;$failed=0;
foreach($q->fetchAll() as $row){try{$service->refresh((int)$row['system_id'],$row['tenant_external_id']);$ok++;}catch(Throwable){$failed++;}}
echo "Subscriptions reconciled: $ok; pending: $failed\n";
exit($failed?1:0);
