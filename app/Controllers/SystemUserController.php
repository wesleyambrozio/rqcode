<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\SystemUser;
use App\Services\SystemUserDirectory;

final class SystemUserController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $systemFilter = max(0, (int) ($_GET['system_id'] ?? 0));
        [$sort, $direction] = SystemUserDirectory::selection($_GET['sort'] ?? null, $_GET['direction'] ?? null);
        $users = SystemUserDirectory::users($db, $systemFilter, $sort, $direction);
        $sorts = SystemUserDirectory::SORTS;
        $lastSync = null;
        foreach ($users as $user) if (($user['synced_at'] ?? '') > ($lastSync ?? '')) $lastSync = $user['synced_at'];
        $systems = $db->query('select id, name from saas_systems where active = 1 order by name')->fetchAll();
        $this->view('system-users/index', compact('users', 'systems', 'systemFilter', 'sort', 'direction', 'sorts', 'lastSync') + ['title' => 'Usuários dos sistemas']);
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
