<?php

require dirname(__DIR__).'/vendor/autoload.php';

use App\Controllers\AccountingController;

$controller=new AccountingController();
$method=new ReflectionMethod($controller,'safeDocumentContents');
$temporary=tempnam(sys_get_temp_dir(),'rqcode-accounting-');

assert($method->invoke($controller,'application/pdf',"%PDF-1.7\n1 0 obj\n<<>>\nendobj",$temporary)===true);
assert($method->invoke($controller,'application/pdf',"%PDF-1.7\n/OpenAction 2 0 R",$temporary)===false);

$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
file_put_contents($temporary,$png);
assert($method->invoke($controller,'image/png',$png,$temporary)===true);
assert($method->invoke($controller,'image/jpeg',$png,$temporary)===false);

unlink($temporary);
echo "Accounting upload security OK\n";
