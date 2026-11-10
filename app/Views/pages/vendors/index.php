<?php if(!empty($_SESSION['flash'])):?><div class="alert"><?=e($_SESSION['flash']);unset($_SESSION['flash']);?></div><?php endif;?>
<?php if(!empty($_SESSION['flash_success'])):?><div class="alert alert-success"><?=e($_SESSION['flash_success']);unset($_SESSION['flash_success']);?></div><?php endif;?>
<section class="card">
  <h2>Novo vendedor</h2>
  <p>Toda conta nova é atribuída ao vendedor do código usado no cadastro (link com <code>?vendedor=CODIGO</code>) ou, sem código, ao vendedor padrão. As condições abaixo são copiadas para cada conta no momento da atribuição.</p>
  <form method="post" action="/vendedores" class="form-grid">
    <?= csrf_field() ?>
    <label>Nome <input name="name" required></label>
    <label>Código de indicação <input name="code" maxlength="40" pattern="[A-Za-z0-9-]{1,40}" placeholder="ANA-01"></label>
    <label>E-mail <input type="email" name="email"></label>
    <label>Telefone <input name="phone"></label>
    <label>Comissão padrão (%) sobre o valor bruto <input type="number" step="0.01" min="0" max="100" name="commission_default_percent" value="0"></label>
    <label>Tipo de comissão <select name="commission_type"><?php foreach ($types as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
    <label>Parcelas comissionadas (se limitado) <input type="number" min="1" max="600" name="commission_installments"></label>
    <label>Vendedor padrão <select name="is_default"><option value="0">Não</option><option value="1">Sim</option></select></label>
    <label>Ativo <select name="active"><option value="1">Sim</option><option value="0">Não</option></select></label>
    <div class="actions"><button type="submit">Salvar vendedor</button> <a href="/vendedores/carteira">Ver carteira e comissões</a></div>
  </form>
</section>
<section class="card" style="margin-top:16px">
  <h2>Vendedores cadastrados</h2>
  <div class="table-wrap"><table><tr><th>Nome</th><th>Código</th><th>Contato</th><th>Comissão</th><th>Tipo</th><th>Contas</th><th>Status</th><th>Editar</th></tr>
    <?php foreach ($vendors as $vendor): ?>
      <tr>
        <td><?= e($vendor['name']) ?><?= $vendor['is_default'] ? ' <span class="badge">padrão</span>' : '' ?></td>
        <td><?= e($vendor['code']) ?></td>
        <td><?= e($vendor['email']) ?> <?= e($vendor['phone']) ?></td>
        <td><?= e($vendor['commission_default_percent']) ?>%</td>
        <td><?= e($types[$vendor['commission_type']] ?? $vendor['commission_type']) ?><?= $vendor['commission_type'] === 'installments' ? ' ('.e($vendor['commission_installments']).')' : '' ?></td>
        <td><?= e($vendor['accounts_count']) ?></td>
        <td><span class="badge <?= $vendor['active'] ? 'success' : 'danger' ?>"><?= $vendor['active'] ? 'Ativo' : 'Inativo' ?></span></td>
        <td><details><summary>Editar</summary>
          <form method="post" action="/vendedores" class="form-grid">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($vendor['id']) ?>">
            <label>Nome <input name="name" value="<?= e($vendor['name']) ?>" required></label>
            <label>Código <input name="code" value="<?= e($vendor['code']) ?>" maxlength="40" pattern="[A-Za-z0-9-]{1,40}"></label>
            <label>E-mail <input type="email" name="email" value="<?= e($vendor['email']) ?>"></label>
            <label>Telefone <input name="phone" value="<?= e($vendor['phone']) ?>"></label>
            <label>Comissão (%) <input type="number" step="0.01" min="0" max="100" name="commission_default_percent" value="<?= e($vendor['commission_default_percent']) ?>"></label>
            <label>Tipo <select name="commission_type"><?php foreach ($types as $key => $label): ?><option value="<?= e($key) ?>" <?= $vendor['commission_type'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label>Parcelas <input type="number" min="1" max="600" name="commission_installments" value="<?= e($vendor['commission_installments']) ?>"></label>
            <label>Padrão <select name="is_default"><option value="0">Não</option><option value="1" <?= $vendor['is_default'] ? 'selected' : '' ?>>Sim</option></select></label>
            <label>Ativo <select name="active"><option value="1">Sim</option><option value="0" <?= $vendor['active'] ? '' : 'selected' ?>>Não</option></select></label>
            <div class="actions"><button type="submit">Salvar</button></div>
          </form>
        </details></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
</section>
