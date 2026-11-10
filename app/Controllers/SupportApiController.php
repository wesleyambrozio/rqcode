<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Services\SupportBridge;
use Throwable;

final class SupportApiController
{
    public function dispatch(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        try {
            $keys=json_decode((string)env('SUPPORT_PRODUCT_KEYS','{}'),true,16,JSON_THROW_ON_ERROR);
            $result=(new SupportBridge(Database::connection(),$keys))->handle((string)file_get_contents('php://input'),[
                'system'=>$_SERVER['HTTP_X_SUPPORT_SYSTEM']??'',
                'timestamp'=>$_SERVER['HTTP_X_SUPPORT_TIMESTAMP']??'',
                'nonce'=>$_SERVER['HTTP_X_SUPPORT_NONCE']??'',
                'signature'=>$_SERVER['HTTP_X_SUPPORT_SIGNATURE']??'',
            ]);
            echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $status=in_array($e->getCode(),[401,403,404,409,413,422,429],true)?$e->getCode():503;
            http_response_code($status);
            // Request bodies, signatures and customer data must never enter logs.
            error_log('support_api status='.$status.' exception='.get_class($e));
            echo json_encode(['error'=>$status===503?'Support temporarily unavailable':$e->getMessage()]);
        }
    }
}
