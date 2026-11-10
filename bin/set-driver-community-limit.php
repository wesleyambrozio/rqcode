<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__).'/vendor/autoload.php';
$db = new PDO('mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=central_saas','root','',[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
]);
$rows=$db->query("select p.* from plans p join saas_systems s on s.id=p.system_id where s.slug='checklist' and p.slug='community'")->fetchAll();
if(count($rows)!==1)throw new RuntimeException('Expected exactly one Checklist community plan');
$row=$rows[0];$service=new App\Services\ProductAdministration($db);$plan=$service->exportPlan($row);
if((float)$plan['price_monthly']!==0.0||!$plan['active'])throw new RuntimeException('Expected active free plan');
if(($argv[1]??'')==='--apply') {
    $backup='/var/backups/driver-limit-20261005/community-before.json';
    if(!is_dir(dirname($backup)))throw new RuntimeException('Backup directory required');
    if(!file_exists($backup))file_put_contents($backup,json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    $owner=$db->query("select id from admin_users where id=1 and role='owner' and active=1")->fetchColumn();
    if(!$owner)throw new RuntimeException('Platform owner not found');
    if(($plan['limits']['vehicles_driver']??null)!==1){
        $plan['limits']['vehicles_driver']=1;
        $service->savePlan((int)$row['system_id'],$plan['external_id'],$plan,(int)$row['revision'],(int)$owner,
            'Solicitação do proprietário em 05/10/2026: gratuito motorista limitado a 1 veículo; demais perfis e preços preservados. Aplicação assistida via CLI.');
    }
}
$q=$db->prepare('select limits_json,revision,applied_revision,sync_error from plans where id=?');$q->execute([$row['id']]);
echo json_encode($q->fetch(),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
