<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\VendorCommissions;
use RuntimeException;
use Throwable;

final class VendorController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $vendors = $db->query('select v.*, (select count(*) from vendor_assignments a where a.vendor_id=v.id and a.ends_at is null) accounts_count from vendors v order by v.is_default desc, v.name')->fetchAll();
        $this->view('vendors/index', compact('vendors') + ['title' => 'Vendedores', 'types' => VendorCommissions::TYPES]);
    }

    public function store(): void
    {
        try {
            $db = Database::connection();
            $id = (int) $this->input('id', 0);
            $type = (string) $this->input('commission_type', 'lifetime');
            $limit = $type === 'installments' ? (int) $this->input('commission_installments', 0) : null;
            $percent = (float) $this->input('commission_default_percent', 0);
            (new VendorCommissions($db))->validateTerms($percent, $type, $limit);
            $code = trim((string) $this->input('code', ''));
            $code = $code === '' ? null : (VendorCommissions::validCode($code) ?? throw new RuntimeException('Código deve ter letras, números ou hífen (até 40).'));
            $isDefault = $this->input('is_default', '0') === '1';
            $active = $this->input('active', '0') === '1';
            if ($isDefault && !$active) throw new RuntimeException('O vendedor padrão precisa estar ativo.');
            $values = [trim((string) $this->input('name')), $this->input('email') ?: null, $this->input('phone') ?: null, $percent, $active, $code, $isDefault, $type, $limit];
            if ($values[0] === '') throw new RuntimeException('Informe o nome do vendedor.');
            $db->beginTransaction();
            // Only one default vendor receives accounts without a sign-up code.
            if ($isDefault) $db->prepare('update vendors set is_default=false where id<>?')->execute([$id]);
            if ($id) {
                $db->prepare('update vendors set name=?,email=?,phone=?,commission_default_percent=?,active=?,code=?,is_default=?,commission_type=?,commission_installments=? where id=?')->execute([...$values, $id]);
            } else {
                $db->prepare('insert into vendors(name,email,phone,commission_default_percent,active,code,is_default,commission_type,commission_installments) values(?,?,?,?,?,?,?,?,?)')->execute($values);
            }
            $db->commit();
            $_SESSION['flash_success'] = 'Vendedor salvo. As condições valem para as próximas contas; contas já atribuídas mantêm o acordo registrado.';
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            $_SESSION['flash'] = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível salvar; verifique se o código já está em uso.';
        }
        redirect('/vendedores');
    }

    public function portfolio(): void
    {
        $db = Database::connection();
        $systemId = (int) $this->input('system_id', 0);
        $where = $systemId ? ' where pa.system_id=?' : '';
        $q = $db->prepare("select pa.system_id, pa.external_id, pa.name account_name, pa.profile, s.name system_name, va.id assignment_id, va.vendor_id, va.commission_percent, va.commission_type, va.installments_limit, va.source, va.starts_at, v.name vendor_name,
            (select count(*) from commission_entries c where c.assignment_id=va.id and c.kind='commission') installments_paid
            from product_accounts pa join saas_systems s on s.id=pa.system_id
            left join vendor_assignments va on va.system_id=pa.system_id and va.tenant_external_id=pa.external_id and va.ends_at is null
            left join vendors v on v.id=va.vendor_id{$where} order by va.id is not null, pa.name limit 500");
        $q->execute($systemId ? [$systemId] : []);
        $accounts = $q->fetchAll();
        $statement = $db->query("select c.*, v.name vendor_name, pa.name account_name, f.status entry_status, f.due_date from commission_entries c join vendors v on v.id=c.vendor_id
            left join product_accounts pa on pa.system_id=c.system_id and pa.external_id=c.tenant_external_id left join financial_entries f on f.id=c.financial_entry_id order by c.id desc limit 200")->fetchAll();
        $totals = $db->query("select v.name, sum(case when f.status='pending' then c.amount else 0 end) pending, sum(case when f.status='paid' then c.amount else 0 end) paid
            from commission_entries c join vendors v on v.id=c.vendor_id left join financial_entries f on f.id=c.financial_entry_id group by v.name order by v.name")->fetchAll();
        $vendors = $db->query('select id, name, commission_default_percent, commission_type, commission_installments from vendors where active=true order by name')->fetchAll();
        $systems = $db->query('select id, name from saas_systems where active=true order by name')->fetchAll();
        $this->view('vendors/portfolio', compact('accounts', 'statement', 'totals', 'vendors', 'systems', 'systemId') + ['title' => 'Carteira e comissões', 'types' => VendorCommissions::TYPES]);
    }

    public function reassign(): void
    {
        try {
            $type = (string) $this->input('commission_type', 'lifetime');
            (new VendorCommissions(Database::connection()))->reassign(
                (int) $this->input('system_id'),
                (string) $this->input('tenant_external_id'),
                (int) $this->input('vendor_id'),
                (float) $this->input('commission_percent', 0),
                $type,
                $type === 'installments' ? (int) $this->input('installments_limit', 0) : null,
                (int) (Auth::user()['id'] ?? 0),
                (string) $this->input('reason', ''),
            );
            $_SESSION['flash_success'] = 'Vendedor da conta atualizado. Comissões já geradas permanecem com o vendedor anterior.';
        } catch (Throwable $e) {
            $_SESSION['flash'] = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível alterar o vendedor.';
        }
        redirect('/vendedores/carteira');
    }
}
