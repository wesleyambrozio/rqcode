<?php
declare(strict_types=1);
namespace App\Services;
use PDO;
use RuntimeException;

final class ProductReferrals {
    public function __construct(private PDO $db) {}
    public function sync(int $system,array $data):array {
        if(!(new ProductAdministration($this->db))->enabled($system))throw new RuntimeException('Product disabled',403);
        if(($data['version']??null)!==1)throw new RuntimeException('Invalid version',422);
        $summary=$this->counts($data['summary']??[]);
        $rows=$data['referrers']??null;$segments=$data['segments']??null;
        if(!is_array($rows)||count($rows)>100||!is_array($segments)||count($segments)>100)throw new RuntimeException('Invalid batch',422);
        $cleanSegments=[];
        foreach($segments as $s){
            if(!is_array($s)||!in_array($s['profile']??'', ['driver','workshop','fleet'],true))throw new RuntimeException('Invalid profile',422);
            $cleanSegments[]=['profile'=>$s['profile'],'city'=>$this->text($s['city']??'',100,true),'state'=>$this->text($s['state']??'',2,true)]+$this->counts($s);
        }
        foreach($rows as $row){
            if(!is_array($row))throw new RuntimeException('Invalid row',422);
            $id=$this->text($row['external_id']??null,80);$code=$this->text($row['code']??null,24);$name=$this->text($row['name']??null,180);
            if(!preg_match('/^[A-Z0-9]{12}$/D',$code))throw new RuntimeException('Invalid code',422);
            $c=$this->counts($row);
            $q=$this->db->prepare('select code from product_referrers where system_id=? and external_id=?');$q->execute([$system,$id]);$old=$q->fetchColumn();
            if($old!==false && $old!==$code)throw new RuntimeException('Referral code changed',409);
            if($old===false){$this->db->prepare('insert into product_referrers(system_id,external_id,code,name,registered,confirmed,activated) values(?,?,?,?,?,?,?)')->execute([$system,$id,$code,$name,$c['registered'],$c['confirmed'],$c['activated']]);}
            else{$this->db->prepare('update product_referrers set name=?,registered=?,confirmed=?,activated=?,last_seen_at=current_timestamp where system_id=? and external_id=?')->execute([$name,$c['registered'],$c['confirmed'],$c['activated'],$system,$id]);}
        }
        $q=$this->db->prepare('select system_id from product_referral_state where system_id=?');$q->execute([$system]);
        $values=[json_encode($summary,JSON_THROW_ON_ERROR),json_encode($cleanSegments,JSON_THROW_ON_ERROR),$system];
        if($q->fetchColumn()!==false)$this->db->prepare('update product_referral_state set summary_json=?,segments_json=?,last_sync_at=current_timestamp where system_id=?')->execute($values);
        else $this->db->prepare('insert into product_referral_state(summary_json,segments_json,system_id) values(?,?,?)')->execute($values);
        return ['ok'=>true];
    }
    private function counts(array $row):array {
        $result=[];
        foreach(['registered','confirmed','activated'] as $key){$v=$row[$key]??null;if(!(is_int($v)||(is_string($v)&&ctype_digit($v)))||(int)$v<0||(int)$v>1000000000)throw new RuntimeException('Invalid counters',422);$result[$key]=(int)$v;}
        if($result['confirmed']>$result['registered']||$result['activated']>$result['registered'])throw new RuntimeException('Inconsistent counters',422);
        return $result;
    }
    private function text(mixed $value,int $max,bool $empty=false):string {
        if(!is_string($value)||mb_strlen($value)>$max||(!$empty&&trim($value)===''))throw new RuntimeException('Invalid text',422);
        return trim($value);
    }
}
