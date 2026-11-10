<?php
declare(strict_types=1);
namespace App\Services;
use PDO;
use RuntimeException;
use Throwable;

final class BillingBridge
{
    public function __construct(private PDO $db,private array $keys,private AsaasSubscriptions $billing) {}
    public function handle(string $raw,array $headers):array {
        if(strlen($raw)>16384)throw new RuntimeException('Request too large',413);
        $secret=$this->keys[$headers['system']??'']??'';$time=(string)($headers['timestamp']??'');$nonce=(string)($headers['nonce']??'');
        if(strlen($secret)<32||!ctype_digit($time)||abs(time()-(int)$time)>300||!preg_match('/^[a-zA-Z0-9-]{20,80}$/D',$nonce)||!hash_equals(hash_hmac('sha256',$time."\n".$nonce."\n".hash('sha256',$raw),$secret),(string)($headers['signature']??'')))throw new RuntimeException('Unauthorized',401);
        try{$data=json_decode($raw,true,16,JSON_THROW_ON_ERROR);}catch(Throwable){throw new RuntimeException('Invalid JSON',422);}
        if(!is_array($data)||!in_array($data['action']??'', ['billing.state','billing.subscribe','billing.refresh','billing.cancel'],true))throw new RuntimeException('Unknown action',422);
        $q=$this->db->prepare('select s.id from saas_systems s join product_admin_state a on a.system_id=s.id where s.slug=? and s.active=true and a.enabled=true');$q->execute([$headers['system']]);$system=(int)$q->fetchColumn();if(!$system)throw new RuntimeException('Unauthorized',401);
        $this->db->beginTransaction();
        try{
            $this->db->query('select id from saas_systems where id='.$system.($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'?'':' for update'))->fetch();
            $q=$this->db->prepare('select count(*) from support_api_nonces where system_id=? and nonce=?');$q->execute([$system,$nonce]);if($q->fetchColumn())throw new RuntimeException('Replay rejected',409);
            $driver=$this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $minute=match($driver){'pgsql'=>"current_timestamp - interval '60 seconds'",'sqlite'=>"datetime('now','-60 seconds')",default=>'current_timestamp - interval 60 second'};
            $day=match($driver){'pgsql'=>"current_timestamp - interval '1 day'",'sqlite'=>"datetime('now','-1 day')",default=>'current_timestamp - interval 1 day'};
            $q=$this->db->prepare('select count(*) from support_api_nonces where system_id=? and created_at>='.$minute);$q->execute([$system]);if((int)$q->fetchColumn()>=240)throw new RuntimeException('Rate limit',429);
            $this->db->prepare('insert into support_api_nonces(system_id,nonce) values(?,?)')->execute([$system,$nonce]);
            $this->db->exec('delete from support_api_nonces where created_at<'.$day);$this->db->commit();
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
        // Business reservations commit independently before external operations.
        $tenant=(string)($data['tenant_id']??'');
        return match($data['action']){
            'billing.subscribe'=>$this->billing->subscribe($system,$tenant,$data),
            'billing.cancel'=>$this->billing->cancel($system,$tenant),
            'billing.refresh'=>$this->billing->refresh($system,$tenant),
            default=>$this->billing->state($system,$tenant),
        };
    }
}
