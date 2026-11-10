<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
use App\Services\VendorCommissions;
$name=$argv[1]??'';$mysql=$name!=='';
if($mysql){
 // MariaDB mode: same checks against the real migrations, in a throwaway database.
 if(!preg_match('/^rqcode_admin_test_[a-z0-9_]+$/D',$name))throw new RuntimeException('Dedicated test database required.');
 $admin=new PDO('mysql:unix_socket=/var/run/mysqld/mysqld.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $admin->exec('create database `'.$name.'` character set utf8mb4');
 register_shutdown_function(fn()=>$admin->exec('drop database `'.$name.'`'));
 $db=new PDO('mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname='.$name,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 foreach(['001_central_saas','017_product_administration','022_vendor_commissions'] as $m)foreach(array_filter(array_map('trim',explode(';',file_get_contents(dirname(__DIR__).'/database/migrations/'.$m.'_mysql.sql'))))as $sql)$db->exec($sql);
 $db->exec("insert into saas_systems(id,name,slug,active) values(1,'Checklist','checklist',1)");
}else{
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("create table product_accounts(id integer primary key,system_id integer,external_id text);
create table financial_entries(id integer primary key,description text,category text,direction text,amount decimal(12,2),due_date date,paid_at date,status text,payment_method text,notes text);
create table vendors(id integer primary key,name text,email text,phone text,commission_default_percent decimal(8,2) default 0,active boolean default true,code text unique,is_default boolean default false,commission_type text default 'lifetime',commission_installments integer);
create table vendor_assignments(id integer primary key autoincrement,system_id integer,tenant_external_id text,vendor_id integer,commission_percent decimal(8,2),commission_type text,installments_limit integer,source text,starts_at timestamp default current_timestamp,ends_at timestamp,assigned_by integer,reason text);
create table commission_entries(id integer primary key autoincrement,assignment_id integer,vendor_id integer,system_id integer,tenant_external_id text,billing_payment_id integer,kind text,installment_number integer,base_amount decimal(12,2),commission_percent decimal(8,2),amount decimal(12,2),financial_entry_id integer,created_at timestamp default current_timestamp,unique(billing_payment_id,kind));");
}
$count=0;
function ensure(bool $ok,string $name):void{global $count;if(!$ok)throw new RuntimeException($name);echo "PASS $name\n";$count++;}
function deny(callable $fn,string $name):void{try{$fn();}catch(RuntimeException){ensure(true,$name);return;}throw new RuntimeException('Not rejected: '.$name);}
$c=new VendorCommissions($db);
$a='11111111-1111-4111-8111-111111111111';$b='22222222-2222-4222-8222-222222222222';$x='33333333-3333-4333-8333-333333333333';
foreach([$a,$b,$x] as $i=>$id)$mysql
 ?$db->prepare("insert into product_accounts(id,system_id,external_id,name,slug,profile,active,observed_active,last_seen_at) values(?,1,?,'Conta','conta','driver',1,1,current_timestamp)")->execute([$i+1,$id])
 :$db->prepare('insert into product_accounts(id,system_id,external_id) values(?,1,?)')->execute([$i+1,$id]);
ensure($c->ensureAssignment(1,$x,null)===null,'Without default vendor the account stays pending');
$db->exec("insert into vendors(id,name,commission_default_percent,code,is_default,commission_type) values(1,'Direto RQCODE',0,'DIRETO',true,'lifetime')");
$db->exec("insert into vendors(id,name,commission_default_percent,code,commission_type,commission_installments) values(2,'Ana',10,'ANA-01','installments',3)");
$db->exec("insert into vendors(id,name,commission_default_percent,code,commission_type,active) values(3,'Inativo',50,'OLD','lifetime',false)");
ensure((int)$c->ensureAssignment(1,$a,'ana-01')['vendor_id']===2,'Sign-up code links the account to its vendor');
ensure((int)$c->ensureAssignment(1,$b,'OLD')['vendor_id']===1,'Inactive or unknown code falls back to the default vendor');
ensure((int)$c->ensureAssignment(1,$a,null)['vendor_id']===2,'Later syncs keep the current vendor');
$s=['environment'=>'production','system_id'=>1,'tenant_external_id'=>$a];
$pay=fn(int $id,string $status,float $amount=100.0)=>['id'=>$id,'payment_id'=>'pay_'.$id,'status'=>$status,'amount'=>$amount];
$c->onPayment($s,$pay(1,'PENDING'));
ensure((int)$db->query('select count(*) from commission_entries')->fetchColumn()===0,'Pending payment generates no commission');
$c->onPayment($s,$pay(1,'RECEIVED',149.9));$c->onPayment($s,$pay(1,'RECEIVED',149.9));
$row=$db->query("select * from commission_entries where billing_payment_id=1")->fetch();
ensure((float)$row['amount']===14.99&&(float)$row['base_amount']===149.9,'Commission is 10% of the gross payment');
ensure((int)$db->query('select count(*) from commission_entries')->fetchColumn()===1,'Repeated webhook does not duplicate commission');
ensure($db->query("select direction from financial_entries where id=".(int)$row['financial_entry_id'])->fetchColumn()==='payable','Commission becomes a payable in finance');
foreach([2,3,4,5] as $id)$c->onPayment($s,$pay($id,'RECEIVED'));
ensure((int)$db->query("select count(*) from commission_entries where kind='commission'")->fetchColumn()===3,'Installment commission stops after the limit');
$c->onPayment($s,$pay(1,'REFUNDED',149.9));
ensure($db->query("select status from financial_entries where id=".(int)$row['financial_entry_id'])->fetchColumn()==='cancelled','Refund before paying the vendor cancels the payable');
$second=$db->query("select * from commission_entries where billing_payment_id=2")->fetch();
$db->exec("update financial_entries set status='paid' where id=".(int)$second['financial_entry_id']);
$c->onPayment($s,$pay(2,'REFUNDED'));$c->onPayment($s,$pay(2,'REFUNDED'));
$rev=$db->query("select * from commission_entries where billing_payment_id=2 and kind='reversal'")->fetchAll();
ensure(count($rev)===1&&(float)$rev[0]['amount']===-10.0,'Refund after paying the vendor records one reversal to recover');
deny(fn()=>$c->reassign(1,$a,2,10,'installments',null,1,'Troca'),'Installment type requires a limit');
deny(fn()=>$c->reassign(1,$a,2,120,'lifetime',null,1,'Troca'),'Percent above 100 rejected');
deny(fn()=>$c->reassign(1,$a,3,10,'lifetime',null,1,'Troca'),'Inactive vendor rejected');
deny(fn()=>$c->reassign(1,$a,1,5,'lifetime',null,1,' '),'Reason is mandatory');
$c->reassign(1,$a,1,5,'lifetime',null,7,'Cliente passou para venda direta');
ensure((int)$db->query("select count(*) from vendor_assignments where tenant_external_id='$a' and ends_at is null")->fetchColumn()===1,'Only one open assignment per account');
ensure((int)$db->query("select count(*) from vendor_assignments where tenant_external_id='$a'")->fetchColumn()===2,'Previous assignment is kept as history');
foreach([6,7,8,9] as $id)$c->onPayment($s,$pay($id,'RECEIVED',200));
ensure((int)$db->query("select count(*) from commission_entries where vendor_id=1")->fetchColumn()===4&&(float)$db->query("select amount from commission_entries where billing_payment_id=9")->fetchColumn()===10.0,'Lifetime commission continues on every payment');
$c->onPayment(['environment'=>'sandbox']+$s,$pay(10,'RECEIVED'));
ensure((int)$db->query("select count(*) from commission_entries where billing_payment_id=10")->fetchColumn()===0,'Sandbox payments never generate commission');
echo "TOTAL $count commission checks passed\n";
