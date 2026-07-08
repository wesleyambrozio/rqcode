<section class="card">
  <h2>Nova integração</h2>
  <form method="post" action="/integracoes" class="form-grid">
    <?= csrf_field() ?>
    <label>Sistema <select name="system_id"><option value="">Global</option><?php foreach ($systems as $system): ?><option value="<?= e($system['id']) ?>"><?= e($system['name']) ?></option><?php endforeach; ?></select></label>
    <label>Nome <input name="name" required></label>
    <label>Tipo <select name="type"><option>api</option><option>webhook</option><option>database</option><option>support</option><option>payment</option></select></label>
    <label>Endpoint/DSN <input name="endpoint_url"></label>
    <label>Status <select name="status"><option>pending</option><option>active</option><option>error</option><option>disabled</option></select></label>
    <div class="actions"><button type="submit">Salvar integração</button></div>
  </form>
</section>
<section class="card" style="margin-top:16px">
  <h2>Integrações cadastradas</h2>
  <div class="table-wrap"><table><tr><th>Sistema</th><th>Nome</th><th>Tipo</th><th>Endpoint</th><th>Status</th></tr>
    <?php foreach ($integrations as $integration): ?><tr><td><?= e($integration['system_name'] ?: 'Global') ?></td><td><?= e($integration['name']) ?></td><td><?= e($integration['type']) ?></td><td><?= e($integration['endpoint_url']) ?></td><td><span class="badge"><?= e($integration['status']) ?></span></td></tr><?php endforeach; ?>
  </table></div>
</section>
