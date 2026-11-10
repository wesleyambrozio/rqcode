<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;
use App\Services\{AsaasClient,AsaasSubscriptions,BillingBridge};
use Throwable;
final class BillingApiController
{
    public function dispatch():void {
        header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
        try{
            $keys=json_decode((string)env('SUPPORT_PRODUCT_KEYS','{}'),true,16,JSON_THROW_ON_ERROR);
            $db=Database::connection();$service=new AsaasSubscriptions($db,AsaasClient::configured(),(string)env('BILLING_DOCUMENT_KEY',''));
            $r=(new BillingBridge($db,$keys,$service))->handle((string)file_get_contents('php://input',false,null,0,16385),[
                'system'=>$_SERVER['HTTP_X_SUPPORT_SYSTEM']??'','timestamp'=>$_SERVER['HTTP_X_SUPPORT_TIMESTAMP']??'',
                'nonce'=>$_SERVER['HTTP_X_SUPPORT_NONCE']??'','signature'=>$_SERVER['HTTP_X_SUPPORT_SIGNATURE']??'',
            ]);echo json_encode($r,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        }catch(Throwable $e){$status=in_array($e->getCode(),[401,403,404,409,413,422,429],true)?$e->getCode():503;http_response_code($status);echo json_encode(['error'=>$status===503?'Cobrança temporariamente indisponível. Consulte a assinatura antes de tentar novamente.':$e->getMessage()]);}
    }
}
