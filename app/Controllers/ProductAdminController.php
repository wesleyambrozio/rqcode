<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Auth,Controller,Database};
use App\Services\ProductAdministration;
use RuntimeException;
use Throwable;

final class ProductAdminController extends Controller
{
    public function index():void
    {
        $this->allow();$db=Database::connection();
        $systems=$db->query('select s.id,s.name,s.slug,a.enabled,a.last_sync_at,a.summary_json from saas_systems s join product_admin_state a on a.system_id=s.id where a.enabled=true order by s.name')->fetchAll();
        $systemId=(int)$this->input('system_id',$systems[0]['id']??0);$system=null;
        if($product=(string)$this->input('product','')){ $systemId=-1;foreach($systems as $candidate)if($candidate['slug']===$product)$systemId=(int)$candidate['id']; }
        foreach($systems as $candidate)if((int)$candidate['id']===$systemId)$system=$candidate;
        if(!$system && $systemId){http_response_code(404);exit('Produto não habilitado.');}
        $plans=[];$accounts=[];$audit=[];$total=0;$query=mb_substr(trim((string)$this->input('q','')),0,120);$page=max(1,min(100000,(int)$this->input('page',1)));
        $health=[];$pending=0;$referralState=null;$referrers=[];$referralTotal=0;
        $referralQuery=mb_substr(trim((string)$this->input('referral_q','')),0,80);
        $referralPage=max(1,min(100000,(int)$this->input('referral_page',1)));
        if($system){
            $q=$db->prepare('select * from product_referral_state where system_id=?');$q->execute([$systemId]);$referralState=$q->fetch()?:null;
            $refWhere='system_id=?';$refParams=[$systemId];
            if($referralQuery!==''){$refWhere.=' and (lower(name) like ? or lower(code) like ?)';$refParams[]='%'.mb_strtolower($referralQuery).'%';$refParams[]='%'.mb_strtolower($referralQuery).'%';}
            $q=$db->prepare('select count(*) from product_referrers where '.$refWhere);$q->execute($refParams);$referralTotal=(int)$q->fetchColumn();
            $q=$db->prepare('select * from product_referrers where '.$refWhere.' order by registered desc,external_id limit 50 offset '.(($referralPage-1)*50));$q->execute($refParams);$referrers=$q->fetchAll();
            $q=$db->prepare('select p.*,(select count(*) from product_accounts a where a.system_id=p.system_id and a.plan_external_id=p.external_id) accounts_count from plans p where p.system_id=? and p.external_id is not null order by p.sort_order,p.name');$q->execute([$systemId]);$plans=$q->fetchAll();
            $where='a.system_id=?';$params=[$systemId];
            if($query!==''){$where.=' and (lower(a.name) like ? or lower(a.slug) like ?)';$params[]='%'.mb_strtolower($query).'%';$params[]='%'.mb_strtolower($query).'%';}
            $q=$db->prepare('select count(*) from product_accounts a where '.$where);$q->execute($params);$total=(int)$q->fetchColumn();
            $q=$db->prepare('select a.* from product_accounts a where '.$where.' order by a.name,a.id limit 50 offset '.(($page-1)*50));$q->execute($params);$accounts=$q->fetchAll();
            $q=$db->prepare('select h.*,u.name actor_name from product_admin_audit h left join admin_users u on u.id=h.actor_id where h.system_id=? order by h.id desc limit 50');$q->execute([$systemId]);$audit=$q->fetchAll();
            $health=json_decode($system['summary_json']??'{}',true)?:[];
            $q=$db->prepare('select count(*) from product_accounts where system_id=? and (revision>observed_revision or sync_error is not null)');$q->execute([$systemId]);$pending=(int)$q->fetchColumn();
            foreach($plans as $plan)if($plan['revision']>$plan['applied_revision']||$plan['sync_error'])$pending++;
        }
        $this->view('product-admin/index',compact('systems','system','systemId','plans','accounts','query','page','total','audit','health','pending','referralState','referrers','referralTotal','referralPage','referralQuery')+['title'=>'Administração dos produtos','features'=>ProductAdministration::FEATURES,'limits'=>ProductAdministration::LIMITS]);
    }

    public function plan():void
    {
        $this->allow();$system=(int)$this->input('system_id');
        try{
            $this->confirmation();$db=Database::connection();$service=new ProductAdministration($db);$id=(string)$this->input('external_id','');$old=null;
            if($id){$q=$db->prepare('select * from plans where system_id=? and external_id=?');$q->execute([$system,$id]);$old=$q->fetch();if(!$old)throw new RuntimeException('Plano não encontrado.');}
            $features=$old?(json_decode($old['features_json']??'{}',true)?:[]):[];
            $limits=$old?(json_decode($old['limits_json']??'{}',true)?:[]):[];
            $postedFeatures=$this->input('features',[]);$postedLimits=$this->input('limits',[]);
            if(!is_array($postedFeatures)||!is_array($postedLimits))throw new RuntimeException('Regras inválidas.');
            foreach(ProductAdministration::FEATURES as $key=>$label){
                // Missing on an imported plan stays missing unless explicitly enabled.
                if(array_key_exists($key,$features)||isset($postedFeatures[$key])||!$old)$features[$key]=($postedFeatures[$key]??'0')==='1';
            }
            foreach(ProductAdministration::LIMITS as $key=>$label){
                if(!array_key_exists($key,$postedLimits))continue;
                $value=trim((string)$postedLimits[$key]);
                if($value===''){unset($limits[$key]);continue;}
                if(!preg_match('/^(?:-1|\d{1,10})$/D',$value))throw new RuntimeException('Limite inválido: '.$label);
                $limits[$key]=(int)$value;
            }
            $sort=(string)$this->input('sort_order','0');if(!ctype_digit($sort))throw new RuntimeException('Ordem inválida.');
            $price=trim((string)$this->input('price_monthly',''));
            $data=['name'=>trim((string)$this->input('name')),'slug'=>$old['slug']??trim((string)$this->input('slug')),
                'description'=>trim((string)$this->input('description',''))?:null,'price_monthly'=>$price===''?null:$price,
                'active'=>$this->input('active','0')==='1','features'=>$features,'limits'=>$limits,'sort_order'=>(int)$sort];
            $service->savePlan($system,$id?:null,$data,$old?(int)$this->input('revision'):null,(int)Auth::user()['id'],trim((string)$this->input('reason')));
            $_SESSION['flash_success']='Plano salvo. Aguarde a confirmação de aplicação pelo Checklist. Nenhuma cobrança foi gerada.';
        }catch(Throwable $e){$_SESSION['flash']=$e instanceof RuntimeException?$e->getMessage():'Não foi possível salvar; revise os campos.';}
        redirect('/administracao-produtos?system_id='.$system.'#planos');
    }

    public function account():void
    {
        $this->allow();$system=(int)$this->input('system_id');
        try{
            $this->confirmation();
            (new ProductAdministration(Database::connection()))->saveAccount($system,(string)$this->input('external_id'),(string)$this->input('plan_external_id'),$this->input('active','0')==='1',(int)$this->input('revision'),(int)Auth::user()['id'],trim((string)$this->input('reason')));
            $_SESSION['flash_success']='Alteração registrada. A conta será atualizada na próxima sincronização; acompanhe a confirmação abaixo.';
        }catch(Throwable $e){$_SESSION['flash']=$e instanceof RuntimeException?$e->getMessage():'Não foi possível salvar; revise os campos.';}
        redirect('/administracao-produtos?system_id='.$system.'#contas');
    }

    private function confirmation():void
    {
        if(empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'],(string)$this->input('_csrf','')))throw new RuntimeException('Sessão expirada.');
        if($this->input('confirm')!=='1')throw new RuntimeException('Confirme o impacto antes de aplicar a alteração.');
        $reason=trim((string)$this->input('reason'));if($reason===''||mb_strlen($reason)>500)throw new RuntimeException('Informe um motivo de até 500 caracteres.');
    }
    private function allow():void
    {
        \App\Services\CentralAdminAccess::enforce();
    }
}
