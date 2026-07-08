<section class="card">
  <h2>Novo vendedor</h2>
  <form method="post" action="/vendedores" class="form-grid">
    <?= csrf_field() ?>
    <label>Nome <input name="name" required></label>
    <label>E-mail <input type="email" name="email"></label>
    <label>Telefone <input name="phone"></label>
    <label>Comissão padrão (%) <input type="number" step="0.01" name="commission_default_percent" value="0"></label>
    <label>Ativo <select name="active"><option value="1">Sim</option><option value="0">Não</option></select></label>
    <div class="actions"><button type="submit">Salvar vendedor</button></div>
  </form>
</section>
<section class="card" style="margin-top:16px">
  <h2>Vendedores cadastrados</h2>
  <div class="table-wrap"><table><tr><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Comissão</th><th>Status</th></tr>
    <?php foreach ($vendors as $vendor): ?>
      <tr><td><?= e($vendor['name']) ?></td><td><?= e($vendor['email']) ?></td><td><?= e($vendor['phone']) ?></td><td><?= e($vendor['commission_default_percent']) ?>%</td><td><span class="badge <?= $vendor['active'] ? 'success' : 'danger' ?>"><?= $vendor['active'] ? 'Ativo' : 'Inativo' ?></span></td></tr>
    <?php endforeach; ?>
  </table></div>
</section>
