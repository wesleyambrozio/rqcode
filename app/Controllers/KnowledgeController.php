<?php
namespace App\Controllers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\KnowledgePublisher;
use Throwable;

final class KnowledgeController extends Controller
{
    public function index():void
    {
        $this->allow();
        $db=Database::connection(); $systemId=(int)$this->input('system_id',0); $status=trim((string)$this->input('status',''));
        $where=[]; $params=[];
        if($systemId>0){$where[]='d.system_id=?';$params[]=$systemId;}
        if($status!==''){$where[]='d.status=?';$params[]=$status;}
        $q=$db->prepare('select d.*,s.name system_name from knowledge_documents d join saas_systems s on s.id=d.system_id'.($where?' where '.implode(' and ',$where):'').' order by d.updated_at desc limit 300');
        $q->execute($params);$documents=$q->fetchAll();
        $systems=$db->query('select id,name from saas_systems where active=true order by name')->fetchAll();
        $history=[];
        if($id=(int)$this->input('history_id',0)){
            $q=$db->prepare('select version,snapshot_json,created_at from knowledge_document_versions where document_id=? order by version desc limit 30');$q->execute([$id]);$history=$q->fetchAll();
        }
        $this->view('knowledge/index',compact('documents','systems','systemId','status','history')+['title'=>'Base de conhecimento']);
    }
    public function store():void { $this->write(false); }
    public function update():void { $this->write(true); }
    private function write(bool $update):void
    {
        $this->allow();
        try {
            $db=Database::connection(); $old=null;
            if($update){
                $q=$db->prepare('select * from knowledge_documents where id=?');$q->execute([(int)$this->input('id')]);$old=$q->fetch();
                if(!$old)throw new \RuntimeException('Documento não encontrado.');
            }
            $title=trim((string)$this->input('title',$old['title']??''));
            $slug=$old['slug']??(trim((string)$this->input('slug'))?:$this->slug($title));
            $metadata=json_decode($old['metadata_json']??'{}',true)?:[];
            $metadata['profiles']=array_values(array_intersect((array)$this->input('profiles',['driver','workshop','fleet']),['driver','workshop','fleet']));
            $metadata['paths']=array_values(array_filter(array_map('trim',explode(',',(string)$this->input('paths',''))),fn($path)=>preg_match('#^/[a-z0-9/-]+$#D',$path)));
            $permission=(string)$this->input('permission','');
            $allowed=['manage_templates','exchange_checklists','execute_checklists','view_operations','view_reports','view_vehicles','manage_people'];
            if ($permission!==''&&!in_array($permission,$allowed,true)) throw new \RuntimeException('Permissão inválida.');
            $metadata['permission']=$permission?:null; $metadata['edited_in_central']=true;
            $data=['metadata'=>$metadata];
            foreach(['title','summary','content','status','audience','source_path','document_type','language','tags'] as $key) $data[$key]=(string)$this->input($key,$old[$key]??(['status'=>'draft','audience'=>'support','document_type'=>'guide','language'=>'pt-BR'][$key]??''));
            (new KnowledgePublisher($db))->save((int)($old['system_id']??$this->input('system_id')),$slug,$data,$old?(int)$this->input('version'):null,(int)Auth::user()['id']);
            $_SESSION['flash_success']='Documento salvo com histórico de versão. Publicações chegam aos aplicativos na próxima sincronização.';
        } catch(Throwable $e) {
            $_SESSION['flash']='Não foi possível salvar. '.($e instanceof \RuntimeException?$e->getMessage():'Revise os campos e tente novamente.');
        }
        redirect('/conhecimento');
    }
    private function allow():void {\App\Services\CentralAdminAccess::enforce();}
    private function slug(string $value):string {$ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value)?:$value;return trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/','-',$ascii)??''),'-');}
}
