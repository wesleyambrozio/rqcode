<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Integration;

final class IntegrationController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $integrations = $db->query('select i.*, s.name system_name from integrations i left join saas_systems s on s.id = i.system_id order by i.name')->fetchAll();
        $systems = $db->query('select id, name from saas_systems where active = 1 order by name')->fetchAll();
        $this->view('integrations/index', compact('integrations', 'systems') + ['title' => 'Integrações']);
    }

    public function store(): void
    {
        (new Integration())->create([
            'system_id' => $this->input('system_id') ?: null,
            'name' => $this->input('name'),
            'type' => $this->input('type'),
            'endpoint_url' => $this->input('endpoint_url'),
            'status' => $this->input('status'),
        ]);
        redirect('/integracoes');
    }
}
