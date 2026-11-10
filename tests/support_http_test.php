<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
$root=realpath(dirname(__DIR__));$stamp=$argv[1]??'';
if(!preg_match('/^\d{14}$/D',$stamp)||$root!=='/tmp/rqcode-support-test-'.$stamp||env('DB_DATABASE')!=='rqcode_support_test_ui_'.$stamp)throw new RuntimeException('Isolated environment required.');
$db=App\Core\Database::connection();$password='Support-test-'.$stamp;
$db->prepare("insert ignore into admin_users(name,email,password_hash,role,active) values('Accountant QA','accountant@example.invalid',?,'accountant',1)")->execute([password_hash($password,PASSWORD_DEFAULT)]);
$db->prepare("insert ignore into admin_users(name,email,password_hash,role,active) values('Viewer QA','viewer@example.invalid',?,'viewer',1)")->execute([password_hash($password,PASSWORD_DEFAULT)]);
$cookie=tempnam(sys_get_temp_dir(),'rqcode-support-cookie-');$count=0;
$request=function(string $path,?array $form=null)use($cookie){$ch=curl_init('http://127.0.0.1:9181'.$path);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>8]);if($form!==null)curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($form)]);$body=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);return [$code,$body];};
$check=function(bool $ok,string $message)use(&$count){if(!$ok)throw new RuntimeException($message);$count++;echo 'PASS '.$message.PHP_EOL;};
$login=function(string $email)use($request,$password,$check){[$code,$body]=$request('/login');preg_match('/name="_csrf" value="([^"]+)"/',$body,$m);$check(isset($m[1]),'login CSRF token present');return $request('/login',['_csrf'=>$m[1],'email'=>$email,'password'=>$password]);};
try{
 foreach(['/suporte','/conhecimento','/suporte/anexo?id=1']as $path)$check($request($path)[0]===302,'guest denied '.$path);
 $check($request('/api/support/v1')[0]===405,'API rejects GET');
 $check($request('/api/support/v1',[])[0]===401,'API requires signature');
 $login('accountant@example.invalid');
 foreach(['/suporte','/conhecimento','/suporte/anexo?id=1']as $path)$check($request($path)[0]===302,'accountant redirected away from '.$path);
 file_put_contents($cookie,'');$login('viewer@example.invalid');
 foreach(['/suporte','/conhecimento','/suporte/anexo?id=1']as $path)$check($request($path)[0]===403,'viewer denied '.$path);
 // Replace only the temporary cookie file; the test account/session never touches production.
 file_put_contents($cookie,'');$login('qa@example.invalid');
 foreach(['/suporte','/conhecimento']as $path)$check($request($path)[0]===200,'owner allowed '.$path);
 $check($request('/suporte/mensagem',['ticket_id'=>'4','body'=>'CSRF must block']) [0]===419,'missing CSRF blocks browser write');
 echo "TOTAL: $count HTTP checks passed\n";
}finally{unlink($cookie);}
