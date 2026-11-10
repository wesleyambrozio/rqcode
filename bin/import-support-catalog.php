<?php
declare(strict_types=1);
use App\Core\Database;
use App\Services\KnowledgePublisher;
require dirname(__DIR__).'/vendor/autoload.php';
if (PHP_SAPI!=='cli') exit(1);
$root=realpath($argv[1]??'');
if (!$root || !is_file($root.'/catalog.json')) throw new RuntimeException('Informe a pasta docs/help do Checklist.');
$catalog=json_decode(file_get_contents($root.'/catalog.json'),true,32,JSON_THROW_ON_ERROR);
$db=Database::connection(); $q=$db->prepare('select id from saas_systems where slug=?'); $q->execute(['checklist']); $system=(int)$q->fetchColumn();
if (!$system) throw new RuntimeException('Cadastre primeiro o sistema checklist.');
$publisher=new KnowledgePublisher($db);
foreach($catalog['documents']??$catalog as $document) {
    $file=realpath($root.'/'.$document['file']);
    if (!$file || !str_starts_with($file,$root.DIRECTORY_SEPARATOR)) throw new RuntimeException('Fonte fora do catálogo.');
    $content=trim(file_get_contents($file));
    $metadata=array_intersect_key($document,array_flip(['paths','profiles','permission','reviewed_at']));
    $metadata['managed_by']='checklist-catalog'; $metadata['source_hash']=hash('sha256',$content.json_encode($metadata,JSON_UNESCAPED_UNICODE));
    $q=$db->prepare('select * from knowledge_documents where system_id=? and slug=?');$q->execute([$system,$document['slug']]);$old=$q->fetch();
    $prior=json_decode($old['metadata_json']??'{}',true)?:[];
    if ($old && ($prior['source_hash']??'')===$metadata['source_hash']) { echo 'Sem alteração: '.$document['slug'].PHP_EOL; continue; }
    if ($old && !empty($prior['edited_in_central'])) { echo 'Revisão manual preservada: '.$document['slug'].PHP_EOL; continue; }
    $publisher->save($system,$document['slug'],[
        'title'=>$document['title'],'summary'=>$document['summary'],'content'=>$content,'audience'=>'customer','status'=>$document['status'],
        'source_path'=>'ZVTech Checklist/docs/help/'.$document['file'],'metadata'=>$metadata,
    ],$old?(int)$old['version']:null);
    echo 'Publicado: '.$document['slug'].PHP_EOL;
}
// Only superseded legacy Checklist customer docs are archived. Other products/internal docs stay intact.
$q=$db->prepare("select * from knowledge_documents where system_id=? and audience='customer' and source_path like 'projetos/checklist/kb/%' and status<>'archived'"); $q->execute([$system]);
foreach($q->fetchAll() as $old) $publisher->save($system,$old['slug'],array_merge($old,['status'=>'archived','metadata'=>json_decode($old['metadata_json']??'{}',true)?:[]]),(int)$old['version']);
