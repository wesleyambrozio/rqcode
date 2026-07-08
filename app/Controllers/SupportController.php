<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\SupportTicket;

final class SupportController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $tickets = $db->query('select t.*, s.name system_name from support_tickets t left join saas_systems s on s.id = t.system_id order by t.opened_at desc')->fetchAll();
        $systems = $db->query('select id, name from saas_systems where active = 1 order by name')->fetchAll();
        $this->view('support/index', compact('tickets', 'systems') + ['title' => 'Suporte']);
    }

    public function store(): void
    {
        (new SupportTicket())->create([
            'system_id' => $this->input('system_id'),
            'external_id' => $this->input('external_id'),
            'customer_name' => $this->input('customer_name'),
            'customer_email' => $this->input('customer_email'),
            'subject' => $this->input('subject'),
            'priority' => $this->input('priority'),
            'status' => $this->input('status'),
            'opened_at' => $this->input('opened_at'),
        ]);
        redirect('/suporte');
    }
}
