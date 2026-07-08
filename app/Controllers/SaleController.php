<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Sale;

final class SaleController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $sales = $db->query('select sa.*, v.name vendor_name, s.name system_name, p.name plan_name from sales sa left join vendors v on v.id = sa.vendor_id left join saas_systems s on s.id = sa.system_id left join plans p on p.id = sa.plan_id order by sa.sold_at desc')->fetchAll();
        $vendors = $db->query('select id, name from vendors where active = 1 order by name')->fetchAll();
        $systems = $db->query('select id, name from saas_systems where active = 1 order by name')->fetchAll();
        $plans = $db->query('select id, system_id, name from plans where active = 1 order by name')->fetchAll();
        $this->view('sales/index', compact('sales', 'vendors', 'systems', 'plans') + ['title' => 'Vendas e Comissões']);
    }

    public function store(): void
    {
        (new Sale())->create([
            'vendor_id' => $this->input('vendor_id'),
            'system_id' => $this->input('system_id'),
            'plan_id' => $this->input('plan_id') ?: null,
            'customer_name' => $this->input('customer_name'),
            'customer_email' => $this->input('customer_email'),
            'amount' => $this->input('amount'),
            'commission_percent' => $this->input('commission_percent'),
            'commission_amount' => ((float) $this->input('amount') * (float) $this->input('commission_percent')) / 100,
            'recurring' => (int) ($this->input('recurring', 0) === '1'),
            'status' => $this->input('status'),
            'sold_at' => $this->input('sold_at'),
        ]);
        redirect('/vendas');
    }
}
