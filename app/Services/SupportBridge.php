<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

/** Server-to-server API. Identity is supplied only by the authenticated product. */
final class SupportBridge
{
    public function __construct(private PDO $db, private array $keys) {}

    public function handle(string $raw, array $headers): array
    {
        if (strlen($raw) > 1500000) throw new RuntimeException('Request too large', 413);
        $system = (string) ($headers['system'] ?? '');
        $secret = $this->keys[$system] ?? '';
        $timestamp = (string) ($headers['timestamp'] ?? '');
        $nonce = (string) ($headers['nonce'] ?? '');
        $signature = (string) ($headers['signature'] ?? '');
        if (strlen($secret) < 32 || !ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300
            || !preg_match('/^[a-zA-Z0-9-]{20,80}$/D', $nonce)
            || !hash_equals(hash_hmac('sha256', $timestamp."\n".$nonce."\n".hash('sha256', $raw), $secret), $signature)) {
            throw new RuntimeException('Unauthorized', 401);
        }
        try { $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new RuntimeException('Invalid JSON',422); }
        if (!is_array($data)) throw new RuntimeException('Invalid request', 422);
        $q = $this->db->prepare('select id from saas_systems where slug=? and active=true');
        $q->execute([$system]);
        $systemId = (int) $q->fetchColumn();
        if (!$systemId) throw new RuntimeException('Unauthorized', 401);

        $this->db->beginTransaction();
        try {
            // Serializes replay checks and idempotent writes for this product only.
            $this->db->query('select id from saas_systems where id='.$systemId.' for update')->fetch();
            $q = $this->db->prepare('select count(*) from support_api_nonces where system_id=? and nonce=?');
            $q->execute([$systemId, $nonce]);
            if ($q->fetchColumn()) throw new RuntimeException('Replay rejected', 409);
            $driver=$this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $minute=$driver==='pgsql'?"current_timestamp - interval '60 seconds'":'current_timestamp - interval 60 second';
            $day=$driver==='pgsql'?"current_timestamp - interval '1 day'":'current_timestamp - interval 1 day';
            $q = $this->db->prepare('select count(*) from support_api_nonces where system_id=? and created_at>='.$minute);
            $q->execute([$systemId]);
            if ((int) $q->fetchColumn() >= 240) throw new RuntimeException('Rate limit', 429);
            $this->db->prepare('insert into support_api_nonces(system_id,nonce) values (?,?)')->execute([$systemId,$nonce]);
            $this->db->exec('delete from support_api_nonces where created_at<'.$day);
            $action = $data['action'] ?? '';
            if (!in_array($action, ['ticket.create','ticket.message','ticket.read','knowledge.list','health','admin.sync','admin.ack','referrals.sync'], true)) throw new RuntimeException('Unknown action', 422);
            $mutating = in_array($action, ['ticket.create','ticket.message'], true);
            $eventId = $mutating ? $this->text($data,'event_id',80) : '';
            $hash = hash('sha256', $raw);
            $result = null;
            if ($mutating) {
                $q=$this->db->prepare('select payload_hash,response_json from support_api_receipts where system_id=? and event_id=?');
                $q->execute([$systemId,$eventId]);
                if ($receipt=$q->fetch()) {
                    if (!hash_equals($receipt['payload_hash'],$hash)) throw new RuntimeException('Idempotency conflict',409);
                    $result=json_decode($receipt['response_json'],true,32,JSON_THROW_ON_ERROR);
                }
            }
            if ($result === null) {
                $result = match($action) {
                    'ticket.create' => $this->createTicket($systemId,$data),
                    'ticket.message' => $this->message($systemId,$data),
                    'ticket.read' => $this->readTicket($systemId,$data),
                    'knowledge.list' => $this->documents($systemId),
                    'admin.sync' => (new ProductAdministration($this->db))->sync($systemId,$data),
                    'admin.ack' => (new ProductAdministration($this->db))->acknowledge($systemId,$data),
                    'referrals.sync' => (new ProductReferrals($this->db))->sync($systemId,$data),
                    default => ['ok'=>true,'version'=>1],
                };
                if ($mutating) $this->db->prepare('insert into support_api_receipts(system_id,event_id,payload_hash,response_json) values (?,?,?,?)')->execute([$systemId,$eventId,$hash,json_encode($result,JSON_THROW_ON_ERROR)]);
                $this->db->prepare('insert into support_audit(system_id,action,entity_id) values (?,?,?)')->execute([$systemId,$action,$data['ticket_id']??$data['external_id']??null]);
            }
            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function createTicket(int $systemId, array $data): array
    {
        $external=$this->text($data,'external_id',120);
        $tenant=$this->text($data,'tenant_id',120);
        $user=$this->text($data,'user_id',120);
        $bridgeKey=hash('sha256',$tenant.'|'.$external);
        $q=$this->db->prepare('select id,requester_external_id from support_tickets where system_id=? and bridge_key=?');
        $q->execute([$systemId,$bridgeKey]);
        if ($existing=$q->fetch()) {
            if ($existing['requester_external_id']!==$user) throw new RuntimeException('Not found',404);
            return ['ticket_id'=>(string)$existing['id'],'protocol'=>'RQ-'.$existing['id']];
        }
        $priority=$data['priority']??'normal';
        $category=$data['category']??'how_to';
        if (!in_array($priority,['low','normal','high','urgent'],true) || !in_array($category,['how_to','incident','access','billing','feedback'],true)) throw new RuntimeException('Invalid category or priority',422);
        $q=$this->db->prepare('select id,first_response_hours,resolution_hours from support_queues where system_id=? and active=true order by id limit 1');
        $q->execute([$systemId]);$queue=$q->fetch();
        $context=[];
        foreach(['page','browser','connection','profile','role','tenant_name','release'] as $key) $context[$key]=mb_substr((string)($data['context'][$key]??''),0,250);
        $context['page']=preg_replace('/\?.*$/','',$context['page']);
        $status=$data['initial_status']??'open';
        if (!in_array($status,['open','in_progress','waiting_customer','resolved','closed'],true)) throw new RuntimeException('Invalid status',422);
        $opened=isset($data['opened_at']) ? strtotime((string)$data['opened_at']) : time();
        if (!$opened || $opened>time()+300) throw new RuntimeException('Invalid date',422);
        $q=$this->db->prepare('insert into support_tickets(system_id,external_id,bridge_key,tenant_external_id,requester_external_id,customer_name,customer_email,subject,description,priority,category,status,queue_id,context_json,opened_at,first_response_due_at,resolution_due_at,updated_at) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([$systemId,$external,$bridgeKey,$tenant,$user,$this->text($data,'name',180),$this->text($data,'email',180),$this->text($data,'subject',160),$this->text($data,'message',6000),$priority,$category,$status,$queue['id']??null,json_encode($context,JSON_THROW_ON_ERROR),date('Y-m-d H:i:s',$opened),date('Y-m-d H:i:s',time()+(int)($queue['first_response_hours']??8)*3600),date('Y-m-d H:i:s',time()+(int)($queue['resolution_hours']??48)*3600),date('Y-m-d H:i:s')]);
        $q=$this->db->prepare('select id from support_tickets where system_id=? and bridge_key=?');$q->execute([$systemId,$bridgeKey]);$id=(string)$q->fetchColumn();
        // Import legacy administrative notes as internal notes only.
        if (!empty($data['internal_notes'])) $this->db->prepare('insert into support_ticket_messages(ticket_id,author_type,author_name,body,internal_note) values (?,?,?,?,true)')->execute([$id,'admin','Histórico importado',mb_substr((string)$data['internal_notes'],0,6000)]);
        if (!empty($data['screenshot'])) {
            $bytes=base64_decode($data['screenshot']['data']??'',true);
            $info=$bytes ? @getimagesizefromstring($bytes) : false;
            if (!$info || strlen($bytes)>1048576 || $info[0]>4000 || $info[1]>4000 || !in_array($info['mime'],['image/jpeg','image/png'],true)) throw new RuntimeException('Invalid screenshot',422);
            $this->db->prepare('insert into support_ticket_attachments(ticket_id,mime,data) values (?,?,?)')->execute([$id,$info['mime'],base64_encode($bytes)]);
        }
        if (in_array($status,['resolved','closed'],true)) $this->db->prepare('update support_tickets set closed_at=current_timestamp where id=?')->execute([$id]);
        return ['ticket_id'=>$id,'protocol'=>'RQ-'.$id];
    }

    private function ticket(int $systemId,array $data): array
    {
        $q=$this->db->prepare('select * from support_tickets where id=? and system_id=? and tenant_external_id=? and requester_external_id=?');
        $q->execute([$this->text($data,'ticket_id',120),$systemId,$this->text($data,'tenant_id',120),$this->text($data,'user_id',120)]);
        return $q->fetch() ?: throw new RuntimeException('Not found',404);
    }

    private function message(int $systemId,array $data): array
    {
        $ticket=$this->ticket($systemId,$data);
        $q=$this->db->prepare('insert into support_ticket_messages(ticket_id,author_type,author_name,body,internal_note) values (?,?,?,?,false)');
        $q->execute([$ticket['id'],'customer',$this->text($data,'name',180),$this->text($data,'message',6000)]);
        $this->db->prepare("update support_tickets set status='open',closed_at=null,updated_at=current_timestamp where id=?")->execute([$ticket['id']]);
        return ['ok'=>true];
    }

    private function readTicket(int $systemId,array $data): array
    {
        $ticket=$this->ticket($systemId,$data);
        $q=$this->db->prepare('select id,author_type,author_name,body,created_at from support_ticket_messages where ticket_id=? and internal_note=false and id>? order by id limit 100');
        $q->execute([$ticket['id'],max(0,(int)($data['after_id']??0))]);
        return ['ticket_id'=>(string)$ticket['id'],'status'=>$ticket['status'],'priority'=>$ticket['priority'],'messages'=>$q->fetchAll(),'protocol'=>'RQ-'.$ticket['id']];
    }

    private function documents(int $systemId): array
    {
        $q=$this->db->prepare("select slug,title,summary,content,language,version,reviewed_at,metadata_json from knowledge_documents where system_id=? and status='published' and audience='customer' and language='pt-BR' order by slug limit 300");
        $q->execute([$systemId]);
        return ['documents'=>array_map(function($row){$row['metadata']=json_decode($row['metadata_json']??'{}',true)?:[];unset($row['metadata_json']);return $row;},$q->fetchAll())];
    }

    private function text(array $data,string $key,int $max): string
    {
        if (!is_scalar($data[$key]??null)) throw new RuntimeException('Invalid '.$key,422);
        $value=trim((string)$data[$key]);
        if ($value===''||mb_strlen($value)>$max) throw new RuntimeException('Invalid '.$key,422);
        return $value;
    }
}
