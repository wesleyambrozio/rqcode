<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Auth,Database};
final class CentralAdminAccess
{
    public static function enforce():void
    {
        $q=Database::connection()->prepare('select role from admin_users where id=? and active=true');
        $q->execute([Auth::user()['id']??0]);
        if(!in_array($q->fetchColumn(),['owner','admin'],true)){http_response_code(403);exit('Acesso restrito à administração.');}
    }
}
