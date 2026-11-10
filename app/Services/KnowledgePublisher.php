<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

/** Immutable snapshots and optimistic concurrency for the editorial workflow. */
final class KnowledgePublisher
{
    public function __construct(private PDO $db) {}
    public function save(int $system,string $slug,array $data,?int $expectedVersion=null,?int $admin=null):int
    {
        if (!preg_match('/^[a-z0-9-]{1,180}$/D',$slug)) throw new RuntimeException('Slug inválido.',422);
        if (!in_array($data['status']??'', ['draft','review','published','archived'],true) || !in_array($data['audience']??'', ['customer','support','admin','agent'],true)) throw new RuntimeException('Público ou estado inválido.',422);
        foreach(['title'=>180,'summary'=>2000,'content'=>60000,'source_path'=>500] as $field=>$max) {
            $data[$field]=trim((string)($data[$field]??''));
            if (mb_strlen($data[$field])>$max || (in_array($field,['title','content'],true)&&$data[$field]==='')) throw new RuntimeException('Verifique '.$field.'.',422);
        }
        if ($data['status']==='published' && $data['source_path']==='') throw new RuntimeException('Publicação exige fonte verificável.',422);
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare('select * from knowledge_documents where system_id=? and slug=? for update'); $q->execute([$system,$slug]); $old=$q->fetch();
            if ($old && $expectedVersion===null) throw new RuntimeException('Este slug já existe. Edite o documento e informe sua versão atual.',409);
            if ($old && $expectedVersion!==null && (int)$old['version']!==$expectedVersion) throw new RuntimeException('O documento mudou. Recarregue antes de editar.',409);
            if ($old) {
                $q=$this->db->prepare('select count(*) from knowledge_document_versions where document_id=? and version=?'); $q->execute([$old['id'],$old['version']]);
                if (!$q->fetchColumn()) $this->snapshot($old);
            }
            $fields=['title','summary','content','audience','status','source_path']; $row=[];
            foreach($fields as $field) $row[$field]=$data[$field];
            $row+=['document_type'=>$data['document_type']??'guide','language'=>$data['language']??'pt-BR','tags'=>$data['tags']??'',
                'metadata_json'=>json_encode($data['metadata']??[],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
                'token_estimate'=>(int)ceil(mb_strlen($data['content'])/4),'reviewed_at'=>$data['status']==='published'?date('Y-m-d H:i:s'):($old['reviewed_at']??null),
                'version'=>($old['version']??0)+1];
            if ($old) {
                $sql='update knowledge_documents set '.implode(',',array_map(fn($key)=>$key.'=?',array_keys($row))).',updated_at=current_timestamp where id=?';
                $this->db->prepare($sql)->execute([...array_values($row),$old['id']]);
            } else {
                $row+=['system_id'=>$system,'slug'=>$slug,'created_by'=>$admin];
                $this->db->prepare('insert into knowledge_documents('.implode(',',array_keys($row)).') values('.implode(',',array_fill(0,count($row),'?')).')')->execute(array_values($row));
            }
            $q=$this->db->prepare('select * from knowledge_documents where system_id=? and slug=?'); $q->execute([$system,$slug]); $current=$q->fetch();
            $this->snapshot($current);
            $this->db->prepare('insert into support_audit(system_id,admin_user_id,action,entity_id) values(?,?,?,?)')->execute([$system,$admin,'knowledge.'.$data['status'],(string)$current['id']]);
            $this->db->commit(); return (int)$current['id'];
        } catch(Throwable $e) { if($this->db->inTransaction())$this->db->rollBack(); throw $e; }
    }
    private function snapshot(array $document):void
    {
        $this->db->prepare('insert into knowledge_document_versions(document_id,version,snapshot_json) values(?,?,?)')->execute([$document['id'],$document['version'],json_encode($document,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
    }
}
