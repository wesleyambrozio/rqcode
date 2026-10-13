<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

/** Central desired state. Product snapshots never overwrite administrative decisions. */
final class ProductAdministration
{
    public const FEATURES = [
        'dashboard'=>'Dashboard', 'templates'=>'Modelos de checklist', 'executions'=>'Executar checklists',
        'users_management'=>'Usuários e grupos', 'vehicles'=>'Veículos', 'voice_commands'=>'Comandos de voz',
        'advanced_reports'=>'Relatórios avançados', 'workshop_network'=>'Rede de oficinas',
        'public_api'=>'API pública', 'ai_insights'=>'Recurso de insights (não ativa provedor de IA)',
    ];
    public const LIMITS = [
        'users'=>'Usuários', 'vehicles'=>'Veículos', 'vehicles_driver'=>'Veículos — motorista (substitui limite geral)', 'active_templates'=>'Modelos ativos',
        'executions_month'=>'Execuções por mês', 'assignment_links_daily'=>'Links por dia',
        'storage_mb'=>'Armazenamento (MB)', 'history_days'=>'Histórico (dias; não apaga registros)',
        'quick_reports_month_driver'=>'Relatos/mês — motorista', 'quick_reports_month_workshop'=>'Relatos/mês — oficina',
    ];

    public function __construct(private PDO $db) {}

    public function enabled(int $system): bool
    {
        $q=$this->db->prepare('select enabled from product_admin_state where system_id=?');$q->execute([$system]);
        return (bool)$q->fetchColumn();
    }

    // Called inside SupportBridge's authenticated product transaction and product lock.
    public function sync(int $system,array $data): array
    {
        $this->requireEnabled($system);
        if (($data['version']??null)!==1) throw new RuntimeException('Unsupported administration protocol',422);
        foreach ($this->rows($data,'plans',300) as $row) {
            $plan=$this->plan($row);
            if (!$this->find('plans',$system,$plan['external_id'])) {
                $this->insertPlan($system,$plan);
                $this->audit($system,null,'plan',$plan['external_id'],'Importação inicial; condições preservadas.',null,$plan);
            }
        }
        $ids=[];
        foreach ($this->rows($data,'accounts',100) as $row) {
            $id=$this->uuid($row['external_id']??null);$ids[]=$id;
            $plan=$row['plan_external_id']??null;
            if ($plan!==null) { $this->uuid($plan); $this->requirePlan($system,$plan,false); }
            $active=$this->boolean($row['active']??null);
            $name=$this->text($row['name']??null,180);$slug=$this->text($row['slug']??null,180);
            $profile=$row['profile']??'fleet';
            if (!in_array($profile,['driver','workshop','fleet'],true)) throw new RuntimeException('Invalid account profile',422);
            $usage=$this->counters($row['usage']??[],['users','vehicles','templates','executions_month','storage_bytes']);
            $old=$this->find('product_accounts',$system,$id);
            if (!$old) {
                $q=$this->db->prepare('insert into product_accounts(system_id,external_id,name,slug,profile,plan_external_id,active,observed_plan_external_id,observed_active,usage_json,last_seen_at) values(?,?,?,?,?,?,?,?,?,?,current_timestamp)');
                $q->execute([$system,$id,$name,$slug,$profile,$plan,(int)$active,$plan,(int)$active,json_encode($usage,JSON_THROW_ON_ERROR)]);
                $this->audit($system,null,'account',$id,'Importação inicial; vínculo preservado.',null,['plan_external_id'=>$plan,'active'=>$active]);
            } else {
                // Metadata is observational only. An offline/stale product cannot revert desired state.
                $this->db->prepare('update product_accounts set name=?,slug=?,profile=?,usage_json=?,last_seen_at=current_timestamp where id=?')->execute([$name,$slug,$profile,json_encode($usage,JSON_THROW_ON_ERROR),$old['id']]);
            }
        }
        $summary=$this->counters($data['summary']??[],['accounts','active_accounts','users','new_accounts_today','active_users','executions_month','support_pending','support_errors','support_open']);
        $this->db->prepare('update product_admin_state set last_sync_at=current_timestamp,summary_json=? where system_id=?')->execute([json_encode($summary,JSON_THROW_ON_ERROR),$system]);
        $q=$this->db->prepare('select * from plans where system_id=? and external_id is not null order by id limit 301');$q->execute([$system]);$plans=$q->fetchAll();
        if(count($plans)>300) throw new RuntimeException('Plan catalog exceeds supported batch',422);
        $q=$this->db->prepare('select * from product_accounts where system_id=? and (observed_revision<revision or sync_error is not null) order by coalesce(applied_at,last_seen_at),id limit 100');$q->execute([$system]);
        $accounts=[];foreach($q->fetchAll() as $row)$accounts[$row['external_id']]=$row;
        if($ids){$q=$this->db->prepare('select * from product_accounts where system_id=? and external_id in ('.implode(',',array_fill(0,count($ids),'?')).')');$q->execute([$system,...$ids]);foreach($q->fetchAll() as $row)$accounts[$row['external_id']]=$row;}
        return ['version'=>1,'plans'=>array_map([$this,'exportPlan'],$plans),'accounts'=>array_map(fn($row)=>[
            'external_id'=>$row['external_id'],'plan_external_id'=>$row['plan_external_id'],'active'=>(bool)$row['active'],'revision'=>(int)$row['revision'],
        ],array_values($accounts))];
    }

    public function acknowledge(int $system,array $data): array
    {
        $this->requireEnabled($system);
        foreach(['plans'=>'plans','accounts'=>'product_accounts'] as $key=>$table) {
            foreach($this->rows($data,$key,300) as $row){
                $id=$this->uuid($row['external_id']??null);$old=$this->find($table,$system,$id);
                if(!$old)throw new RuntimeException('Unknown entity',404);
                $revision=$row['revision']??null;
                if(!is_int($revision)||$revision<1||$revision>(int)$old['revision'])throw new RuntimeException('Invalid acknowledgement revision',409);
                $column=$table==='plans'?'applied_revision':'observed_revision';
                if($revision<(int)$old[$column])continue;
                $ok=$this->boolean($row['ok']??null);
                // Old failures must not obscure a newer successful confirmation.
                if(!$ok && $revision<=(int)$old[$column])continue;
                if(!$ok){$this->db->prepare("update $table set sync_error=? where id=?")->execute(['Aplicação pendente: verificar diagnóstico no produto.',$old['id']]);continue;}
                $extra=[];$sql="update $table set $column=?,sync_error=null,applied_at=current_timestamp";
                if($table==='product_accounts'){
                    $plan=$row['plan_external_id']??null;if($plan!==null)$this->requirePlan($system,$this->uuid($plan),false);
                    $sql.=',observed_plan_external_id=?,observed_active=?';$extra=[$plan,(int)$this->boolean($row['active']??null)];
                }
                $this->db->prepare($sql.' where id=?')->execute([$revision,...$extra,$old['id']]);
            }
        }
        return ['ok'=>true];
    }

    public function savePlan(int $system,?string $id,array $data,?int $revision,int $actor,string $reason): string
    {
        return $this->transaction($system,function()use($system,$id,$data,$revision,$actor,$reason){
            $old=$id?$this->find('plans',$system,$this->uuid($id)):null;
            if($id&&!$old)throw new RuntimeException('Plano não encontrado.',404);
            if($old&&(int)$old['revision']!==$revision)throw new RuntimeException('O plano mudou. Recarregue antes de salvar.',409);
            $plan=$this->plan(array_merge($data,['external_id'=>$id??$this->newUuid()]));
            if($old && $old['slug']!==$plan['slug'])throw new RuntimeException('O código do plano não pode ser alterado.',422);
            if($plan['slug']==='community' && (!$plan['active'] || $plan['price_monthly']===null || (float)$plan['price_monthly']!==0.0))throw new RuntimeException('Comunidade deve permanecer ativo e gratuito para os novos cadastros.',422);
            if($old){
                $this->db->prepare('update plans set name=?,price=?,active=?,description=?,features_json=?,limits_json=?,sort_order=?,revision=revision+1,sync_error=null where id=?')->execute([$plan['name'],$plan['price_monthly'],(int)$plan['active'],$plan['description'],json_encode($plan['features'],JSON_THROW_ON_ERROR),json_encode($plan['limits'],JSON_THROW_ON_ERROR),$plan['sort_order'],$old['id']]);
            }else{$this->insertPlan($system,$plan);}
            $this->audit($system,$actor,'plan',$plan['external_id'],$this->text($reason,500),$old?$this->exportPlan($old):null,$plan);
            return $plan['external_id'];
        });
    }

    public function saveAccount(int $system,string $id,string $plan,bool $active,int $revision,int $actor,string $reason): void
    {
        $this->transaction($system,function()use($system,$id,$plan,$active,$revision,$actor,$reason){
            $old=$this->find('product_accounts',$system,$this->uuid($id));
            if(!$old)throw new RuntimeException('Conta não encontrada.',404);
            if((int)$old['revision']!==$revision)throw new RuntimeException('A conta mudou. Recarregue antes de salvar.',409);
            $this->requirePlan($system,$this->uuid($plan),$old['plan_external_id']!==$plan);
            $this->db->prepare('update product_accounts set plan_external_id=?,active=?,revision=revision+1,sync_error=null where id=?')->execute([$plan,(int)$active,$old['id']]);
            $this->audit($system,$actor,'account',$id,$this->text($reason,500),['plan_external_id'=>$old['plan_external_id'],'active'=>(bool)$old['active'],'revision'=>(int)$old['revision']],['plan_external_id'=>$plan,'active'=>$active,'revision'=>$revision+1]);
        });
    }

    public function exportPlan(array $row): array
    {
        return ['external_id'=>$row['external_id'],'slug'=>$row['slug'],'name'=>$row['name'],'description'=>$row['description'],
            'price_monthly'=>$row['price'],'active'=>(bool)$row['active'],'features'=>json_decode($row['features_json']??'{}',true)??[],
            'limits'=>json_decode($row['limits_json']??'{}',true)??[],'sort_order'=>(int)$row['sort_order'],'revision'=>(int)$row['revision']];
    }

    public function plan(array $row): array
    {
        $id=$this->uuid($row['external_id']??null);$slug=$this->text($row['slug']??null,120);
        if(!preg_match('/^[a-z0-9][a-z0-9-]*$/D',$slug))throw new RuntimeException('Código de plano inválido.',422);
        $price=$row['price_monthly']??null;
        if($price!==null && (!is_scalar($price)||!preg_match('/^\d{1,8}(\.\d{1,2})?$/D',(string)$price)))throw new RuntimeException('Preço inválido.',422);
        $features=$row['features']??[];$limits=$row['limits']??[];
        foreach(['features'=>$features,'limits'=>$limits] as $kind=>$values){
            if(!is_array($values)||count($values)>100)throw new RuntimeException('Regras inválidas.',422);
            foreach($values as $key=>$value){
                if(!preg_match('/^[a-z][a-z0-9_]{0,79}$/D',(string)$key)||($kind==='features'?!is_bool($value):($value!==null&&(!is_int($value)||$value< -1||$value>1000000000))))throw new RuntimeException('Valor inválido para '.$key,422);
            }
        }
        $sort=$row['sort_order']??0;if(!is_int($sort)||$sort<0||$sort>1000000)throw new RuntimeException('Ordem inválida.',422);
        $description=$row['description']??null;
        if($description!==null&&(!is_string($description)||mb_strlen($description)>1000))throw new RuntimeException('Descrição inválida.',422);
        return ['external_id'=>$id,'name'=>$this->text($row['name']??null,120),'slug'=>$slug,'description'=>$description,'price_monthly'=>$price===null?null:number_format((float)$price,2,'.',''),
            'features'=>$features,'limits'=>$limits,'active'=>$this->boolean($row['active']??null),'sort_order'=>$sort];
    }

    private function insertPlan(int $system,array $p):void
    {
        $q=$this->db->prepare('select id from plans where system_id=? and slug=?');$q->execute([$system,$p['slug']]);
        if($q->fetchColumn())throw new RuntimeException('Já existe um plano com este código; não será sobrescrito.',409);
        $this->db->prepare("insert into plans(system_id,external_id,slug,name,billing_cycle,price,active,description,features_json,limits_json,sort_order) values(?,?,?,?,'monthly',?,?,?,?,?,?)")->execute([$system,$p['external_id'],$p['slug'],$p['name'],$p['price_monthly'],(int)$p['active'],$p['description'],json_encode($p['features'],JSON_THROW_ON_ERROR),json_encode($p['limits'],JSON_THROW_ON_ERROR),$p['sort_order']]);
    }
    private function find(string $table,int $system,string $id):array|false {$q=$this->db->prepare("select * from $table where system_id=? and external_id=?");$q->execute([$system,$id]);return $q->fetch();}
    private function requirePlan(int $system,string $id,bool $active):void {$row=$this->find('plans',$system,$id);if(!$row||($active&&!(bool)$row['active']))throw new RuntimeException('Plano indisponível para este produto.',422);}
    private function requireEnabled(int $system):void {if(!$this->enabled($system))throw new RuntimeException('Administração não habilitada para este produto.',403);}
    private function rows(array $data,string $key,int $max):array {$rows=$data[$key]??null;if(!is_array($rows)||!array_is_list($rows)||count($rows)>$max)throw new RuntimeException('Invalid batch: '.$key,422);foreach($rows as $row)if(!is_array($row))throw new RuntimeException('Invalid row',422);return $rows;}
    private function uuid(mixed $id):string {if(!is_string($id)||!preg_match('/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D',$id))throw new RuntimeException('Invalid external ID',422);return $id;}
    private function boolean(mixed $value):bool {if(!is_bool($value))throw new RuntimeException('Invalid boolean',422);return $value;}
    private function text(mixed $value,int $max):string {if(!is_string($value)||trim($value)===''||mb_strlen($value)>$max)throw new RuntimeException('Texto inválido.',422);return $value;}
    private function counters(mixed $values,array $keys):array {if(!is_array($values))throw new RuntimeException('Invalid counters',422);$out=[];foreach($keys as $key){$value=$values[$key]??0;if(!is_int($value)||$value<0)throw new RuntimeException('Invalid counter',422);$out[$key]=$value;}return $out;}
    private function newUuid():string {$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);$s=bin2hex($b);return substr($s,0,8).'-'.substr($s,8,4).'-'.substr($s,12,4).'-'.substr($s,16,4).'-'.substr($s,20);}
    private function audit(int $system,?int $actor,string $type,string $id,string $reason,?array $before,array $after):void {$this->db->prepare('insert into product_admin_audit(system_id,actor_id,entity_type,external_id,reason,before_json,after_json) values(?,?,?,?,?,?,?)')->execute([$system,$actor,$type,$id,$reason,$before===null?null:json_encode($before,JSON_THROW_ON_ERROR),json_encode($after,JSON_THROW_ON_ERROR)]);}
    private function transaction(int $system,callable $fn):mixed {
        $this->db->beginTransaction();try{$this->db->query('select id from saas_systems where id='.$system.' for update')->fetch();$this->requireEnabled($system);$result=$fn();$this->db->commit();return $result;}catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
}
