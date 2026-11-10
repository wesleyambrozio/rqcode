<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $today = date('Y-m-d');
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $integrated=$db->query('select s.name,a.system_id,a.summary_json,a.last_sync_at from product_admin_state a join saas_systems s on s.id=a.system_id where a.enabled=true and a.last_sync_at is not null')->fetchAll();
        $excluded=implode(',',array_map(fn($row)=>(int)$row['system_id'],$integrated))?:'0';
        $metrics = [
            'accounts' => $this->scalar('select coalesce(sum(accounts_total),0) from metric_snapshots where snapshot_date = :date and system_id not in ('.$excluded.')', ['date' => $today]),
            'new_accounts' => $this->scalar('select coalesce(sum(new_accounts),0) from metric_snapshots where snapshot_date = :date and system_id not in ('.$excluded.')', ['date' => $today]),
            'active_users' => $this->scalar('select coalesce(sum(active_users),0) from metric_snapshots where snapshot_date = :date and system_id not in ('.$excluded.')', ['date' => $today]),
            'online_users' => $this->scalar('select coalesce(sum(online_users),0) from metric_snapshots where snapshot_date = :date and system_id not in ('.$excluded.')', ['date' => $today]),
            'pending_payments' => $this->scalar("select coalesce(sum(amount),0) from financial_entries where direction = 'receivable' and status = 'pending'"),
            'paid_payments' => $this->scalar("select coalesce(sum(amount),0) from financial_entries where direction = 'receivable' and status = 'paid' and paid_at >= :date", ['date' => $thirtyDaysAgo]),
            'open_tickets' => $this->scalar("select count(*) from support_tickets where status in ('open','in_progress')"),
        ];
        foreach($integrated as $source){
            $summary=json_decode($source['summary_json']??'{}',true)?:[];
            $metrics['accounts']+=(int)($summary['accounts']??0);
            $metrics['new_accounts']+=str_starts_with($source['last_sync_at'],$today)?(int)($summary['new_accounts_today']??0):0;
            $metrics['active_users']+=(int)($summary['active_users']??0);
        }
        // Checklist does not collect live presence; zero would falsely mean nobody is online.
        if($integrated)$metrics['online_users']=null;
        $tickets = $db->query('select st.*, ss.name system_name from support_tickets st left join saas_systems ss on ss.id = st.system_id order by st.opened_at desc limit 8')->fetchAll();
        $entries = $db->query("select * from financial_entries where status = 'pending' order by due_date asc limit 8")->fetchAll();

        $this->view('dashboard/index', compact('metrics', 'tickets', 'entries','integrated') + ['title' => 'Dashboard']);
    }

    private function scalar(string $sql, array $params = []): mixed
    {
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn();
    }
}
