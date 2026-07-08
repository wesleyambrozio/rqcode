<section class="grid cols-2">
  <div class="card">
    <h2>Novo sistema</h2>
    <form method="post" action="/sistemas" class="grid">
      <?= csrf_field() ?>
      <input type="hidden" name="entity" value="system">
      <label>Nome <input name="name" required></label>
      <label>Slug <input name="slug" required></label>
      <label>URL base <input type="url" name="base_url"></label>
      <label>Banco <select name="database_type"><option>mariadb</option><option>supabase</option><option>mysql</option><option>postgres</option></select></label>
      <button type="submit">Salvar sistema</button>
    </form>
  </div>
  <div class="card">
    <h2>Novo plano</h2>
    <form method="post" action="/sistemas" class="grid">
      <?= csrf_field() ?>
      <input type="hidden" name="entity" value="plan">
      <label>Sistema <select name="system_id" required><?php foreach ($systems as $system): ?><option value="<?= e($system['id']) ?>"><?= e($system['name']) ?></option><?php endforeach; ?></select></label>
      <label>Plano <input name="name" required></label>
      <label>Ciclo <select name="billing_cycle"><option>monthly</option><option>quarterly</option><option>yearly</option><option>one_time</option></select></label>
      <label>Preço <input type="number" step="0.01" name="price" required></label>
      <button type="submit">Salvar plano</button>
    </form>
  </div>
</section>
<section class="card" style="margin-top:16px">
  <h2>Sistemas</h2>
  <div class="table-wrap"><table><tr><th>Nome</th><th>Slug</th><th>URL</th><th>Banco</th></tr>
    <?php foreach ($systems as $system): ?><tr><td><?= e($system['name']) ?></td><td><?= e($system['slug']) ?></td><td><?= e($system['base_url']) ?></td><td><?= e($system['database_type']) ?></td></tr><?php endforeach; ?>
  </table></div>
</section>
<section class="card" style="margin-top:16px">
  <h2>Planos</h2>
  <div class="table-wrap"><table><tr><th>Sistema</th><th>Plano</th><th>Ciclo</th><th>Preço</th></tr>
    <?php foreach ($plans as $plan): ?><tr><td><?= e($plan['system_name']) ?></td><td><?= e($plan['name']) ?></td><td><?= e($plan['billing_cycle']) ?></td><td><?= money($plan['price']) ?></td></tr><?php endforeach; ?>
  </table></div>
</section>
