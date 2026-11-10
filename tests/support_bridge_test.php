<?php
declare(strict_types=1);
use App\Services\{SupportBridge,KnowledgePublisher};
require dirname(__DIR__).'/vendor/autoload.php';
if(PHP_SAPI!=='cli')exit(1);
$name=$argv[1]??'';
if(!preg_match('/^rqcode_support_test_[a-z0-9_]+$/D',$name))throw new RuntimeException('Dedicated test database required.');
$admin=new PDO('mysql:unix_socket=/var/run/mysqld/mysqld.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$admin->exec('create database `'.$name.'` character set utf8mb4');
$count=0;
function check(bool $ok,string $label):void { global $count; if(!$ok)throw new RuntimeException($label);$count++;echo 'PASS '.$label.PHP_EOL; }
function rejected(callable $call,int $code,string $label):void {try{$call();}catch(RuntimeException $e){check($e->getCode()===$code,$label);return;}throw new RuntimeException('Not rejected: '.$label);}
try{
 $db=new PDO('mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname='.$name,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 foreach(['001_central_saas','009_support_control_plane','011_support_queues_ai','013_support_integrity','016_support_bridge'] as $migration) foreach(array_filter(array_map('trim',explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/'.$migration.'_mysql.sql')))) as $sql)$db->exec($sql);
 $db->exec("insert into saas_systems(name,slug,active) values('Test Checklist','checklist',1),('Other','other',1)");
 $system=(int)$db->query("select id from saas_systems where slug='checklist'")->fetchColumn();$other=(int)$db->query("select id from saas_systems where slug='other'")->fetchColumn();
 $secret=str_repeat('a',64);$bridge=new SupportBridge($db,['checklist'=>$secret,'other'=>str_repeat('b',64)]);
 $headers=function(string $raw,string $system='checklist',?int $timestamp=null)use($secret){$time=(string)($timestamp??time());$nonce=bin2hex(random_bytes(16));return ['system'=>$system,'timestamp'=>$time,'nonce'=>$nonce,'signature'=>hash_hmac('sha256',$time."\n".$nonce."\n".hash('sha256',$raw),$system==='checklist'?$secret:str_repeat('b',64))];};
 $call=function(array $data,string $product='checklist')use($bridge,$headers){$raw=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);return $bridge->handle($raw,$headers($raw,$product));};
 $raw='{"action":"health"}'; $h=$headers($raw); check($bridge->handle($raw,$h)['ok'],'authenticated health');
 rejected(fn()=>$bridge->handle($raw,$h),409,'nonce replay');
 rejected(fn()=>$bridge->handle($raw,[]),401,'unsigned request');
 rejected(fn()=>$bridge->handle($raw,$headers($raw,'checklist',time()-600)),401,'expired signature');
 rejected(fn()=>$bridge->handle($raw.' ',$headers($raw)),401,'body tampering');
 rejected(fn()=>$bridge->handle('{',$headers('{')),422,'invalid JSON');
 $create=['action'=>'ticket.create','event_id'=>'event-create','external_id'=>'local-1','tenant_id'=>'tenant-1','user_id'=>'user-1','name'=>'Test User','email'=>'test@example.invalid','subject'=>'Foto negativa','message'=>'Ajuda com evidência','internal_notes'=>'NEVER EXPORT'];
 $ticket=$call($create); check($call($create)===$ticket,'idempotent creation');
 $conflict=$create;$conflict['subject']='changed';rejected(fn()=>$call($conflict),409,'idempotency conflict');
 $repeat=$create;$repeat['event_id']='another-event'; check($call($repeat)===$ticket,'external reference deduplication');
 check((int)$db->query('select count(*) from support_tickets')->fetchColumn()===1,'one persisted ticket');
 $identity=['ticket_id'=>$ticket['ticket_id'],'tenant_id'=>'tenant-1','user_id'=>'user-1'];
 rejected(fn()=>$call(array_merge($identity,['action'=>'ticket.read','tenant_id'=>'tenant-2'])),404,'tenant isolation');
 rejected(fn()=>$call(array_merge($identity,['action'=>'ticket.read','user_id'=>'user-2'])),404,'requester isolation');
 rejected(fn()=>$call($identity+['action'=>'ticket.read'],'other'),404,'product isolation');
 $db->prepare("insert into support_ticket_messages(ticket_id,author_type,author_name,body,internal_note) values(?,'admin','Support','PUBLIC ANSWER',false)")->execute([$ticket['ticket_id']]);
 $read=$call($identity+['action'=>'ticket.read']);check(count($read['messages'])===1&&$read['messages'][0]['body']==='PUBLIC ANSWER','internal notes excluded');
 $message=$identity+['action'=>'ticket.message','event_id'=>'reply-1','name'=>'User','message'=>'Thanks'];$call($message);$call($message);
 check((int)$db->query("select count(*) from support_ticket_messages where author_type='customer'")->fetchColumn()===1,'reply deduplication');
 $id=(int)end($call($identity+['action'=>'ticket.read'])['messages'])['id'];check($call($identity+['action'=>'ticket.read','after_id'=>$id])['messages']===[],'message cursor');
 $legacy=array_merge($create,['external_id'=>'old-ticket','event_id'=>'legacy','initial_status'=>'closed','opened_at'=>'2026-01-01T12:00:00Z']);$old=$call($legacy);
 check($call(['action'=>'ticket.read','ticket_id'=>$old['ticket_id'],'tenant_id'=>'tenant-1','user_id'=>'user-1'])['status']==='closed','legacy status preserved');
 $invalid=array_merge($create,['external_id'=>'bad-image','event_id'=>'bad-image','screenshot'=>['data'=>base64_encode('<svg/>')]]);rejected(fn()=>$call($invalid),422,'unsafe attachment rejected');
 check((int)$db->query('select count(*) from support_tickets')->fetchColumn()===2,'invalid upload rolled back');
 $image=imagecreatetruecolor(2,2);ob_start();imagepng($image);$imageBytes=ob_get_clean();imagedestroy($image);
 $withImage=array_merge($create,['external_id'=>'image','event_id'=>'image','screenshot'=>['data'=>base64_encode($imageBytes)]]);$call($withImage);
 check((int)$db->query('select count(*) from support_ticket_attachments')->fetchColumn()===1,'valid attachment stored privately');
 $publisher=new KnowledgePublisher($db);$doc=['title'=>'How to','summary'=>'Published guide','content'=>'Verified content','status'=>'published','audience'=>'customer','source_path'=>'docs/help/test.md','metadata'=>['profiles'=>['driver']]];
 $docId=$publisher->save($system,'guide',$doc);$publisher->save($system,'draft',array_merge($doc,['status'=>'draft']));$publisher->save($system,'internal',array_merge($doc,['audience'=>'support']));$publisher->save($other,'other-product',$doc);
 rejected(fn()=>$publisher->save($system,'guide',$doc),409,'duplicate article slug does not overwrite');
 $documents=$call(['action'=>'knowledge.list'])['documents'];check(count($documents)===1&&$documents[0]['slug']==='guide','published customer documents only, same product');
 $publisher->save($system,'guide',array_merge($doc,['content'=>'Revised']),1);
 check((int)$db->query('select count(*) from knowledge_document_versions where document_id='.$docId)->fetchColumn()===2,'immutable editorial history');
 rejected(fn()=>$publisher->save($system,'guide',$doc,1),409,'concurrent editorial edit prevented');
 $publisher->save($system,'guide',array_merge($doc,['status'=>'archived']),2);check(count($call(['action'=>'knowledge.list'])['documents'])===0,'archived articles excluded');
 check((int)$db->query('select count(*) from support_audit')->fetchColumn()>8,'audit metadata recorded');
 echo "TOTAL: $count checks passed\n";
} finally { $admin->exec('drop database `'.$name.'`'); }
