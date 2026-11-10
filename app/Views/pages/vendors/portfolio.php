<?php if(!empty($_SESSION['flash'])):?><div class="alert"><?=e($_SESSION['flash']);unset($_SESSION['flash']);?></div><?php endif;?>
<?php if(!empty($_SESSION['flash_success'])):?><div class="alert alert-success"><?=e($_SESSION['flash_success']);unset($_SESSION['flash_success']);?></div><?php endif;?>
<?php $unassigned = count(array_filter($accounts, fn($a) => !$a['assignment_id'])); ?>
<?php $sources = ['signup_code' => 'Código no cadastro', 'default' => 'Padrão', 'manual' => 'Manual']; ?>
<?php $situations = ['pending' => 'A pagar', 'paid' => 'Pago', 'cancelled' => 'Cancelado']; ?>
<section class="card">
  <h2>Carteira por vendedor</h2>
  <form method="get" action="/vendedores/carteira" class="form-grid">
    <label>Sistema <select name="system_id"><option value="0">Todos</option><?php foreach ($systems as $s): ?><option value="<?= e($s['id']) ?>" <?= (int) $s['id'] === $systemId ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></label>
    <div class="actions"><button type="submit">Filtrar</button> <a href="/vendedores">Cadastro de vendedores</a></div>
  </form>
  <?php if ($unassigned): ?><div class="alert"><?= e($unassigned) ?> conta(s) sem vendedor. Cadastre e marque um vendedor padrão: a próxima sincronização atribui essas contas automaticamente.</div><?php endif; ?>
  <div class="table-wrap"><table><tr><th>Conta</th><th>Sistema</th><th>Vendedor</th><th>Comissão</th><th>Parcelas</th><th>Origem</th><th>Alterar vendedor</th></tr>
    <?php foreach ($accounts as $a): ?>
      <tr>
        <td><?= e($a['account_name']) ?> <small><?= e($a['profile']) ?></small></td>
        <td><?= e($a['system_name']) ?></td>
        <td><?= $a['assignment_id'] ? e($a['vendor_name']) : '<span class="badge danger">Sem vendedor</span>' ?></td>
        <td><?= $a['assignment_id'] ? e($a['commission_percent']).'% · '.e($types[$a['commission_type']] ?? $a['commission_type']) : '' ?></td>
        <td><?= $a['assignment_id'] ? e($a['installments_paid']).($a['commission_type'] === 'installments' ? ' de '.e($a['installments_limit']) : '') : '' ?></td>
        <td><?= e($sources[$a['source'] ?? ''] ?? '') ?></td>
        <td><details><summary>Alterar</summary>
          <form method="post" action="/vendedores/carteira" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="system_id" value="<?= e($a['system_id']) ?>"><input type="hidden" name="tenant_external_id" value="<?= e($a['external_id']) ?>">
            <label>Vendedor <select name="vendor_id"><?php foreach ($vendors as $v): ?><option value="<?= e($v['id']) ?>" <?= (int) $v['id'] === (int) $a['vendor_id'] ? 'selected' : '' ?>><?= e($v['name']) ?> (<?= e($v['commission_default_percent']) ?>%)</option><?php endforeach; ?></select></label>
            <label>Comissão (%) <input type="number" step="0.01" min="0" max="100" name="commission_percent" value="<?= e($a['commission_percent'] ?? 0) ?>" required></label>
            <label>Tipo <select name="commission_type"><?php foreach ($types as $key => $label): ?><option value="<?= e($key) ?>" <?= ($a['commission_type'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label>Parcelas <input type="number" min="1" max="600" name="installments_limit" value="<?= e($a['installments_limit']) ?>"></label>
            <label>Motivo <input name="reason" maxlength="500" required></label>
            <div class="actions"><button type="submit">Salvar</button></div>
          </form>
        </details></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
</section>
<section class="card" style="margin-top:16px">
  <h2>Totais por vendedor</h2>
  <div class="table-wrap"><table><tr><th>Vendedor</th><th>A pagar</th><th>Pago</th></tr>
    <?php foreach ($totals as $t): ?><tr><td><?= e($t['name']) ?></td><td><?= money($t['pending']) ?></td><td><?= money($t['paid']) ?></td></tr><?php endforeach; ?>
  </table></div>
  <p>A comissão incide sobre o valor bruto de cada pagamento recebido e entra no financeiro como conta a pagar na categoria Comissões. Estorno antes do pagamento ao vendedor cancela a conta; depois do pagamento, gera um valor a recuperar.</p>
</section>
<section class="card" style="margin-top:16px">
  <h2>Extrato de comissões</h2>
  <div class="table-wrap"><table><tr><th>Data</th><th>Vendedor</th><th>Conta</th><th>Parcela</th><th>Base bruta</th><th>%</th><th>Comissão</th><th>Situação</th></tr>
    <?php foreach ($statement as $c): ?>
      <tr><td><?= e(substr((string) $c['created_at'], 0, 10)) ?></td><td><?= e($c['vendor_name']) ?></td><td><?= e($c['account_name'] ?? $c['tenant_external_id']) ?></td><td><?= e($c['installment_number']) ?><?= $c['kind'] === 'reversal' ? ' (estorno)' : '' ?></td><td><?= money($c['base_amount']) ?></td><td><?= e($c['commission_percent']) ?>%</td><td><?= money($c['amount']) ?></td><td><span class="badge"><?= e($situations[$c['entry_status']] ?? $c['entry_status']) ?></span></td></tr>
    <?php endforeach; ?>
  </table></div>
</section>
