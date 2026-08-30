<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;

final class NfeXmlService
{
    public function parse(string $contents): array
    {
        if ($contents === '' || strlen($contents) > 10 * 1024 * 1024) throw new RuntimeException('XML vazio ou maior que 10 MB.');
        if (stripos($contents, '<!DOCTYPE') !== false || stripos($contents, '<!ENTITY') !== false) throw new RuntimeException('XML com declaracao externa nao permitida.');

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOCDATA);
        libxml_use_internal_errors($previous);
        if (!$xml) throw new RuntimeException('XML invalido. Envie o arquivo XML autorizado da NF-e.');

        $nodes = $xml->xpath('//*[local-name()="infNFe"]');
        $inf = $nodes[0] ?? null;
        if (!$inf) throw new RuntimeException('O arquivo nao contem uma NF-e valida.');

        $ide = $this->first($inf, './*[local-name()="ide"]');
        $issuer = $this->first($inf, './*[local-name()="emit"]');
        $total = $this->first($inf, './*[local-name()="total"]/*[local-name()="ICMSTot"]');
        $key = preg_replace('/\D/', '', (string)($inf['Id'] ?? ''));
        if (str_starts_with($key, 'NFe')) $key = substr($key, 3);
        if (strlen($key) !== 44) {
            $protocol = $xml->xpath('//*[local-name()="protNFe"]/*[local-name()="infProt"]/*[local-name()="chNFe"]');
            $key = preg_replace('/\D/', '', (string)($protocol[0] ?? ''));
        }
        if (!$this->validAccessKey($key)) throw new RuntimeException('Chave de acesso da NF-e ausente ou invalida.');
        $protocolKeyNodes=$xml->xpath('//*[local-name()="protNFe"]/*[local-name()="infProt"]/*[local-name()="chNFe"]');
        $statusNodes=$xml->xpath('//*[local-name()="protNFe"]/*[local-name()="infProt"]/*[local-name()="cStat"]');
        if(!$protocolKeyNodes||!$statusNodes)throw new RuntimeException('Envie o XML processado da NF-e, com protocolo de autorizacao.');
        if(trim((string)$statusNodes[0])!=='100')throw new RuntimeException('A NF-e nao consta como autorizada (cStat '.trim((string)$statusNodes[0]).').');
        if(preg_replace('/\D/','',(string)$protocolKeyNodes[0])!==$key)throw new RuntimeException('A chave do protocolo nao corresponde a chave da NF-e.');
        if($this->value($ide,'mod')!=='55')throw new RuntimeException('Somente NF-e modelo 55 pode ser importada.');
        if($this->value($ide,'tpNF')!==''&&$this->value($ide,'tpNF')!=='1')throw new RuntimeException('O XML nao representa uma nota de venda/saida do fornecedor.');

        $recipient=$this->first($inf, './*[local-name()="dest"]');
        $recipientDocument=preg_replace('/\D/','',$this->value($recipient,'CNPJ') ?: $this->value($recipient,'CPF'));
        $companyDocument=preg_replace('/\D/','',(string)env('COMPANY_DOCUMENT',''));
        if($companyDocument!==''&&$recipientDocument!==$companyDocument)throw new RuntimeException('A NF-e nao foi emitida para a empresa configurada.');

        $items = [];
        foreach ($inf->xpath('./*[local-name()="det"]') ?: [] as $det) {
            $product = $this->first($det, './*[local-name()="prod"]');
            if (!$product) continue;
            $description = trim($this->value($product, 'xProd'));
            $items[] = [
                'number'=>(int)($det['nItem'] ?? count($items)+1), 'code'=>$this->value($product,'cProd'),
                'ean'=>$this->value($product,'cEAN'), 'description'=>$description, 'ncm'=>$this->value($product,'NCM'),
                'cfop'=>$this->value($product,'CFOP'), 'unit'=>$this->value($product,'uCom'),
                'quantity'=>(float)$this->value($product,'qCom'), 'unit_price'=>(float)$this->value($product,'vUnCom'),
                'total_price'=>(float)$this->value($product,'vProd'), 'is_filament'=>$this->isFilament($description),
            ];
        }
        if (!$items) throw new RuntimeException('A NF-e nao possui itens de produto.');

        $issued = $this->value($ide, 'dhEmi') ?: $this->value($ide, 'dEmi');$issuedTimestamp=strtotime($issued);
        if($issued===''||$issuedTimestamp===false)throw new RuntimeException('Data de emissao da NF-e ausente ou invalida.');
        $issuerDocument=preg_replace('/\D/','',$this->value($issuer,'CNPJ') ?: $this->value($issuer,'CPF'));
        if($this->value($issuer,'xNome')===''||!in_array(strlen($issuerDocument),[11,14],true))throw new RuntimeException('Emitente da NF-e sem nome ou CPF/CNPJ valido.');
        return [
            'access_key'=>$key, 'model'=>$this->value($ide,'mod'), 'series'=>$this->value($ide,'serie'),
            'number'=>$this->value($ide,'nNF'), 'issued_at'=>date('Y-m-d H:i:s',$issuedTimestamp),
            'recipient_document'=>$recipientDocument,
            'issuer_name'=>$this->value($issuer,'xNome'),
            'issuer_document'=>$issuerDocument,
            'total_products'=>(float)$this->value($total,'vProd'), 'total_freight'=>(float)$this->value($total,'vFrete'),
            'total_discount'=>(float)$this->value($total,'vDesc'), 'total_taxes'=>(float)$this->value($total,'vTotTrib'),
            'total_invoice'=>(float)$this->value($total,'vNF'), 'items'=>$items,
        ];
    }

    public function filamentDetails(array $item): array
    {
        $text=mb_strtoupper($item['description'],'UTF-8');
        $material='Outro'; foreach(['PETG','PLA','ABS','TPU','ASA','NYLON','RESINA'] as $candidate) if(preg_match('/\b'.preg_quote($candidate,'/').'\b/u',$text)){$material=in_array($candidate,['NYLON','RESINA'],true)?ucfirst(strtolower($candidate)):$candidate;break;}
        $color=null;foreach(['PRETO','BRANCO','AZUL','VERDE','VERMELHO','AMARELO','LARANJA','ROSA','ROXO','CINZA','MARROM','DOURADO','PRATA','TRANSPARENTE','NATURAL'] as $candidate)if(preg_match('/\b'.$candidate.'\b/u',$text)){$color=ucfirst(strtolower($candidate));break;}
        $unitWeight=1000.0;
        if(preg_match('/(\d+(?:[.,]\d+)?)\s*KG\b/u',$text,$m)) $unitWeight=(float)str_replace(',','.',$m[1])*1000;
        elseif(preg_match('/(\d+(?:[.,]\d+)?)\s*(?:G|GR)\b/u',$text,$m)) $unitWeight=(float)str_replace(',','.',$m[1]);
        $quantity=max(1,(float)$item['quantity']);
        return ['material'=>$material,'color'=>$color,'unit_weight_g'=>$unitWeight,'total_weight_g'=>$unitWeight*$quantity,'spool_quantity'=>$quantity];
    }

    private function isFilament(string $description): bool { return (bool)preg_match('/\b(FILAMENTO|PLA|PETG|ABS|TPU|ASA|NYLON)\b/iu',$description); }
    private function validAccessKey(string $key): bool { if(!preg_match('/^\d{44}$/',$key))return false;$sum=0;$weight=2;for($i=42;$i>=0;$i--){$sum+=(int)$key[$i]*$weight;$weight=$weight===9?2:$weight+1;}$digit=11-($sum%11);if($digit>=10)$digit=0;return (int)$key[43]===$digit; }
    private function first(?SimpleXMLElement $node,string $path): ?SimpleXMLElement { if(!$node)return null;$found=$node->xpath($path);return $found[0]??null; }
    private function value(?SimpleXMLElement $node,string $name): string { if(!$node)return '';$found=$node->xpath('./*[local-name()="'.$name.'"]');return trim((string)($found[0]??'')); }
}
