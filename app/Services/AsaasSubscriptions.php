<?php
declare(strict_types=1);
namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;
use Throwable;

final class AsaasSubscriptions
{
    public const TERMS_VERSION='2026-10-13.billing.1';
    public const COMMERCIAL_SLUGS=['road-pro','starter','workshop-essential','workshop-professional','professional','fleet','enterprise','network-enterprise'];
    public function __construct(private PDO $db,private AsaasClient $api,private string $documentKey) {}
    private function today():string {return (new DateTimeImmutable('now',new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');}
    private function lock():string {return $this->db->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'?'':' for update';}
    private function row(string $sql,array $args=[]):?array {$q=$this->db->prepare($sql);$q->execute($args);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
    private function rows(string $sql,array $args=[]):array {$q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll(PDO::FETCH_ASSOC);}
    private function run(string $sql,array $args=[]):void {$this->db->prepare($sql)->execute($args);}
    private function transaction(callable $fn):mixed {
        $this->db->beginTransaction();try{$r=$fn();$this->db->commit();return $r;}catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    private function account(int $system,string $tenant,bool $lock=false):array {
        if(!preg_match('/^[a-f0-9-]{36}$/iD',$tenant))throw new RuntimeException('Conta inválida.',422);
        return $this->row('select * from product_accounts where system_id=? and external_id=?'.($lock?$this->lock():''),[$system,$tenant])??throw new RuntimeException('A conta ainda precisa sincronizar com a Central. Tente novamente em um minuto.',404);
    }
    public function catalog(int $system):array {
        return array_values(array_filter($this->rows("select external_id,slug,name,price,billing_cycle,features_json,limits_json from plans where system_id=? and active=true and external_id is not null order by sort_order,price",[$system]),fn($p)=>in_array($p['slug'],self::COMMERCIAL_SLUGS,true)&&$p['price']!==null&&(float)$p['price']>=5&&$p['billing_cycle']==='monthly'));
    }
    private function latest(int $system,string $tenant):?array {return $this->row('select * from billing_subscriptions where system_id=? and tenant_external_id=? and environment=? order by id desc limit 1',[$system,$tenant,$this->api->environment()]);}
    public function state(int $system,string $tenant):array {
        $account=$this->account($system,$tenant);
        $s=$this->latest($system,$tenant);
        if($s){$this->transaction(function()use($s){$locked=$this->row('select * from billing_subscriptions where id=?'.$this->lock(),[$s['id']]);$this->entitlement($locked);});$s=$this->latest($system,$tenant);$account=$this->account($system,$tenant);}
        $customer=$this->row('select document_mask from billing_customers where system_id=? and tenant_external_id=? and environment=?',[$system,$tenant,$this->api->environment()]);
        return ['environment'=>$this->api->environment(),'terms_version'=>self::TERMS_VERSION,'catalog'=>$this->catalog($system),
            'account'=>['external_id'=>$tenant,'plan_external_id'=>$account['plan_external_id'],'active'=>(bool)$account['active'],'revision'=>(int)$account['revision']],
            'document_mask'=>$customer['document_mask']??null,'can_subscribe'=>!$s||($s['gateway_status']==='CANCELLED'&&(!$s['service_until']||$s['service_until']<=$this->today())),'can_recover'=>$s&&$s['gateway_status']==='CREATING',
            'subscription'=>$s?array_intersect_key($s,array_flip(['id','plan_external_id','plan_name','amount','billing_type','gateway_status','cancel_requested','cancelled_at','service_until','first_due_date'])):null,
            'payments'=>$s?$this->rows('select payment_id,status,amount,due_date,paid_at,invoice_url from billing_payments where subscription_local_id=? order by due_date desc,id desc limit 12',[$s['id']]):[]];
    }
    public function subscribe(int $system,string $tenant,array $data):array {
        if(($data['accept_billing']??false)!==true||($data['terms_version']??'')!==self::TERMS_VERSION)throw new RuntimeException('Leia e confirme as condições da assinatura mensal.',422);
        if(!preg_match('/^[a-f0-9-]{36}$/iD',$data['user_id']??''))throw new RuntimeException('Usuário inválido.',422);
        $document=BrazilianDocument::normalize((string)($data['document']??''));
        if(!BrazilianDocument::valid($document))throw new RuntimeException('Informe um CPF ou CNPJ válido.',422);
        if(strlen($this->documentKey)<32)throw new RuntimeException('Chave de proteção da cobrança não configurada.',503);
        $name=trim((string)($data['name']??''));$email=(string)($data['email']??'');
        if(mb_strlen($name)<3||mb_strlen($name)>160||strlen($email)>180||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Revise nome e e-mail do pagador.',422);
        if(!in_array($data['billing_type']??'', ['BOLETO','PIX','UNDEFINED'],true))throw new RuntimeException('Forma de pagamento inválida.',422);
        $plan=$this->row('select * from plans where system_id=? and external_id=? and active=true',[$system,$data['plan_id']??'']);
        if(!$plan||!in_array($plan['slug'],self::COMMERCIAL_SLUGS,true)||$plan['billing_cycle']!=='monthly'||(float)$plan['price']<5)throw new RuntimeException('Plano indisponível para assinatura.',422);
        if(!isset($data['accepted_price'])||!is_numeric($data['accepted_price'])||abs((float)$data['accepted_price']-(float)$plan['price'])>0.001)throw new RuntimeException('O preço do plano mudou. Recarregue e confirme o novo valor.',409);
        $env=$this->api->environment();$hash=hash_hmac('sha256',$document,$this->documentKey);
        // Commit the durable reservation BEFORE any external POST. Retries only recover by reference.
        [$customer,$s,$freshCustomer,$freshSubscription]=$this->transaction(function()use($system,$tenant,$data,$plan,$env,$hash,$document){
            $account=$this->account($system,$tenant,true);
            if(!$account['active'])throw new RuntimeException('Conta suspensa. Entre em contato com o suporte.',403);
            $s=$this->latest($system,$tenant);
            if($s&&($s['gateway_status']!=='CANCELLED'||($s['service_until']&&$s['service_until']>$this->today()))) {
                if($s['plan_external_id']!==$plan['external_id'])throw new RuntimeException('Já existe uma assinatura ou tentativa em andamento. Cancele antes de contratar outro plano.',409);
                if($s['gateway_status']==='CANCELLED')throw new RuntimeException('O período contratado ainda está vigente. Aguarde o término antes de contratar novamente.',409);
                $freshSubscription=false;
            }else{
                $this->run('insert into billing_subscriptions(system_id,tenant_external_id,environment,plan_external_id,plan_name,amount,billing_type,external_reference,first_due_date,accepted_by,terms_version) values(?,?,?,?,?,?,?,?,?,?,?)',[$system,$tenant,$env,$plan['external_id'],$plan['name'],$plan['price'],$data['billing_type'],'rqsub-'.bin2hex(random_bytes(16)),$this->today(),$data['user_id'],self::TERMS_VERSION]);
                $s=$this->latest($system,$tenant);$freshSubscription=true;
            }
            $customer=$this->row('select * from billing_customers where system_id=? and tenant_external_id=? and environment=?',[$system,$tenant,$env]);
            $freshCustomer=!$customer;
            if($customer&&!hash_equals($customer['document_hash'],$hash))throw new RuntimeException('Documento diferente do pagador cadastrado. Solicite a correção ao suporte.',409);
            if(!$customer){$reference='rqcus-'.bin2hex(random_bytes(16));$this->run('insert into billing_customers(system_id,tenant_external_id,environment,external_reference,document_hash,document_mask) values(?,?,?,?,?,?)',[$system,$tenant,$env,$reference,$hash,'***'.substr($document,-4)]);$customer=$this->row('select * from billing_customers where external_reference=?',[$reference]);}
            return [$customer,$s,$freshCustomer,$freshSubscription];
        });
        if(!$customer['customer_id']) {
            if($freshCustomer)$remote=$this->api->request('POST','/customers',['name'=>$name,'email'=>$email,'cpfCnpj'=>$document,'externalReference'=>$customer['external_reference'],'notificationDisabled'=>$env==='sandbox']);
            else {$found=$this->api->request('GET','/customers',['externalReference'=>$customer['external_reference'],'limit'=>2]);if(count($found['data']??[])!==1)throw new RuntimeException('Cadastro anterior sem confirmação. O suporte precisa verificar a tentativa antes de repetir.',409);$remote=$found['data'][0];}
            if(!preg_match('/^cus_[a-zA-Z0-9_]+$/D',$remote['id']??'')||($remote['externalReference']??'')!==$customer['external_reference']||BrazilianDocument::normalize((string)($remote['cpfCnpj']??''))!==$document)throw new RuntimeException('Cadastro do pagador incompatível.',409);
            $customer['customer_id']=$remote['id'];$this->run('update billing_customers set customer_id=? where id=?',[$remote['id'],$customer['id']]);
        }
        $this->run('update billing_subscriptions set customer_id=? where id=?',[$customer['customer_id'],$s['id']]);
        if(!$s['subscription_id']) {
            $canPost=$this->transaction(function()use($s){$locked=$this->row('select * from billing_subscriptions where id=?'.$this->lock(),[$s['id']]);if($locked['subscription_attempted'])return false;$this->run('update billing_subscriptions set subscription_attempted=true where id=?',[$s['id']]);return true;});
            if($canPost)$remote=$this->api->request('POST','/subscriptions',['customer'=>$customer['customer_id'],'billingType'=>$s['billing_type'],'value'=>(float)$s['amount'],'nextDueDate'=>$s['first_due_date'],'cycle'=>'MONTHLY','description'=>'ZVTech Checklist — '.$s['plan_name'].' / mensal','externalReference'=>$s['external_reference']]);
            else {$found=$this->api->request('GET','/subscriptions',['externalReference'=>$s['external_reference'],'limit'=>2]);if(count($found['data']??[])!==1)throw new RuntimeException('Assinatura anterior sem confirmação. Nova emissão bloqueada para evitar duplicidade; consulte o suporte.',409);$remote=$found['data'][0];}
            if(!preg_match('/^sub_[a-zA-Z0-9_]+$/D',$remote['id']??'')||($remote['externalReference']??'')!==$s['external_reference']||($remote['customer']??'')!==$customer['customer_id']||($remote['cycle']??'')!=='MONTHLY'||abs((float)($remote['value']??0)-(float)$s['amount'])>0.001)throw new RuntimeException('Assinatura incompatível.',409);
            $this->run("update billing_subscriptions set subscription_id=?,gateway_status='PENDING',updated_at=current_timestamp where id=? and subscription_id is null",[$remote['id'],$s['id']]);
        }
        return $this->refresh($system,$tenant);
    }
    public function refresh(int $system,string $tenant):array {
        $this->account($system,$tenant);$s=$this->latest($system,$tenant);
        if($s&&$s['subscription_id']) {
            if($s['cancel_requested']&&!$s['cancelled_at'])$this->cancelRemote($s);
            $list=$this->api->request('GET','/subscriptions/'.rawurlencode($s['subscription_id']).'/payments',['limit'=>100]);
            foreach($list['data']??[] as $payment)$this->payment($s,$payment,true);
        }
        return $this->state($system,$tenant);
    }
    public function cancel(int $system,string $tenant):array {
        $this->account($system,$tenant);$s=$this->latest($system,$tenant);
        if(!$s||!$s['subscription_id'])throw new RuntimeException('Assinatura ainda sem confirmação. Consulte o suporte.',409);
        $this->run('update billing_subscriptions set cancel_requested=true where id=?',[$s['id']]);
        $this->cancelRemote($s);return $this->state($system,$tenant);
    }
    private function cancelRemote(array $s):void {
        $list=$this->api->request('GET','/subscriptions',['externalReference'=>$s['external_reference'],'includeDeleted'=>'true','limit'=>2]);
        $remote=$list['data'][0]??[];
        if(count($list['data']??[])!==1||($remote['id']??'')!==$s['subscription_id'])throw new RuntimeException('Assinatura sem confirmação para cancelamento.',409);
        $result=!empty($remote['deleted'])?['deleted'=>true]:$this->api->request('DELETE','/subscriptions/'.rawurlencode($s['subscription_id']));
        if(($result['deleted']??false)!==true)throw new RuntimeException('Cancelamento ainda não confirmado pelo Asaas.',503);
        $this->run("update billing_subscriptions set gateway_status='CANCELLED',cancel_requested=true,cancelled_at=current_timestamp,updated_at=current_timestamp where id=?",[$s['id']]);
    }
    private function validDate(mixed $date):bool {return is_string($date)&&($d=DateTimeImmutable::createFromFormat('!Y-m-d',$date))&&$d->format('Y-m-d')===$date;}
    private function periodEnd(string $due):string {
        $d=new DateTimeImmutable($due);$next=$d->modify('first day of next month');$day=min((int)$d->format('d'),(int)$next->format('t'));return $next->setDate((int)$next->format('Y'),(int)$next->format('m'),$day)->format('Y-m-d');
    }
    private function payment(array $s,array $p,bool $authoritative=false):void {
        $this->transaction(function()use($s,$p,$authoritative){
            $s=$this->row('select * from billing_subscriptions where id=?'.$this->lock(),[$s['id']]);
            if($authoritative){if(!is_string($p['id']??null)||!preg_match('/^pay_[a-zA-Z0-9_]+$/D',$p['id']))throw new RuntimeException('Pagamento inválido.',409);$p=$this->api->request('GET','/payments/'.rawurlencode($p['id']));}
            if(($p['subscription']??'')!==$s['subscription_id']||($p['customer']??'')!==$s['customer_id']||!preg_match('/^pay_[a-zA-Z0-9_]+$/D',$p['id']??'')||!$this->validDate($p['dueDate']??null)||abs((float)($p['originalValue']??$p['value']??0)-(float)$s['amount'])>0.001)throw new RuntimeException('Pagamento incompatível com a assinatura.',409);
            $status=!empty($p['deleted'])?'DELETED':($p['status']??'UNKNOWN');
            if(!is_string($status)||strlen($status)>50)throw new RuntimeException('Estado inválido.',409);
            $paid=in_array($status,['RECEIVED','RECEIVED_IN_CASH'],true);$date=$paid?($p['paymentDate']??$p['clientPaymentDate']??null):null;
            if($paid&&!$this->validDate($date))throw new RuntimeException('Recebimento sem data válida.',409);
            $url=$p['invoiceUrl']??null;if($url&&(!filter_var($url,FILTER_VALIDATE_URL)||parse_url($url,PHP_URL_SCHEME)!=='https'||!preg_match('/(^|\.)asaas\.com$/D',parse_url($url,PHP_URL_HOST)??'')))$url=null;
            $old=$this->row('select * from billing_payments where environment=? and payment_id=?',[$s['environment'],$p['id']]);
            if(!$old){$this->run('insert into billing_payments(subscription_local_id,environment,payment_id,status,amount,due_date,paid_at,invoice_url) values(?,?,?,?,?,?,?,?)',[$s['id'],$s['environment'],$p['id'],$status,$s['amount'],$p['dueDate'],$date,$url]);$old=$this->row('select * from billing_payments where environment=? and payment_id=?',[$s['environment'],$p['id']]);}
            if((int)$old['subscription_local_id']!==(int)$s['id'])throw new RuntimeException('Pagamento pertence a outra assinatura.',409);
            $this->run('update billing_payments set status=?,billing_type=?,due_date=?,paid_at=?,invoice_url=?,updated_at=current_timestamp where id=?',[$status,$p['billingType']??$s['billing_type'],$p['dueDate'],$date,$url,$old['id']]);
            if($s['environment']==='production') {
                $local=$paid?'paid':(in_array($status,['REFUNDED','DELETED'],true)?'cancelled':'pending');
                if(!$old['entry_id']) {
                    $description=mb_substr('Assinatura '.$s['plan_name'].' — '.$s['tenant_external_id'].' — '.$p['dueDate'],0,180);
                    $this->run('insert into financial_entries(description,category,direction,amount,due_date,paid_at,status,payment_method,notes) values(?,?,?,?,?,?,?,?,?)',[$description,'Assinaturas SaaS','receivable',$s['amount'],$p['dueDate'],$date,$local,'Asaas','subscription='.$s['subscription_id'].'; payment='.$p['id']]);
                    $entry=(int)$this->db->lastInsertId();$this->run('update billing_payments set entry_id=? where id=?',[$entry,$old['id']]);
                } else $this->run('update financial_entries set status=?,paid_at=? where id=?',[$local,$date,$old['entry_id']]);
            }
            $this->entitlement($s);
        });
    }
    private function entitlement(array $s):void {
        $last=$this->row("select due_date from billing_payments where subscription_local_id=? and (status in ('RECEIVED','RECEIVED_IN_CASH') or (status='CONFIRMED' and billing_type='CREDIT_CARD')) order by due_date desc limit 1",[$s['id']]);
        $until=$last?$this->periodEnd($last['due_date']):null;
        $active=$until&&$until>$this->today();
        $status=$s['cancelled_at']?'CANCELLED':($active?'ACTIVE':($last?'PAST_DUE':($s['subscription_id']?'PENDING':'CREATING')));
        $this->run('update billing_subscriptions set service_until=?,gateway_status=?,updated_at=current_timestamp where id=?',[$until,$status,$s['id']]);
        if($s['environment']!=='production')return;
        // Older subscriptions cannot replace the newest contract for the same tenant.
        if((int)$this->latest((int)$s['system_id'],$s['tenant_external_id'])['id']!==(int)$s['id'])return;
        $account=$this->account((int)$s['system_id'],$s['tenant_external_id'],true);
        $hadAccess=$last||$s['service_until']!==null||in_array($s['gateway_status'],['ACTIVE','PAST_DUE'],true);
        $target=$active?$s['plan_external_id']:($hadAccess?$this->row("select external_id from plans where system_id=? and slug='community' and active=true",[$s['system_id']])['external_id']??null:null);
        // Do not change a pre-existing commercial plan before this contract's first payment.
        if(!$target||$target===$account['plan_external_id'])return;
        $this->run('update product_accounts set plan_external_id=?,revision=revision+1 where id=?',[$target,$account['id']]);
        $this->run('insert into product_admin_audit(system_id,actor_id,entity_type,external_id,reason,before_json,after_json) values(?,null,?,?,?,?,?)',[$s['system_id'],'account',$s['tenant_external_id'],'Conciliação da assinatura Asaas',json_encode(['plan_external_id'=>$account['plan_external_id']]),json_encode(['plan_external_id'=>$target,'subscription'=>$s['subscription_id']])]);
    }
    public function webhook(array $event):bool {
        $env=$this->api->environment();$isPayment=str_starts_with((string)($event['event']??''),'PAYMENT_');
        $remoteId=$isPayment?($event['payment']['subscription']??null):($event['subscription']['id']??null);
        if(!is_string($remoteId))return false;
        $s=$this->row('select * from billing_subscriptions where environment=? and subscription_id=?',[$env,$remoteId]);
        if(!$s){$ref=$isPayment?($event['payment']['externalReference']??''):($event['subscription']['externalReference']??'');if(is_string($ref))$s=$this->row('select * from billing_subscriptions where environment=? and external_reference=?',[$env,$ref]);}
        if(!$s)return false;
        if($this->row('select event_id from billing_events where environment=? and event_id=?',[$env,$event['id']]))return true;
        // Resolve creation races through the authoritative subscription, never through untrusted event fields.
        if(!$s['subscription_id']) {
            $remote=$this->api->request('GET','/subscriptions/'.rawurlencode($remoteId));
            $customer=$this->row('select customer_id from billing_customers where system_id=? and tenant_external_id=? and environment=?',[$s['system_id'],$s['tenant_external_id'],$env]);
            if(($remote['externalReference']??'')!==$s['external_reference']||($remote['customer']??'')!==($customer['customer_id']??null)||abs((float)($remote['value']??0)-(float)$s['amount'])>0.001)throw new RuntimeException('Assinatura incompatível.',409);
            $this->run('update billing_subscriptions set subscription_id=?,customer_id=? where id=? and subscription_id is null',[$remoteId,$remote['customer'],$s['id']]);$s=$this->row('select * from billing_subscriptions where id=?',[$s['id']]);
        }
        if($isPayment) {
            $id=$event['payment']['id']??'';if(!is_string($id)||!preg_match('/^pay_[a-zA-Z0-9_]+$/D',$id))throw new RuntimeException('Evento inválido.',400);
            $this->payment($s,['id'=>$id],true);
        }else{
            $list=$this->api->request('GET','/subscriptions',['externalReference'=>$s['external_reference'],'includeDeleted'=>'true','limit'=>2]);
            $remote=$list['data'][0]??[];
            if(count($list['data']??[])!==1||($remote['id']??'')!==$remoteId)throw new RuntimeException('Assinatura sem confirmação no Asaas.',409);
            if(!empty($remote['deleted'])||($remote['status']??'')==='INACTIVE')$this->run("update billing_subscriptions set cancelled_at=current_timestamp,gateway_status='CANCELLED' where id=?",[$s['id']]);
        }
        try{$this->run('insert into billing_events(environment,event_id,event_type,subscription_local_id) values(?,?,?,?)',[$env,$event['id'],$event['event'],$s['id']]);}catch(\PDOException $e){if(!in_array($e->getCode(),['23000','23505'],true))throw $e;}
        return true;
    }
}
