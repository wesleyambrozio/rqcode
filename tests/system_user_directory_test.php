<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Services/SystemUserDirectory.php';
require dirname(__DIR__).'/app/Services/ProjectSnapshotClient.php';
use App\Services\{SystemUserDirectory, ProjectSnapshotClient};
$checks=0;
$verify=static function(bool $value,string $label)use(&$checks):void{if(!$value)throw new RuntimeException($label);$checks++;};
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec('create table saas_systems(id integer primary key,name text); create table system_users(id integer primary key,system_id integer,name text,registered_at text,created_at text,last_seen_at text,status text)');
$db->exec("insert into saas_systems values(1,'ZVTech'),(2,'Another');
insert into system_users values
(1,1,'Bruno','2026-11-02','2026-11-02','2026-11-03','active'),
(2,1,'ana','2026-11-01','2026-11-01',null,'inactive'),
(3,2,'Carlos',null,'2026-11-03','2026-11-01','active'),
(4,1,'Bruno','2026-11-02','2026-11-02','2026-11-02','suspended')");
$ids=static fn($rows)=>array_map(static fn($row)=>(int)$row['id'],$rows);
$verify($ids(SystemUserDirectory::users($db,0,'name','asc'))===[2,1,4,3],'Name ascending ignores case, keeps all statuses/products and resolves ties');
$verify($ids(SystemUserDirectory::users($db,0,'name','desc'))===[3,1,4,2],'Name descending across products');
$verify($ids(SystemUserDirectory::users($db,0,'registered_at','desc'))===[3,1,4,2],'Newest includes legacy creation-date fallback');
$verify($ids(SystemUserDirectory::users($db,0,'registered_at','asc'))===[2,1,4,3],'Oldest first');
$verify($ids(SystemUserDirectory::users($db,0,'last_seen_at','desc'))===[1,4,3,2],'Recent access first; no access last');
$verify($ids(SystemUserDirectory::users($db,0,'last_seen_at','asc'))===[3,4,1,2],'Old access first; no access remains last');
$verify($ids(SystemUserDirectory::users($db,1,'name','asc'))===[2,1,4],'Product filter retained');
$verify(SystemUserDirectory::selection('name',null)===['name','asc'],'Default alphabetical direction');
$verify(SystemUserDirectory::selection([],[])===['registered_at','desc'],'Array inputs rejected safely');
$verify($ids(SystemUserDirectory::users($db,0,'name;drop table system_users','desc;--'))===[3,1,4,2],'SQL expressions and direction cannot be injected');
$verify(count(SystemUserDirectory::users($db,999,'name','asc'))===0,'Unknown product is empty');
$secret=str_repeat('t',48);$calls=0;
$client=new ProjectSnapshotClient(static function($url,$headers,$resolve)use($verify,$secret,&$calls){
    $calls++;$verify($url==='https://zvtech.com.br/api/rqcode/v1/snapshot','Public hostname is preserved for TLS/SNI');
    $verify($resolve===['zvtech.com.br:443:127.0.0.1'],'Only TCP resolution changes');
    $time=substr($headers[1],strlen('X-RQCODE-Timestamp: '));
    $verify($headers[2]==='X-RQCODE-Signature: '.hash_hmac('sha256',$time."\nGET\n".ProjectSnapshotClient::PATH,$secret),'Snapshot authentication stays signed');
    return [200,json_encode(['schema_version'=>2,'metrics'=>['accounts_total'=>23],'snapshot_date'=>'2026-11-03','users'=>array_fill(0,150,['external_id'=>'membership'])])];
});
$verify(count($client->fetch('https://zvtech.com.br',$secret,'127.0.0.1')['users'])===150,'Full directory is returned without truncation');
foreach([['http://external.example',$secret,''],['https://zvtech.com.br',$secret,'not-an-ip'],['https://user:password@zvtech.com.br',$secret,''],['https://zvtech.com.br','short','']] as $args){
    try{$client->fetch(...$args);throw new LogicException('Expected rejection');}catch(RuntimeException $e){$verify(true,'Invalid configuration rejected before transport');}
}
$verify($calls===1,'Rejected configuration never transmits credentials');
foreach([[0,false],[302,'redirect'],[401,'private response'],[200,'not-json'],[200,'{}'],[200,'{"schema_version":3,"metrics":{},"snapshot_date":"2026-11-03"}']] as $response){
    try{(new ProjectSnapshotClient(static fn()=> $response))->fetch('https://zvtech.com.br',$secret);throw new LogicException('Expected failure');}
    catch(RuntimeException $e){$verify(!str_contains($e->getMessage(),'private response')&&!str_contains($e->getMessage(),$secret),'Errors fail closed without leaking body or key');}
}
$v1=(new ProjectSnapshotClient(static fn()=>[200,'{"schema_version":1,"metrics":{},"snapshot_date":"2026-11-03"}']))->fetch('https://legacy.example',$secret);
$verify($v1['schema_version']===1,'Legacy snapshot remains supported');
echo "OK: {$checks} directory and integration checks\n";
