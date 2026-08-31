<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\SupportTicket;
use Throwable;

final class SupportController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $systemFilter=(int)$this->input('system_id');$queueFilter=(int)$this->input('queue_id');$statusFilter=trim((string)$this->input('status'));
        $where=[];$params=[];if($systemFilter>0){$where[]='t.system_id=:system';$params['system']=$systemFilter;}if($queueFilter>0){$where[]='t.queue_id=:queue';$params['queue']=$queueFilter;}if($statusFilter!==''){$where[]='t.status=:status';$params['status']=$statusFilter;}
        $ticketSql="select t.*, s.name system_name, a.name assigned_name, q.name queue_name,
            (select count(*) from support_ticket_messages m where m.ticket_id = t.id) message_count
            from support_tickets t left join saas_systems s on s.id = t.system_id
            left join admin_users a on a.id = t.assigned_admin_user_id
            left join support_queues q on q.id=t.queue_id".($where?' where '.implode(' and ',$where):'')." order by case t.status when 'open' then 1 when 'in_progress' then 2 when 'waiting_customer' then 3 else 4 end, t.opened_at desc";
        $statement=$db->prepare($ticketSql);$statement->execute($params);$tickets=$statement->fetchAll();
        $systems = $db->query('select id, name from saas_systems where active = 1 order by name')->fetchAll();
        $admins = $db->query('select id, name from admin_users where active = 1 order by name')->fetchAll();
        $queues=$db->query('select q.*,s.name system_name,(select count(*) from support_tickets t where t.queue_id=q.id and t.status not in (\'resolved\',\'closed\')) open_tickets from support_queues q join saas_systems s on s.id=q.system_id order by s.name,q.name')->fetchAll();
        $aiConfigs=$db->query('select c.*,s.name system_name,q.name fallback_queue_name from ai_chat_configs c join saas_systems s on s.id=c.system_id left join support_queues q on q.id=c.fallback_queue_id order by s.name')->fetchAll();
        $systemOverview=$db->query("select s.id,s.name,
            (select count(*) from support_tickets t where t.system_id=s.id and t.status not in ('resolved','closed')) open_tickets,
            (select count(*) from support_queues q where q.system_id=s.id and q.active=1) active_queues,
            (select count(*) from knowledge_documents d where d.system_id=s.id and d.status='published') published_documents,
            (select coalesce(sum(d.token_estimate),0) from knowledge_documents d where d.system_id=s.id and d.status='published') rag_tokens,
            (select concat(c.provider,' / ',coalesce(c.model,'nao definido')) from ai_chat_configs c where c.system_id=s.id and c.active=1) active_ai
            from saas_systems s where s.active=1 order by s.name")->fetchAll();
        $messages = $db->query('select * from support_ticket_messages order by created_at asc')->fetchAll();
        $messagesByTicket = [];
        foreach ($messages as $message) $messagesByTicket[$message['ticket_id']][] = $message;
        $this->view('support/index', compact('tickets','systems','admins','queues','aiConfigs','systemOverview','messagesByTicket','systemFilter','queueFilter','statusFilter') + ['title' => 'Suporte e RAG']);
    }

    public function store(): void
    {
        $systemId=(int)$this->input('system_id');$queueId=(int)$this->input('queue_id');if($queueId>0&&!$this->validQueue($queueId,$systemId))$queueId=0;
        (new SupportTicket())->create([
            'system_id' => $systemId,
            'queue_id' => $queueId ?: null,
            'external_id' => $this->input('external_id'),
            'customer_name' => $this->input('customer_name'),
            'customer_email' => $this->input('customer_email'),
            'subject' => $this->input('subject'),
            'description' => $this->input('description'),
            'category' => $this->input('category'),
            'priority' => $this->input('priority'),
            'status' => $this->input('status'),
            'opened_at' => $this->input('opened_at'),
            'first_response_due_at' => $this->slaDueAt((string) $this->input('priority')),
        ]);
        redirect('/suporte');
    }

    public function update(): void
    {
        $status = (string) $this->input('status');$ticketId=(int)$this->input('id');$ticket=Database::connection()->prepare('select system_id from support_tickets where id=:id');$ticket->execute(['id'=>$ticketId]);$systemId=(int)$ticket->fetchColumn();$queueId=(int)$this->input('queue_id');if($queueId>0&&!$this->validQueue($queueId,$systemId))$queueId=0;
        $statement = Database::connection()->prepare('update support_tickets set status = :status, priority = :priority, queue_id=:queue, assigned_admin_user_id = :assigned, closed_at = :closed_at, updated_at = current_timestamp where id = :id');
        $statement->execute(['status' => $status, 'priority' => $this->input('priority'), 'queue'=>$queueId?:null,'assigned' => $this->input('assigned_admin_user_id') ?: null, 'closed_at' => $status === 'closed' ? date('Y-m-d H:i:s') : null, 'id' => $ticketId]);
        redirect('/suporte');
    }

    public function message(): void
    {
        $body = trim((string) $this->input('body'));
        if ($body !== '') {
            $statement = Database::connection()->prepare('insert into support_ticket_messages (ticket_id, author_type, author_name, body, internal_note) values (:ticket_id, :author_type, :author_name, :body, :internal_note)');
            $statement->execute(['ticket_id' => (int) $this->input('ticket_id'), 'author_type' => 'admin', 'author_name' => \App\Core\Auth::user()['name'] ?? 'RQCode', 'body' => $body, 'internal_note' => $this->input('internal_note') ? 1 : 0]);
        }
        redirect('/suporte');
    }

    public function queue():void
    {
        $this->allowManager();$systemId=(int)$this->input('system_id');$name=mb_substr(trim((string)$this->input('name')),0,120);$slug=$this->slug($name);$priority=in_array($this->input('default_priority'),['low','normal','high','urgent'],true)?$this->input('default_priority'):'normal';
        if($systemId<=0||$name===''||$slug===''){$_SESSION['flash']='Informe sistema e nome valido para a fila.';redirect('/suporte');}
        try{$statement=Database::connection()->prepare('insert into support_queues(system_id,name,slug,category,default_priority,first_response_hours,resolution_hours) values(:system,:name,:slug,:category,:priority,:first_response,:resolution)');$statement->execute(['system'=>$systemId,'name'=>$name,'slug'=>$slug,'category'=>mb_substr(trim((string)$this->input('category')),0,60)?:null,'priority'=>$priority,'first_response'=>max(1,min(720,(int)$this->input('first_response_hours',8))),'resolution'=>max(1,min(2160,(int)$this->input('resolution_hours',48)))]);$_SESSION['flash_success']='Fila criada.';}catch(Throwable){$_SESSION['flash']='Nao foi possivel criar a fila. Verifique se ela ja existe.';}redirect('/suporte');
    }

    public function aiConfig():void
    {
        $this->allowManager();$systemId=(int)$this->input('system_id');$providers=['disabled','openai','anthropic','google','azure_openai','local'];$provider=in_array($this->input('provider'),$providers,true)?$this->input('provider'):'disabled';$active=$this->input('active')==='1'&&$provider!=='disabled'?1:0;$model=mb_substr(trim((string)$this->input('model')),0,100)?:null;$envKey=preg_match('/^[A-Z][A-Z0-9_]{2,99}$/',(string)$this->input('credential_env_key'))?(string)$this->input('credential_env_key'):null;
        $fallbackQueue=(int)$this->input('fallback_queue_id');if($fallbackQueue>0&&!$this->validQueue($fallbackQueue,$systemId))$fallbackQueue=0;
        if($systemId<=0){$_SESSION['flash']='Selecione o sistema da configuracao de IA.';redirect('/suporte');}
        if($active&&($model===null||($provider!=='local'&&($envKey===null||(string)env($envKey,'')==='')))){$_SESSION['flash']='Para ativar a IA, informe modelo e uma variavel de credencial configurada no servidor.';redirect('/suporte');}
        $data=['system'=>$systemId,'provider'=>$provider,'model'=>$model,'env_key'=>$envKey,'temperature'=>max(0,min(2,(float)$this->input('temperature',0.2))),'tokens'=>max(100,min(16000,(int)$this->input('max_output_tokens',800))),'confidence'=>max(0,min(1,(float)$this->input('min_confidence',0.7))),'rag'=>$this->input('rag_enabled')==='1'?1:0,'queue'=>$fallbackQueue?:null,'prompt'=>mb_substr(trim((string)$this->input('system_prompt')),0,10000)?:null,'active'=>$active,'user'=>Auth::user()['id']];
        $db=Database::connection();$driver=$db->getAttribute(\PDO::ATTR_DRIVER_NAME);$sql=$driver==='pgsql'?'insert into ai_chat_configs(system_id,provider,model,credential_env_key,temperature,max_output_tokens,min_confidence,rag_enabled,fallback_queue_id,system_prompt,active,updated_by,updated_at) values(:system,:provider,:model,:env_key,:temperature,:tokens,:confidence,:rag,:queue,:prompt,:active,:user,current_timestamp) on conflict(system_id) do update set provider=excluded.provider,model=excluded.model,credential_env_key=excluded.credential_env_key,temperature=excluded.temperature,max_output_tokens=excluded.max_output_tokens,min_confidence=excluded.min_confidence,rag_enabled=excluded.rag_enabled,fallback_queue_id=excluded.fallback_queue_id,system_prompt=excluded.system_prompt,active=excluded.active,updated_by=excluded.updated_by,updated_at=current_timestamp':'insert into ai_chat_configs(system_id,provider,model,credential_env_key,temperature,max_output_tokens,min_confidence,rag_enabled,fallback_queue_id,system_prompt,active,updated_by,updated_at) values(:system,:provider,:model,:env_key,:temperature,:tokens,:confidence,:rag,:queue,:prompt,:active,:user,current_timestamp) on duplicate key update provider=values(provider),model=values(model),credential_env_key=values(credential_env_key),temperature=values(temperature),max_output_tokens=values(max_output_tokens),min_confidence=values(min_confidence),rag_enabled=values(rag_enabled),fallback_queue_id=values(fallback_queue_id),system_prompt=values(system_prompt),active=values(active),updated_by=values(updated_by),updated_at=current_timestamp';$db->prepare($sql)->execute($data);$_SESSION['flash_success']='Configuracao de IA atualizada sem armazenar a chave secreta.';redirect('/suporte');
    }

    private function slaDueAt(string $priority): string
    {
        $hours = ['low' => 24, 'normal' => 8, 'high' => 4, 'urgent' => 1][$priority] ?? 8;
        return date('Y-m-d H:i:s', time() + ($hours * 3600));
    }
    private function slug(string $value):string{$ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value)?:$value;return trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/','-',$ascii)??''),'-');}
    private function validQueue(int $queueId,int $systemId):bool{$statement=Database::connection()->prepare('select count(*) from support_queues where id=:queue and system_id=:system and active=1');$statement->execute(['queue'=>$queueId,'system'=>$systemId]);return(bool)$statement->fetchColumn();}
    private function allowManager():void{if(!in_array(Auth::user()['role']??'',['owner','admin'],true)){http_response_code(403);exit('Acesso restrito.');}}
}
