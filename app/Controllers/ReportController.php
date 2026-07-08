<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class ReportController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $mrr = $db->query("select coalesce(sum(amount),0) from sales where recurring = 1 and status = 'active'")->fetchColumn();
        $commissions = $db->query("select coalesce(sum(commission_amount),0) from sales where status in ('active','paid')")->fetchColumn();
        $vendors = $db->query('select v.name, count(sa.id) sales_count, coalesce(sum(sa.amount),0) total from vendors v left join sales sa on sa.vendor_id = v.id group by v.id, v.name order by total desc')->fetchAll();
        $this->view('reports/index', compact('mrr', 'commissions', 'vendors') + ['title' => 'Relatórios']);
    }
}
