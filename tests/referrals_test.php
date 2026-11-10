<?php
declare(strict_types=1);
use App\Services\SupportBridge;
require dirname(__DIR__).'/vendor/autoload.php';
if(PHP_SAPI!=='cli')exit(1);
$name=$argv[1]??'';
if(!preg_match('/^rqcode_referral_test_[a-z0-9_]+$/D',$name))throw new RuntimeException('Isolated database required');
$root=new PDO('mysql:unix_socket=/var/run/mysqld/mysqld.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$root->exec('create database `'.$name.'` character set utf8mb4');$count=0;
function verify(bool $ok,string $label):void {global $count;if(!$ok)throw new RuntimeException($label);$count++;echo "PASS $label\n";}
function reject(callable $fn,int $code,string $label):void {try{$fn();}catch(RuntimeException $e){verify($e->getCode()===$code,$label);return;}throw new RuntimeException('Not rejected: '.$label);}
try {
 $db=new PDO('mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname='.$name,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 foreach(['001_central_saas','009_support_control_plane','011_support_queues_ai','013_support_integrity','016_support_bridge','017_product_administration','018_referrals','022_vendor_commissions']as $m)foreach(array_filter(array_map('trim',explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/'.$m.'_mysql.sql'))))as $sql)$db->exec($sql);
 $db->exec("insert into saas_systems(name,slug,active) values('Checklist','checklist',1),('Other','other',1)");
 $id=(int)$db->query("select id from saas_systems where slug='checklist'")->fetchColumn();
 $db->prepare('insert into product_admin_state(system_id,enabled) values(?,true)')->execute([$id]);
 $secret=str_repeat('x',64);$bridge=new SupportBridge($db,['checklist'=>$secret,'other'=>$secret]);
 $call=function(array $payload,string $system='checklist',?string $nonce=null,?string $override=null)use($bridge,$secret){$raw=json_encode($payload,JSON_THROW_ON_ERROR);$time=(string)time();$nonce??=bin2hex(random_bytes(16));return $bridge->handle($raw,['system'=>$system,'timestamp'=>$time,'nonce'=>$nonce,'signature'=>$override??hash_hmac('sha256',$time."\n".$nonce."\n".hash('sha256',$raw),$secret)]);};
 $c=['registered'=>3,'confirmed'=>2,'activated'=>1];
 $payload=['action'=>'referrals.sync','version'=>1,'summary'=>$c,'segments'=>[['profile'=>'driver','city'=>'Curitiba','state'=>'PR']+$c],'referrers'=>[['external_id'=>'12','code'=>'ABC123ABC123','name'=>'Indicador']+$c]];
 verify($call($payload)['ok'],'authenticated sync');$call($payload);
 verify((int)$db->query('select count(*) from product_referrers')->fetchColumn()===1,'retry does not duplicate');
 verify((int)$db->query('select confirmed from product_referrers')->fetchColumn()===2,'confirmed count preserved');
 $payload['summary']['registered']=4;$payload['referrers'][0]['registered']=4;$call($payload);
 verify((int)$db->query('select registered from product_referrers')->fetchColumn()===4,'snapshot updated');
 reject(fn()=>$call($payload,'other'),403,'disabled product rejected');
 reject(fn()=>$call($payload,'checklist',null,'bad'),401,'unsigned request rejected');
 $nonce=bin2hex(random_bytes(16));$call($payload,'checklist',$nonce);reject(fn()=>$call($payload,'checklist',$nonce),409,'replay rejected');
 $invalid=$payload;$invalid['referrers'][0]['confirmed']=99;reject(fn()=>$call($invalid),422,'invalid counts rejected');
 verify((int)$db->query('select confirmed from product_referrers')->fetchColumn()===2,'transaction rollback preserves counts');
 $invalid=$payload;$invalid['referrers']=array_fill(0,101,$payload['referrers'][0]);reject(fn()=>$call($invalid),422,'batch limit enforced');
 $q=$db->query('select segments_json from product_referral_state')->fetchColumn();verify(json_decode($q,true)[0]['city']==='Curitiba','regions available to central');
 echo "TOTAL: $count checks passed\n";
}finally{$root->exec('drop database `'.$name.'`');}
