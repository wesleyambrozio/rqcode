<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use Throwable;

final class AccountingController extends Controller
{
    public function index(): void
    {
        $this->allowAccounting();
        $db = Database::connection();
        $documents = $db->query("select d.*, u.name uploaded_by_name,
            (select count(*) from accounting_document_events e where e.document_id=d.id and e.event_type='download') downloads,
            (select max(e.created_at) from accounting_document_events e where e.document_id=d.id and e.event_type='download') last_access_at
            from accounting_documents d left join admin_users u on u.id=d.uploaded_by order by d.reference_month desc,d.created_at desc")->fetchAll();
        $messages = $db->query("select m.*,u.name sender_name from accounting_messages m join admin_users u on u.id=m.sender_user_id order by m.created_at desc limit 20")->fetchAll();
        $accountants = Auth::user()['role'] === 'accountant' ? [] : $db->query("select id,name,email,active,created_at from admin_users where role='accountant' order by name")->fetchAll();
        $summary = [
            'documents' => count($documents),
            'unread' => count(array_filter($documents, fn($d) => !$d['last_access_at'])),
            'open_messages' => count(array_filter($messages, fn($m) => $m['status'] === 'open')),
            'month' => date('Y-m'),
        ];
        $allowedViews=['dashboard','documentos','mensagens','acessos','relatorios'];
        $section=in_array((string)$this->input('view'),$allowedViews,true)?(string)$this->input('view'):'dashboard';
        if (Auth::user()['role'] === 'accountant' && $section === 'acessos') $section='dashboard';
        $this->view('accounting/index', compact('documents','messages','accountants','summary','section') + ['title'=>'Contabilidade']);
    }

    public function upload(): void
    {
        $this->allowAccounting();
        $file = $_FILES['document'] ?? null;
        $allowed = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];
        $mimeType = $file && ($file['error']??UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name']) ? (string)(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
        if (!$file || ($file['error']??UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !isset($allowed[$mimeType]) || ($file['size']??0) <= 0 || $file['size'] > 20 * 1024 * 1024) {
            $_SESSION['flash'] = 'Envie PDF, JPG ou PNG com no maximo 20 MB.';
            redirect('/contabilidade?view=documentos');
        }
        $contents=file_get_contents($file['tmp_name']);
        if($contents===false||!$this->safeDocumentContents($mimeType,$contents,$file['tmp_name'])){$_SESSION['flash']='O arquivo nao passou pela validacao de seguranca.';redirect('/contabilidade?view=documentos');}
        $categories=['invoice','bank_slip','payment_receipt','bank_statement','contract','tax','payroll','report','other'];
        $category=in_array((string)$this->input('category'),$categories,true)?(string)$this->input('category'):'other';
        $referenceMonth=preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',(string)$this->input('reference_month'))?(string)$this->input('reference_month'):null;
        $originalName=mb_substr(preg_replace('/[\x00-\x1F\x7F]+/u','',basename((string)$file['name'])),0,180);
        $directory = dirname(__DIR__, 2) . '/storage/accounting';
        if (!is_dir($directory) && !mkdir($directory,0770,true) && !is_dir($directory)){$_SESSION['flash']='Armazenamento indisponivel.';redirect('/contabilidade?view=documentos');}
        $storageName = bin2hex(random_bytes(24)) . '.' . $allowed[$mimeType];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $storageName)) {
            $_SESSION['flash'] = 'Nao foi possivel armazenar o documento.';
            redirect('/contabilidade?view=documentos');
        }
        @chmod($directory.'/'.$storageName,0640);
        $db=Database::connection();
        try{$db->beginTransaction();$statement = $db->prepare('insert into accounting_documents(title,category,reference_month,original_name,storage_name,mime_type,file_size,checksum_sha256,notes,uploaded_by,uploaded_by_role,file_status) values(:title,:category,:reference_month,:original_name,:storage_name,:mime_type,:file_size,:checksum,:notes,:uploaded_by,:role,\'available\')');
        $statement->execute(['title'=>mb_substr(trim((string)$this->input('title')) ?: pathinfo($originalName,PATHINFO_FILENAME),0,180),'category'=>$category,'reference_month'=>$referenceMonth,'original_name'=>$originalName?:'documento.'.$allowed[$mimeType],'storage_name'=>$storageName,'mime_type'=>$mimeType,'file_size'=>$file['size'],'checksum'=>hash('sha256',$contents),'notes'=>mb_substr(trim((string)$this->input('notes')),0,2000)?:null,'uploaded_by'=>Auth::user()['id'],'role'=>Auth::user()['role']]);
        $documentId=(int)$db->lastInsertId();$event=$db->prepare('insert into accounting_document_events(document_id,admin_user_id,event_type,ip_address,user_agent) values(:document,:user,\'upload\',:ip,:agent)');$event->execute(['document'=>$documentId,'user'=>Auth::user()['id'],'ip'=>$_SERVER['REMOTE_ADDR']??null,'agent'=>substr($_SERVER['HTTP_USER_AGENT']??'',0,500)]);$db->commit();}catch(Throwable){if($db->inTransaction())$db->rollBack();@unlink($directory.'/'.$storageName);$_SESSION['flash']='Nao foi possivel registrar o documento.';redirect('/contabilidade?view=documentos');}
        $_SESSION['flash_success'] = Auth::user()['role']==='accountant'?'Documento enviado com seguranca para a empresa.':'Documento disponibilizado ao contador.';
        redirect('/contabilidade?view=documentos');
    }

    public function download(): void
    {
        $this->allowAccounting();
        $statement = Database::connection()->prepare('select * from accounting_documents where id=:id');
        $statement->execute(['id'=>(int)$this->input('id')]);
        $document = $statement->fetch();
        $path = $document ? dirname(__DIR__, 2) . '/storage/accounting/' . $document['storage_name'] : '';
        if (!$document || !is_file($path) || is_link($path)) { http_response_code(404); exit('Documento nao encontrado.'); }
        if(!empty($document['checksum_sha256'])&&!hash_equals((string)$document['checksum_sha256'],hash_file('sha256',$path))){http_response_code(409);exit('A verificacao de integridade do documento falhou.');}
        $event = Database::connection()->prepare('insert into accounting_document_events(document_id,admin_user_id,event_type,ip_address,user_agent) values(:document_id,:user_id,:event_type,:ip,:agent)');
        $event->execute(['document_id'=>$document['id'],'user_id'=>Auth::user()['id'],'event_type'=>'download','ip'=>$_SERVER['REMOTE_ADDR'] ?? null,'agent'=>substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,500)]);
        header('Content-Type: ' . $document['mime_type']);
        header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store, max-age=0');header('Content-Security-Policy: sandbox');
        header('Content-Length: ' . filesize($path));
        $downloadName=preg_replace('/[^A-Za-z0-9._-]/','_',basename((string)$document['original_name']))?:'documento';
        header("Content-Disposition: attachment; filename=\"{$downloadName}\"; filename*=UTF-8''".rawurlencode((string)$document['original_name']));
        readfile($path); exit;
    }

    public function message(): void
    {
        $this->allowAccounting();
        $subject = trim((string)$this->input('subject')); $message = trim((string)$this->input('message'));
        if ($subject !== '' && $message !== '') {
            $statement=Database::connection()->prepare('insert into accounting_messages(sender_user_id,subject,message,due_date) values(:user,:subject,:message,:due_date)');
            $statement->execute(['user'=>Auth::user()['id'],'subject'=>$subject,'message'=>$message,'due_date'=>$this->input('due_date') ?: null]);
            $_SESSION['flash_success']='Mensagem registrada.';
        }
        redirect('/contabilidade?view=mensagens');
    }

    public function createAccountant(): void
    {
        $this->allowAdmin();
        $name=trim((string)$this->input('name')); $email=trim((string)$this->input('email')); $password=(string)$this->input('password');
        if ($name === '' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<10) { $_SESSION['flash']='Informe nome, e-mail e senha com 10 caracteres.'; redirect('/contabilidade?view=acessos'); }
        try {
            $statement=Database::connection()->prepare("insert into admin_users(name,email,password_hash,role,active) values(:name,:email,:password,'accountant',1)");
            $statement->execute(['name'=>$name,'email'=>$email,'password'=>password_hash($password,PASSWORD_DEFAULT)]);
            $_SESSION['flash_success']='Acesso exclusivo do contador criado.';
        } catch (Throwable) { $_SESSION['flash']='E-mail ja cadastrado.'; }
        redirect('/contabilidade?view=acessos');
    }

    public function report(): void
    {
        $this->allowAccounting();
        $month = preg_match('/^\d{4}-\d{2}$/',(string)$this->input('month')) ? $this->input('month') : date('Y-m');
        $db=Database::connection();
        $monthExpression = env('DB_CONNECTION', 'mysql') === 'pgsql' ? "to_char(due_date,'YYYY-MM')" : "date_format(due_date,'%Y-%m')";
        $statement=$db->prepare("select direction,status,count(*) quantity,coalesce(sum(amount),0) total from financial_entries where {$monthExpression}=:month group by direction,status order by direction,status");
        $statement->execute(['month'=>$month]);
        header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="resumo-contabil-'.$month.'.csv"');
        $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,['Competencia','Tipo','Status','Quantidade','Total'],';');
        foreach($statement->fetchAll() as $row) fputcsv($out,[$month,$row['direction'],$row['status'],$row['quantity'],number_format((float)$row['total'],2,',','.')],';');
        fclose($out); exit;
    }

    private function allowAccounting(): void { if (!in_array(Auth::user()['role'] ?? '',['owner','admin','accountant'],true)) { http_response_code(403); exit('Acesso negado.'); } }
    private function safeDocumentContents(string $mime,string $contents,string $path):bool{if($mime==='application/pdf')return str_starts_with($contents,'%PDF-')&&!preg_match('/\/(JavaScript|JS|Launch|EmbeddedFile|OpenAction)\b/i',$contents);$image=@getimagesize($path);return is_array($image)&&($image['mime']??'')===$mime;}
    private function allowAdmin(): void { if (!in_array(Auth::user()['role'] ?? '',['owner','admin'],true)) { http_response_code(403); exit('Acesso restrito.'); } }
}
