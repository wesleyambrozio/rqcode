<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\SystemUser;

final class SystemUserController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $users = $db->query('select u.*, s.name system_name from system_users u join saas_systems s on s.id = u.system_id order by u.updated_at desc, u.created_at desc')->fetchAll();
        $systems = $db->query('select id, name from saas_systems where active = 1 order by name')->fetchAll();
        $this->view('system-users/index', compact('users', 'systems') + ['title' => 'Usuários dos sistemas']);
    }

    public function store(): void
    {
        (new SystemUser())->create([
            'system_id' => (int) $this->input('system_id'), 'external_id' => trim((string) $this->input('external_id')) ?: null,
            'tenant_external_id' => trim((string) $this->input('tenant_external_id')) ?: null, 'tenant_name' => trim((string) $this->input('tenant_name')),
            'name' => trim((string) $this->input('name')), 'email' => trim((string) $this->input('email')),
            'role' => trim((string) $this->input('role')), 'status' => $this->input('status', 'active'),
            'synced_at' => date('Y-m-d H:i:s'),
        ]);
        redirect('/usuarios-sistemas');
    }

    public function update(): void
    {
        $statement = Database::connection()->prepare('update system_users set status = :status, updated_at = current_timestamp where id = :id');
        $statement->execute(['status' => $this->input('status', 'active'), 'id' => (int) $this->input('id')]);
        redirect('/usuarios-sistemas');
    }
}
