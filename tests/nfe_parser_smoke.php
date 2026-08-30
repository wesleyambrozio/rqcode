<?php
require dirname(__DIR__).'/vendor/autoload.php';env('APP_NAME');$_ENV['COMPANY_DOCUMENT']='11222333000181';
$xml=file_get_contents(__DIR__.'/fixtures/nfe-filament.xml');$service=new App\Services\NfeXmlService();$data=$service->parse($xml);$filament=$service->filamentDetails($data['items'][0]);
assert($data['access_key']==='31260812345678000123550010000001231000001238');assert(count($data['items'])===2);assert($data['items'][0]['is_filament']===true);assert($data['items'][1]['is_filament']===false);assert($filament['total_weight_g']===2000.0);assert($data['total_invoice']===209.8);echo "NFe parser OK\n";
$rejected=false;try{$service->parse('<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><foo>&xxe;</foo>');}catch(RuntimeException){$rejected=true;}assert($rejected);echo "NFe XML security OK\n";
$rejected=false;try{$service->parse(str_replace('<protNFe>','<removedProtNFe>',str_replace('</protNFe>','</removedProtNFe>',$xml)));}catch(RuntimeException){$rejected=true;}assert($rejected);echo "NFe authorization OK\n";
$rejected=false;try{$service->parse(str_replace('<CNPJ>11222333000181</CNPJ>','<CNPJ>11111111000191</CNPJ>',$xml));}catch(RuntimeException){$rejected=true;}assert($rejected);echo "NFe recipient OK\n";
$personalXml=str_replace('<CNPJ>11222333000181</CNPJ>','<CPF>11144477735</CPF>',$xml);$_ENV['COMPANY_PERSONAL_DOCUMENTS']='11144477735';$personal=$service->parse($personalXml);assert($personal['expense_origin']==='owner_personal');echo "NFe personal recipient OK\n";
