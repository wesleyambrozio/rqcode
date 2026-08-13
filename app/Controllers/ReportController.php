<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use DateTimeImmutable;
use PDO;

final class ReportController extends Controller
{
    public function index(): void
    {
        [$filters,$where,$params]=$this->filters();$db=Database::connection();
        $entries=$this->entries($where,$params);
        $totals=['receivable'=>0.0,'payable'=>0.0,'received'=>0.0,'paid'=>0.0,'overdue'=>0.0];
        foreach($entries as $entry){
            $amount=(float)$entry['amount'];
            if($entry['direction']==='receivable'){$totals['receivable']+=$amount;if($entry['status']==='paid')$totals['received']+=$amount;}
            if($entry['direction']==='payable'){$totals['payable']+=$amount;if($entry['status']==='paid')$totals['paid']+=$amount;}
            if($entry['status']==='pending'&&$entry['due_date']<date('Y-m-d'))$totals['overdue']+=$amount;
        }
        $totals['result']=$totals['received']-$totals['paid'];
        $byAccount=[];
        foreach($entries as $entry){$key=($entry['account_code']?:'S/C').' · '.($entry['account_name']?:$entry['category']?:'Sem conta');$byAccount[$key]??=['name'=>$key,'direction'=>$entry['direction'],'amount'=>0.0,'count'=>0];$byAccount[$key]['amount']+=(float)$entry['amount'];$byAccount[$key]['count']++;}
        usort($byAccount,fn($a,$b)=>$b['amount']<=>$a['amount']);
        $monthly=[];foreach($entries as $entry){$month=substr($entry['due_date'],0,7);$monthly[$month]??=['month'=>$month,'receivable'=>0.0,'payable'=>0.0];$monthly[$month][$entry['direction']]+=(float)$entry['amount'];}ksort($monthly);
        $mrr=$db->query("select coalesce(sum(amount),0) from sales where recurring=1 and status='active'")->fetchColumn();
        $commissions=$db->query("select coalesce(sum(commission_amount),0) from sales where status in ('active','paid')")->fetchColumn();
        $accounts=$db->query('select id,code,name from chart_of_accounts where active=1 order by code')->fetchAll();
        $this->view('reports/index',compact('filters','entries','totals','byAccount','monthly','mrr','commissions','accounts')+['title'=>'Relatórios']);
    }

    public function export(): void
    {
        [$filters,$where,$params]=$this->filters();$entries=$this->entries($where,$params);
        $filename='rqcode-financeiro-'.$filters['from'].'-a-'.$filters['to'].'.csv';
        header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="'.$filename.'"');
        $output=fopen('php://output','wb');fwrite($output,"\xEF\xBB\xBF");
        fputcsv($output,['Vencimento','Liquidação','Descrição','Código','Plano de contas','Natureza','Forma','Valor','Status','Parcela'],';');
        foreach($entries as $entry)fputcsv($output,[$entry['due_date'],$entry['paid_at'],$entry['description'],$entry['account_code'],$entry['account_name']?:$entry['category'],$entry['direction']==='receivable'?'Receber':'Pagar',$entry['payment_method_name']?:$entry['payment_method'],number_format((float)$entry['amount'],2,',',''),$entry['status'],$entry['total_installments']?$entry['installment_number'].'/'.$entry['total_installments']:''],';');
        fclose($output);exit;
    }

    private function filters(): array
    {
        $first=date('Y-m-01');$last=date('Y-m-t');$from=$this->validDate((string)$this->input('from'))?(string)$this->input('from'):$first;$to=$this->validDate((string)$this->input('to'))?(string)$this->input('to'):$last;
        if($from>$to)[$from,$to]=[$to,$from];$direction=in_array($this->input('direction'),['receivable','payable'],true)?$this->input('direction'):'';$status=in_array($this->input('status'),['pending','paid','cancelled'],true)?$this->input('status'):'';$accountId=(int)$this->input('account_id');
        $where=['fe.due_date between :from and :to'];$params=['from'=>$from,'to'=>$to];if($direction){$where[]='fe.direction=:direction';$params['direction']=$direction;}if($status){$where[]='fe.status=:status';$params['status']=$status;}if($accountId>0){$where[]='fe.account_id=:account_id';$params['account_id']=$accountId;}
        return [['from'=>$from,'to'=>$to,'direction'=>$direction,'status'=>$status,'account_id'=>$accountId],implode(' and ',$where),$params];
    }
    private function entries(string $where,array $params): array{$statement=Database::connection()->prepare("select fe.*,coa.code account_code,coa.name account_name,pm.name payment_method_name from financial_entries fe left join chart_of_accounts coa on coa.id=fe.account_id left join payment_methods pm on pm.id=fe.payment_method_id where {$where} order by fe.due_date,fe.id");$statement->execute($params);return $statement->fetchAll(PDO::FETCH_ASSOC);}
    private function validDate(string $date): bool{$parsed=DateTimeImmutable::createFromFormat('Y-m-d',$date);return $parsed&&$parsed->format('Y-m-d')===$date;}
}
