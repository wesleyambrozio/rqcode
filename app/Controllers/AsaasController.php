<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Controller,Database};
use App\Services\{AsaasClient,AsaasPayments,CentralAdminAccess};
use Throwable;

final class AsaasController extends Controller
{
    private function service(): AsaasPayments { return new AsaasPayments(Database::connection(),AsaasClient::configured()); }

    public function index(): void
    {
        CentralAdminAccess::enforce();
        $environment = (string)env('ASAAS_ENV','sandbox');
        $configured = (string)env('ASAAS_API_KEY','') !== '';
        $webhookConfigured = strlen((string)env('ASAAS_WEBHOOK_TOKEN','')) >= 32;
        $webhookUrl = rtrim((string)config('app.url'),'/') . '/api/asaas/webhook';
        $charges = []; $entries = []; $subscriptions = []; $migrationReady = false;
        try {
            $q = Database::connection()->prepare('select c.*, f.description from asaas_charges c join financial_entries f on f.id=c.entry_id where c.environment=? order by c.id desc limit 100');
            $q->execute([$environment]); $charges = $q->fetchAll(); $migrationReady = true;
            $entries = Database::connection()->query("select id,description,amount,due_date from financial_entries where direction='receivable' and status='pending' order by due_date limit 200")->fetchAll();
            $q=Database::connection()->prepare('select b.*,a.name account_name from billing_subscriptions b left join product_accounts a on a.system_id=b.system_id and a.external_id=b.tenant_external_id where b.environment=? order by b.id desc limit 100');
            $q->execute([$environment]);$subscriptions=$q->fetchAll();
        } catch (Throwable) { $_SESSION['flash'] = 'Execute php bin/migrate.php para preparar as tabelas Asaas.'; }
        $this->view('finance/asaas',compact('environment','configured','webhookConfigured','webhookUrl','charges','entries','subscriptions','migrationReady') + ['title'=>'Assinaturas e recebimentos Asaas']);
    }

    public function create(): void
    {
        CentralAdminAccess::enforce();
        try {
            if ($this->input('confirm') !== '1') throw new \RuntimeException('Confirme a emissão da cobrança.');
            if (strlen((string)env('ASAAS_WEBHOOK_TOKEN','')) < 32) throw new \RuntimeException('Configure ASAAS_WEBHOOK_TOKEN antes de emitir cobranças.');
            $this->service()->create((int)$this->input('entry_id'),trim((string)$this->input('customer_id')),(string)$this->input('billing_type'),(int)Auth::user()['id']);
            $_SESSION['flash_success'] = 'Cobrança vinculada ao lançamento. Acompanhe o recebimento abaixo.';
        } catch (Throwable $e) { $_SESSION['flash'] = $e instanceof \RuntimeException && !$e instanceof \PDOException ? $e->getMessage() : 'Não foi possível emitir a cobrança. Verifique a configuração.'; }
        redirect('/financeiro/asaas');
    }

    public function reconcile(): void
    {
        CentralAdminAccess::enforce();
        try { $this->service()->reconcile((int)$this->input('id')); $_SESSION['flash_success']='Cobrança conciliada com o Asaas.'; }
        catch (Throwable) { $_SESSION['flash']='Falha na conciliação. Verifique a configuração e tente novamente.'; }
        redirect('/financeiro/asaas');
    }

    public function webhook(): void
    {
        header('Content-Type: application/json');
        $token = (string)($_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '');
        $expected = (string)env('ASAAS_WEBHOOK_TOKEN','');
        $api = AsaasClient::configured();
        $sandboxToken = (string)env('ASAAS_SANDBOX_WEBHOOK_TOKEN','');
        if (strlen($sandboxToken)>=32 && hash_equals($sandboxToken,$token)) {
            $expected=$sandboxToken;
            $api=new AsaasClient('sandbox',(string)env('ASAAS_SANDBOX_API_KEY',''));
        }
        if (strlen($expected)<32 || !hash_equals($expected,$token)) { http_response_code(401); echo '{"ok":false}'; return; }
        // Shared fixed-window limiter; no session or database work for unauthorized requests.
        $directory = dirname(__DIR__,2).'/storage';
        if (!is_dir($directory)) mkdir($directory,0700,true);
        $file = fopen($directory.'/asaas-webhook-rate.json','c+');
        if (!$file || !flock($file,LOCK_EX)) { http_response_code(503); echo '{"ok":false}'; return; }
        $bucket = json_decode(stream_get_contents($file),true) ?: [];
        $minute = (int)floor(time()/60);
        $count = ($bucket['minute'] ?? null) === $minute ? (int)$bucket['count']+1 : 1;
        ftruncate($file,0); rewind($file); fwrite($file,json_encode(['minute'=>$minute,'count'=>$count])); flock($file,LOCK_UN); fclose($file);
        if ($count>120) { http_response_code(429); echo '{"ok":false}'; return; }
        $raw = file_get_contents('php://input',false,null,0,262145);
        if ($raw === false || strlen($raw)>262144) { http_response_code(413); echo '{"ok":false}'; return; }
        try {
            $event=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
            if(!is_array($event)||!is_string($event['id']??null)||$event['id']===''||strlen($event['id'])>190||!is_string($event['event']??null)||!preg_match('/^(PAYMENT|SUBSCRIPTION)_[A-Z_]+$/D',$event['event']))throw new \RuntimeException('Evento inválido.',400);
            $db=Database::connection();
            $billing=new \App\Services\AsaasSubscriptions($db,$api,(string)env('BILLING_DOCUMENT_KEY',''));
            if(!$billing->webhook($event)&&str_starts_with($event['event'],'PAYMENT_'))(new AsaasPayments($db,$api))->webhook($raw,$token,$expected);
            echo '{"ok":true}';
        }
        catch (\JsonException) { http_response_code(400); echo '{"ok":false}'; }
        catch (Throwable $e) { http_response_code(in_array($e->getCode(),[400,401,409],true)?$e->getCode():503); echo '{"ok":false}'; }
    }
}
