<section class="card">
  <h2>Registrar venda</h2>
  <form method="post" action="/vendas" class="form-grid">
    <?= csrf_field() ?>
    <label>Vendedor <select name="vendor_id" required><?php foreach ($vendors as $vendor): ?><option value="<?= e($vendor['id']) ?>"><?= e($vendor['name']) ?></option><?php endforeach; ?></select></label>
    <label>Sistema <select name="system_id" required><?php foreach ($systems as $system): ?><option value="<?= e($system['id']) ?>"><?= e($system['name']) ?></option><?php endforeach; ?></select></label>
    <label>Plano <select name="plan_id"><option value="">Sem plano</option><?php foreach ($plans as $plan): ?><option value="<?= e($plan['id']) ?>"><?= e($plan['name']) ?></option><?php endforeach; ?></select></label>
    <label>Cliente <input name="customer_name" required></label>
    <label>E-mail do cliente <input type="email" name="customer_email"></label>
    <label>Valor <input type="number" step="0.01" name="amount" required></label>
    <label>Comissão (%) <input type="number" step="0.01" name="commission_percent" value="0"></label>
    <label>Recorrente <select name="recurring"><option value="1">Sim</option><option value="0">Não</option></select></label>
    <label>Status <select name="status"><option>active</option><option>pending</option><option>paid</option><option>cancelled</option></select></label>
    <label>Data da venda <input type="date" name="sold_at" value="<?= date('Y-m-d') ?>"></label>
    <div class="actions"><button type="submit">Registrar venda</button></div>
  </form>
</section>
<section class="card" style="margin-top:16px">
  <h2>Vendas</h2>
  <div class="table-wrap"><table><tr><th>Data</th><th>Cliente</th><th>Sistema</th><th>Vendedor</th><th>Valor</th><th>Comissão</th><th>Recorrente</th><th>Status</th></tr>
    <?php foreach ($sales as $sale): ?>
      <tr><td><?= e($sale['sold_at']) ?></td><td><?= e($sale['customer_name']) ?></td><td><?= e($sale['system_name']) ?></td><td><?= e($sale['vendor_name']) ?></td><td><?= money($sale['amount']) ?></td><td><?= money($sale['commission_amount']) ?></td><td><?= $sale['recurring'] ? 'Sim' : 'Não' ?></td><td><span class="badge"><?= e($sale['status']) ?></span></td></tr>
    <?php endforeach; ?>
  </table></div>
</section>
