<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Plan;
use App\Models\SaasSystem;

final class SystemController extends Controller
{
    public function index(): void
    {
        $systems = (new SaasSystem())->all('name asc');
        $plans = Database::connection()->query('select p.*, s.name system_name from plans p join saas_systems s on s.id = p.system_id order by s.name, p.name')->fetchAll();
        $this->view('systems/index', compact('systems', 'plans') + ['title' => 'Sistemas e Planos']);
    }

    public function store(): void
    {
        if ($this->input('entity') === 'plan') {
            (new Plan())->create([
                'system_id' => $this->input('system_id'),
                'name' => $this->input('name'),
                'billing_cycle' => $this->input('billing_cycle'),
                'price' => $this->input('price'),
                'active' => 1,
            ]);
        } else {
            (new SaasSystem())->create([
                'name' => $this->input('name'),
                'slug' => $this->input('slug'),
                'base_url' => $this->input('base_url'),
                'database_type' => $this->input('database_type'),
                'active' => 1,
            ]);
        }
        redirect('/sistemas');
    }
}
