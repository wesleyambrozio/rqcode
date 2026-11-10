<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
use App\Services\{AsaasClient,AsaasPayments};

$db = new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$sql = file_get_contents(dirname(__DIR__).'/database/migrations/020_asaas_postgres.sql');
$db->exec(str_replace('bigserial primary key','integer primary key autoincrement',$sql));
$db->exec('create table financial_entries(id integer primary key, description text, direction text, status text, amount decimal(12,2), due_date date, paid_at date)');
$q=$db->prepare("insert into financial_entries values(?, 'Teste', ?, 'pending', 99.90, ?, null)");
foreach([1,2,3,4,5,6] as $id) $q->execute([$id,$id===6?'payable':'receivable',date('Y-m-d')]);
$posted=[]; $payments=[]; $posts=0; $fail=false; $gets=0;
$transport=function($method,$path,$data)use(&$posted,&$payments,&$posts,&$fail,&$gets){
    if($method==='POST') {
        $posts++; $id='pay_test'.$posts;
        $p=['id'=>$id,'status'=>'PENDING','invoiceUrl'=>'https://www.asaas.com/i/test','paymentDate'=>date('Y-m-d')]+$data;
        $payments[$id]=$p; $posted[$data['externalReference']]=$p;
        if($fail) throw new RuntimeException('Timeout');
        return $p;
    }
    $gets++;
    if($path==='/payments') return ['data'=>isset($posted[$data['externalReference']])?[$posted[$data['externalReference']]]:[]];
    return $payments[basename($path)];
};
$count=0;
function check(bool $ok,string $name):void {global $count;if(!$ok)throw new RuntimeException($name);$count++;echo "PASS $name\n";}
function rejected(callable $call,string $name):void {try{$call();}catch(RuntimeException){check(true,$name);return;}throw new RuntimeException('Expected rejection: '.$name);}
$service=new AsaasPayments($db,new AsaasClient('production','test',$transport));
$a=$service->create(1,'cus_test','PIX',1);
$service->create(1,'cus_test','PIX',1);
check($posts===1,'Repeated creation does not POST twice');
rejected(fn()=>$service->create(6,'cus_test','PIX',1),'Payable cannot be charged');
rejected(fn()=>$service->create(2,'invalid','PIX',1),'Invalid customer rejected');
$fail=true; rejected(fn()=>$service->create(2,'cus_test','BOLETO',1),'Timeout is visible');
$fail=false; $b=$service->create(2,'cus_test','BOLETO',1);
check($posts===2 && $b['payment_id']==='pay_test2','Timeout recovered by reference without another POST');
$token=str_repeat('x',48);
$event=['id'=>'evt_1','event'=>'PAYMENT_RECEIVED','payment'=>$payments[$a['payment_id']]];
$raw=json_encode($event);
rejected(fn()=>$service->webhook($raw,'bad',$token),'Wrong webhook token rejected');
rejected(fn()=>$service->webhook('{',$token,$token),'Malformed JSON rejected');
$payments[$a['payment_id']]['status']='RECEIVED';
$service->webhook($raw,$token,$token);
check($db->query('select status from financial_entries where id=1')->fetchColumn()==='paid','Authoritative received payment settles production');
$before=$gets; $service->webhook($raw,$token,$token);
check($gets===$before && (int)$db->query('select count(*) from asaas_events')->fetchColumn()===1,'Duplicate event ignored atomically');
$event['id']='evt_old';$event['event']='PAYMENT_CREATED';$event['payment']['status']='PENDING';
$service->webhook(json_encode($event),$token,$token);
check($db->query('select status from financial_entries where id=1')->fetchColumn()==='paid','Delayed event cannot regress received payment');
$payments[$a['payment_id']]['status']='REFUNDED';$service->reconcile((int)$a['id']);
check($db->query('select status from financial_entries where id=1')->fetchColumn()==='cancelled','Refund reverses local settlement');
check($db->query('select paid_at from financial_entries where id=1')->fetchColumn()===null,'Refund clears paid date');
$sandbox=new AsaasPayments($db,new AsaasClient('sandbox','test',$transport));
$s=$sandbox->create(3,'cus_test','PIX',1);$payments[$s['payment_id']]['status']='RECEIVED';$sandbox->reconcile((int)$s['id']);
check($db->query('select status from financial_entries where id=3')->fetchColumn()==='pending','Sandbox never settles real ledger');
$c=$service->create(4,'cus_test','PIX',1);$payments[$c['payment_id']]['status']='CONFIRMED';$service->reconcile((int)$c['id']);
check($db->query('select status from financial_entries where id=4')->fetchColumn()==='pending','Provisional confirmation is not cash receipt');
$payments[$c['payment_id']]['value']=1;
rejected(fn()=>$service->reconcile((int)$c['id']),'Amount mismatch rejected');
check($db->query('select gateway_status from asaas_charges where entry_id=4')->fetchColumn()==='CONFIRMED','Failed reconciliation rolls back');
$fail=true;rejected(fn()=>$service->create(5,'cus_test','PIX',1),'Uncertain request reserved');$fail=false;
$ref=$db->query('select external_reference from asaas_charges where entry_id=5')->fetchColumn();unset($posted[$ref]);$before=$posts;
rejected(fn()=>$service->create(5,'cus_test','PIX',1),'Unresolved reservation blocks reissue');
check($posts===$before,'No POST after unresolved reservation');
echo "TOTAL $count checks passed\n";
