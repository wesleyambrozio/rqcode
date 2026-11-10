<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
$stamp=$argv[1]??'';$root=realpath(dirname(__DIR__));
if(!preg_match('/^\d{14}$/D',$stamp)||$root!=='/tmp/rqcode-support-test-'.$stamp||env('DB_DATABASE')!=='rqcode_support_test_ui_'.$stamp)throw new RuntimeException('Isolated environment required.');
$db=App\Core\Database::connection();$password='Support-test-'.$stamp;
foreach(['viewer','accountant']as $role)$db->prepare('insert ignore into admin_users(name,email,password_hash,role,active) values(?,?,?,?,1)')->execute(['QA '.$role,$role.'@example.invalid',password_hash($password,PASSWORD_DEFAULT),$role]);
$cookie=tempnam('/tmp','rqcode-admin-http-');$count=0;
$check=function(bool $ok,string $label)use(&$count){if(!$ok)throw new RuntimeException($label);$count++;echo 'PASS '.$label.PHP_EOL;};
$request=function(string $path,?array $form=null)use($cookie){$ch=curl_init('http://127.0.0.1:9181'.$path);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>8]);if($form!==null)curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($form)]);$body=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);return [$code,$body];};
$token=function(string $body):string{preg_match('/name="_csrf" value="([^"]+)"/',$body,$m);return $m[1]??throw new RuntimeException('CSRF token absent');};
$login=function(string $email)use($request,$password,$token){return $request('/login',['_csrf'=>$token($request('/login')[1]),'email'=>$email,'password'=>$password]);};
try{
 $check($request('/administracao-produtos')[0]===302,'guest denied administration');
 $login('accountant@example.invalid');$check($request('/administracao-produtos')[0]===302,'accountant excluded');
 file_put_contents($cookie,'');$login('viewer@example.invalid');$check($request('/administracao-produtos')[0]===403,'viewer cannot read administration');
 $csrf=$token($request('/dashboard')[1]);
 foreach(['/administracao-produtos/plano','/administracao-produtos/conta','/sistemas']as $path)$check($request($path,['_csrf'=>$csrf,'entity'=>'plan'])[0]===403,'viewer cannot write '.$path);
 file_put_contents($cookie,'');$login('qa@example.invalid');[$code,$body]=$request('/administracao-produtos?product=checklist');$check($code===200&&str_contains($body,'Administração central dos produtos'),'owner can manage integrated product');$csrf=$token($body);
 $check($request('/administracao-produtos?product=unknown')[0]===404,'unknown product is not silently substituted');
 $check($request('/administracao-produtos/plano',['name'=>'No CSRF'])[0]===419,'CSRF protects plan writes');
 $system=(int)$db->query("select id from saas_systems where slug='checklist'")->fetchColumn();$before=(int)$db->query('select count(*) from plans')->fetchColumn();
 $slug='qa-http-'.bin2hex(random_bytes(3));
 $form=['_csrf'=>$csrf,'system_id'=>$system,'name'=>'QA HTTP <script>','slug'=>$slug,'price_monthly'=>'0','active'=>'1','sort_order'=>'0','features'=>['vehicles'=>'1'],'limits'=>['vehicles'=>'5'],'reason'=>'Teste HTTP isolado'];
 $request('/administracao-produtos/plano',$form);$check((int)$db->query('select count(*) from plans')->fetchColumn()===$before,'confirmation is mandatory');
 $check(str_contains($request('/administracao-produtos')[1],'Confirme o impacto'),'validation error is visible');
 $form['confirm']='1';$request('/administracao-produtos/plano',$form);
 $check((int)$db->query('select count(*) from plans')->fetchColumn()===$before+1,'confirmed owner can create synchronized plan');
 [$code,$body]=$request('/administracao-produtos');$check(str_contains($body,'QA HTTP &lt;script&gt;')&&!str_contains($body,'QA HTTP <script>'),'plan name safely escaped');
 $request('/sistemas',['_csrf'=>$csrf,'entity'=>'plan','system_id'=>$system,'name'=>'Unlinked','price'=>'1','billing_cycle'=>'monthly']);
 $check((int)$db->query('select count(*) from plans')->fetchColumn()===$before+1,'legacy form cannot create disconnected plan');
 $p=$db->query('select * from plans where slug='.$db->quote($slug))->fetch();$form['external_id']=$p['external_id'];$form['revision']='0';$form['name']='Stale name';
 $request('/administracao-produtos/plano',$form);$check($db->query('select name from plans where slug='.$db->quote($slug))->fetchColumn()==='QA HTTP <script>','stale browser form cannot overwrite plan');
 $check(str_contains($request('/administracao-produtos')[1],'O plano mudou'),'concurrency error is visible');
 // Revoking the test session's persisted role must take effect without requiring logout.
 $db->exec("update admin_users set role='viewer' where email='qa@example.invalid'");
 $check($request('/administracao-produtos')[0]===403,'revoked owner session immediately denied');
 foreach(['/suporte','/conhecimento']as $path)$check($request($path)[0]===403,'revoked session denied '.$path);
 $check($request('/sistemas',['_csrf'=>$csrf,'entity'=>'plan'])[0]===403,'revoked session cannot create legacy plan');
 $db->exec("update admin_users set role='owner' where email='qa@example.invalid'");
 echo "TOTAL: $count administration HTTP checks passed\n";
}finally{$db->exec("update admin_users set role='owner' where email='qa@example.invalid'");unlink($cookie);}
