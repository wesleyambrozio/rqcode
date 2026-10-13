<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
use App\Services\{AsaasClient,AsaasSubscriptions,BillingBridge,BrazilianDocument};
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$sql=file_get_contents(dirname(__DIR__).'/database/migrations/021_asaas_subscriptions_postgres.sql');$db->exec(str_replace('bigserial primary key','integer primary key autoincrement',$sql));
$db->exec("create table plans(id integer primary key,system_id integer,external_id text,slug text,name text,price decimal(12,2),billing_cycle text,active integer,sort_order integer,features_json text,limits_json text);
create table product_accounts(id integer primary key,system_id integer,external_id text,plan_external_id text,active integer,revision integer);
create table product_admin_audit(id integer primary key,system_id integer,actor_id integer,entity_type text,external_id text,reason text,before_json text,after_json text);
create table financial_entries(id integer primary key,description text,category text,direction text,amount decimal(12,2),due_date date,paid_at date,status text,payment_method text,notes text);
create table saas_systems(id integer primary key,slug text,active integer);
create table product_admin_state(system_id integer,enabled integer);
create table support_api_nonces(system_id integer,nonce text,created_at timestamp default current_timestamp,unique(system_id,nonce));");
$tenant='11111111-1111-4111-8111-111111111111';$other='22222222-2222-4222-8222-222222222222';$plan='33333333-3333-4333-8333-333333333333';$free='44444444-4444-4444-8444-444444444444';$user='55555555-5555-4555-8555-555555555555';
$db->prepare("insert into plans values(1,1,?,'starter','Starter',79,'monthly',1,1,'{}','{}')")->execute([$plan]);$db->prepare("insert into plans values(2,1,?,'community','Comunidade',0,'monthly',1,0,'{}','{}')")->execute([$free]);
$db->prepare('insert into product_accounts values(1,1,?,?,1,1),(2,1,?,?,1,1)')->execute([$tenant,$free,$other,$free]);$db->exec("insert into saas_systems values(1,'checklist',1);insert into product_admin_state values(1,1)");
$customers=[];$subs=[];$payments=[];$posts=['customers'=>0,'subscriptions'=>0];$failCustomer=false;$failSub=false;
$transport=function($method,$path,$data)use(&$customers,&$subs,&$payments,&$posts,&$failCustomer,&$failSub){
    if($path==='/customers'){
        if($method==='GET')return ['data'=>array_values(array_filter($customers,fn($c)=>$c['externalReference']===$data['externalReference']))];
        $posts['customers']++;$r=['id'=>'cus_test'.$posts['customers']]+$data;$customers[$r['id']]=$r;if($failCustomer)throw new RuntimeException('Timeout');return $r;
    }
    if($path==='/subscriptions'){
        if($method==='GET')return ['data'=>array_values(array_filter($subs,fn($s)=>$s['externalReference']===$data['externalReference']))];
        $posts['subscriptions']++;$id='sub_test'.$posts['subscriptions'];$r=['id'=>$id,'status'=>'ACTIVE']+$data;$subs[$id]=$r;
        $pid='pay_test'.$posts['subscriptions'];$payments[$pid]=['id'=>$pid,'subscription'=>$id,'customer'=>$data['customer'],'billingType'=>$data['billingType'],'value'=>$data['value'],'status'=>'PENDING','dueDate'=>$data['nextDueDate'],'paymentDate'=>date('Y-m-d'),'invoiceUrl'=>'https://www.asaas.com/i/test'];
        if($failSub)throw new RuntimeException('Timeout');return $r;
    }
    if(str_starts_with($path,'/payments/'))return $payments[basename($path)];
    if(str_ends_with($path,'/payments')){$id=explode('/',$path)[2];return ['data'=>array_values(array_filter($payments,fn($p)=>$p['subscription']===$id))];}
    $id=basename($path);
    if($method==='DELETE'){$subs[$id]['deleted']=true;return ['deleted'=>true];}
    return $subs[$id];
};
$service=new AsaasSubscriptions($db,new AsaasClient('production','fake',$transport),str_repeat('k',64));
$data=['user_id'=>$user,'plan_id'=>$plan,'accepted_price'=>79,'name'=>'Teste do Pagador','email'=>'test@example.com','document'=>'529.982.247-25','billing_type'=>'UNDEFINED','accept_billing'=>true,'terms_version'=>AsaasSubscriptions::TERMS_VERSION];
$count=0;
function ensure(bool $ok,string $name):void{global $count;if(!$ok)throw new RuntimeException($name);echo "PASS $name\n";$count++;}
function deny(callable $fn,string $name):void{try{$fn();}catch(RuntimeException){ensure(true,$name);return;}throw new RuntimeException('Not rejected: '.$name);}
ensure(BrazilianDocument::valid('52998224725')&&BrazilianDocument::valid('11222333000181'),'CPF and CNPJ check digits accepted');
ensure(!BrazilianDocument::valid('11111111111')&&!BrazilianDocument::valid('52998224726'),'Invalid documents rejected');
deny(fn()=>$service->subscribe(1,$tenant,array_replace($data,['accepted_price'=>1])),'Client cannot forge plan price');
deny(fn()=>$service->subscribe(1,$tenant,array_replace($data,['accept_billing'=>false])),'Explicit recurring acceptance required');
deny(fn()=>$service->subscribe(1,$tenant,array_replace($data,['document'=>'bad'])),'Invalid CPF rejected before any POST');
ensure($posts===['customers'=>0,'subscriptions'=>0],'Invalid requests make no external mutations');
$state=$service->subscribe(1,$tenant,$data);$service->subscribe(1,$tenant,$data);
ensure($posts===['customers'=>1,'subscriptions'=>1],'Repeated submit creates one customer and one subscription');
ensure($state['account']['plan_external_id']===$free&&$state['subscription']['gateway_status']==='PENDING','Creating contract does not grant paid access');
ensure(!str_contains(json_encode($state),'52998224725')&&$state['document_mask']==='***4725','Response contains only masked document');
ensure(!str_contains(json_encode($db->query('select * from billing_customers')->fetchAll()),'52998224725'),'Complete CPF is not stored in billing tables');
$payments['pay_test1']['status']='CONFIRMED';$payments['pay_test1']['billingType']='PIX';$service->webhook(['id'=>'evt_pix','event'=>'PAYMENT_CONFIRMED','payment'=>$payments['pay_test1']]);
ensure($service->state(1,$tenant)['account']['plan_external_id']===$free,'Provisional Pix confirmation does not grant access');
$payments['pay_test1']['billingType']='CREDIT_CARD';$service->webhook(['id'=>'evt_card','event'=>'PAYMENT_CONFIRMED','payment'=>$payments['pay_test1']]);
$state=$service->state(1,$tenant);
ensure($state['account']['plan_external_id']===$plan&&$state['subscription']['gateway_status']==='ACTIVE','Confirmed credit card activates plan without waiting for cash settlement');
ensure($db->query('select status from financial_entries')->fetchColumn()==='pending','Card confirmation is not incorrectly booked as cash received');
$payments['pay_test1']['status']='RECEIVED';$event=['id'=>'evt_received','event'=>'PAYMENT_RECEIVED','payment'=>$payments['pay_test1']];$service->webhook($event);$service->webhook($event);
ensure((int)$db->query('select count(*) from financial_entries')->fetchColumn()===1&&$db->query('select status from financial_entries')->fetchColumn()==='paid','Receipt settles a single ledger entry idempotently');
$event['id']='evt_old';$event['event']='PAYMENT_CREATED';$event['payment']['status']='PENDING';$service->webhook($event);
ensure($service->state(1,$tenant)['subscription']['gateway_status']==='ACTIVE','Delayed event reads authoritative state');
$payments['pay_test1']['status']='REFUNDED';$service->webhook(['id'=>'evt_refund','event'=>'PAYMENT_REFUNDED','payment'=>$payments['pay_test1']]);
ensure($service->state(1,$tenant)['account']['plan_external_id']===$free&&$db->query('select status from financial_entries')->fetchColumn()==='cancelled','Refund withdraws access and reverses receivable');
$payments['pay_test1']['status']='RECEIVED';$service->refresh(1,$tenant);$state=$service->cancel(1,$tenant);
ensure($state['subscription']['gateway_status']==='CANCELLED'&&$state['account']['plan_external_id']===$plan,'Cancellation preserves paid period');
deny(fn()=>$service->subscribe(1,$tenant,$data),'No replacement subscription during already paid period');
deny(fn()=>$service->state(2,$tenant),'Tenant cannot be accessed from another product');
$failCustomer=true;deny(fn()=>$service->subscribe(1,$other,$data),'Customer timeout remains recoverable');$failCustomer=false;$service->subscribe(1,$other,$data);
ensure($posts===['customers'=>2,'subscriptions'=>2],'Customer timeout recovered without duplicate and subscription can start');
$sandbox=new AsaasSubscriptions($db,new AsaasClient('sandbox','fake',$transport),str_repeat('k',64));$sandbox->subscribe(1,$other,$data);$payments['pay_test3']['status']='RECEIVED';$sandbox->refresh(1,$other);
ensure($sandbox->state(1,$other)['account']['plan_external_id']===$free,'Sandbox does not upgrade a real product account');
$third='66666666-6666-4666-8666-666666666666';$db->prepare('insert into product_accounts values(3,1,?,?,1,1)')->execute([$third,$free]);
$failSub=true;deny(fn()=>$service->subscribe(1,$third,$data),'Subscription POST timeout remains reserved');$failSub=false;$before=$posts;$service->subscribe(1,$third,$data);
ensure($posts===$before,'Subscription timeout recovered without duplicate customer or contract');
$payments['pay_test1']['dueDate']=date('Y-m-d',strtotime('-2 months'));$state=$service->refresh(1,$tenant);
ensure($state['account']['plan_external_id']===$free&&$state['can_subscribe'],'Cancelled account returns to Community at period end and may subscribe again');
$end=new ReflectionMethod(AsaasSubscriptions::class,'periodEnd');
ensure($end->invoke($service,'2026-01-31')==='2026-02-28'&&$end->invoke($service,'2028-01-31')==='2028-02-29','Monthly periods handle short and leap months');
$secret=str_repeat('s',64);$bridge=new BillingBridge($db,['checklist'=>$secret],$service);$raw=json_encode(['action'=>'billing.state','tenant_id'=>$tenant]);$timestamp=(string)time();$nonce=str_repeat('n',32);$headers=['system'=>'checklist','timestamp'=>$timestamp,'nonce'=>$nonce,'signature'=>hash_hmac('sha256',$timestamp."\n".$nonce."\n".hash('sha256',$raw),$secret)];
ensure($bridge->handle($raw,$headers)['environment']==='production','Product requests authenticated with HMAC');
deny(fn()=>$bridge->handle($raw,$headers),'Replay rejected');$headers['nonce']=str_repeat('a',32);deny(fn()=>$bridge->handle($raw,$headers),'Forged signature rejected');
$insert=$db->prepare("insert into support_api_nonces(system_id,nonce,created_at) values(1,?,datetime('now','-2 hours'))");for($i=0;$i<245;$i++)$insert->execute(['old-'.$i]);
$previousTimezone=date_default_timezone_get();date_default_timezone_set('America/Sao_Paulo');$headers['nonce']=str_repeat('z',32);$headers['signature']=hash_hmac('sha256',$timestamp."\n".$headers['nonce']."\n".hash('sha256',$raw),$secret);
ensure($bridge->handle($raw,$headers)['environment']==='production','Old nonces do not exhaust rate limit when PHP and database timezones differ');date_default_timezone_set($previousTimezone);
echo "TOTAL $count subscription checks passed\n";
