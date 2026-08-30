<?php
$directionLabels = ['receivable' => 'Receber', 'payable' => 'Pagar'];
$statusLabels = ['pending' => 'Pendente', 'paid' => 'Liquidado', 'cancelled' => 'Cancelado'];
$frequencyLabels = ['weekly' => 'Semanal', 'monthly' => 'Mensal', 'quarterly' => 'Trimestral', 'yearly' => 'Anual'];
?>

<?php if (!empty($_SESSION['flash'])): ?><div class="alert"><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div><?php endif; ?>
<?php if (!empty($_SESSION['flash_success'])): ?><div class="alert alert-success"><?= e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div><?php endif; ?>

<section class="grid cols-4 metric-grid">
  <article class="card metric metric-blue"><span class="metric-icon">↙</span><div><span class="muted">A receber</span><strong><?= money($summary['receivable']) ?></strong><small>Saldo previsto</small></div></article>
  <article class="card metric metric-amber"><span class="metric-icon">↗</span><div><span class="muted">A pagar</span><strong><?= money($summary['payable']) ?></strong><small>Compromissos</small></div></article>
  <article class="card metric metric-green"><span class="metric-icon">✓</span><div><span class="muted">Liquidado</span><strong><?= money($summary['paid']) ?></strong><small>Pago e recebido</small></div></article>
  <article class="card metric metric-red"><span class="metric-icon">!</span><div><span class="muted">Vencidos</span><strong><?= money($summary['overdue']) ?></strong><small>Requer atenção</small></div></article>
</section>

<section class="card finance-entry-card">
  <div class="card-heading"><div><span class="section-kicker">MOVIMENTAÇÃO</span><h2>Novo lançamento</h2></div><span class="heading-icon">＋</span></div>
  <form method="post" action="/financeiro" class="form-grid finance-form">
    <?= csrf_field() ?>
    <label class="span-2">Descrição <input name="description" required placeholder="Ex.: VPS principal da RQCODE"></label>
    <label>Plano de contas
      <select name="account_id" required>
        <option value="">Selecione receber ou pagar...</option>
        <?php foreach (['receivable' => 'CONTAS A RECEBER', 'payable' => 'CONTAS A PAGAR'] as $direction => $label): ?>
          <optgroup label="<?= $label ?>">
            <?php foreach ($accounts as $account): if ($account['direction'] !== $direction) continue; ?>
              <option value="<?= (int)$account['id'] ?>"><?= e($account['code'].' · '.$account['name']) ?></option>
            <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Forma de pagamento
      <select name="payment_method_id"><option value="">Não definida</option><?php foreach ($paymentMethods as $method): ?><option value="<?= (int)$method['id'] ?>"><?= e($method['name']) ?></option><?php endforeach; ?></select>
    </label>
    <label>Valor <input type="number" min="0.01" step="0.01" name="amount" required></label>
    <label>Primeiro vencimento <input type="date" name="due_date" value="<?= date('Y-m-d') ?>" required></label>
    <label class="non-recurring-field">Status
      <select name="status"><option value="pending">Pendente</option><option value="paid">Liquidado</option><option value="cancelled">Cancelado</option></select>
    </label>
    <label class="checkbox-label"><input type="checkbox" name="recurring" value="1" data-recurring-toggle> Lançamento recorrente</label>
    <div class="recurring-fields" data-recurring-fields hidden>
      <label>Frequência <select name="frequency"><option value="monthly">Mensal</option><option value="weekly">Semanal</option><option value="quarterly">Trimestral</option><option value="yearly">Anual</option></select></label>
      <label>Quantidade de parcelas <input type="number" name="installments" min="2" max="120" value="12"></label>
    </div>
    <label class="span-3">Observações <textarea name="notes" placeholder="Contrato, centro de custo ou informação adicional..."></textarea></label>
    <div class="actions span-3"><button type="submit">Salvar lançamento</button></div>
  </form>
</section>

<section class="grid cols-2 finance-config-grid">
  <div class="card">
    <div class="card-heading compact"><div><span class="section-kicker">CONFIGURAÇÃO</span><h2>Plano de contas</h2></div></div>
    <form method="post" action="/financeiro/plano-contas" class="stack-form">
      <?= csrf_field() ?>
      <div class="form-grid"><label>Código <input name="code" required placeholder="2.12"></label><label>Natureza <select name="direction"><option value="payable">Pagar</option><option value="receivable">Receber</option></select></label><label>Grupo <input name="group_name" required placeholder="Administrativo"></label></div>
      <label>Nome da conta <input name="name" required placeholder="Descrição da conta"></label>
      <button type="submit">Cadastrar conta</button>
    </form>
    <div class="catalog-list"><?php foreach ($accounts as $account): ?><span><b><?= e($account['code']) ?></b> <?= e($account['name']) ?><em><?= e($directionLabels[$account['direction']] ?? $account['direction']) ?></em></span><?php endforeach; ?></div>
  </div>
  <div class="card">
    <div class="card-heading compact"><div><span class="section-kicker">CONFIGURAÇÃO</span><h2>Formas de pagamento</h2></div></div>
    <form method="post" action="/financeiro/formas-pagamento" class="stack-form">
      <?= csrf_field() ?>
      <div class="form-grid cols-form-2"><label>Nome <input name="name" required placeholder="Ex.: Carteira digital"></label><label>Tipo <select name="method_type"><option value="instant">Instantâneo</option><option value="bank_slip">Boleto</option><option value="credit_card">Cartão de crédito</option><option value="debit_card">Cartão de débito</option><option value="bank_transfer">Transferência</option><option value="direct_debit">Débito automático</option><option value="cash">Dinheiro</option><option value="gateway">Gateway</option><option value="other">Outro</option></select></label></div>
      <button type="submit">Cadastrar forma</button>
    </form>
    <div class="payment-chips"><?php foreach ($paymentMethods as $method): ?><span><?= e($method['name']) ?></span><?php endforeach; ?></div>
  </div>
</section>

<?php if ($recurringRules): ?>
<section class="card finance-table-card">
  <div class="card-heading"><div><span class="section-kicker">AUTOMAÇÃO</span><h2>Recorrências cadastradas</h2></div><span class="heading-icon">↻</span></div>
  <div class="table-wrap"><table><tr><th>Descrição</th><th>Conta</th><th>Frequência</th><th>Parcelas</th><th>Valor</th><th>Status</th></tr>
    <?php foreach ($recurringRules as $rule): ?><tr><td><strong><?= e($rule['description']) ?></strong></td><td><?= e($rule['account_code'].' · '.$rule['account_name']) ?></td><td><?= e($frequencyLabels[$rule['frequency']] ?? $rule['frequency']) ?></td><td><?= (int)$rule['generated_count'] ?>/<?= (int)$rule['installments'] ?></td><td><?= money($rule['amount']) ?></td><td><span class="badge success"><?= $rule['active'] ? 'Ativa' : 'Encerrada' ?></span></td></tr><?php endforeach; ?>
  </table></div>
</section>
<?php endif; ?>

<section class="card finance-table-card">
  <div class="card-heading"><div><span class="section-kicker">EXTRATO</span><h2>Movimentações</h2></div><span class="heading-icon">≡</span></div>
  <div class="table-wrap"><table><tr><th>Vence</th><th>Descrição</th><th>Origem</th><th>Plano de contas</th><th>Forma</th><th>Natureza</th><th>Parcela</th><th>Valor</th><th>Status</th><th>Ações</th></tr>
    <?php foreach ($entries as $entry): ?>
      <tr>
        <td><?= e($entry['due_date']) ?></td><td><strong><?= e($entry['description']) ?></strong></td><td><span class="badge <?=($entry['expense_origin']??'company')==='owner_personal'?'warning':'success'?>"><?=($entry['expense_origin']??'company')==='owner_personal'?'Titular':'Empresa'?></span></td><td><?= e(($entry['account_code'] ? $entry['account_code'].' · ' : '').($entry['account_name'] ?: $entry['category'])) ?></td><td><?= e($entry['payment_method_name'] ?: $entry['payment_method'] ?: '—') ?></td><td><?= e($directionLabels[$entry['direction']] ?? $entry['direction']) ?></td><td><?= $entry['total_installments'] ? (int)$entry['installment_number'].'/'.(int)$entry['total_installments'] : '—' ?></td><td><?= money($entry['amount']) ?></td><td><span class="badge <?= $entry['status'] === 'paid' ? 'success' : ($entry['status'] === 'cancelled' ? 'danger' : 'warning') ?>"><?= e($statusLabels[$entry['status']] ?? $entry['status']) ?></span></td>
        <td><?php if ($entry['status'] === 'pending'): ?><form class="inline" method="post" action="/financeiro/liquidar"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($entry['id']) ?>"><button class="small-button" type="submit">Liquidar</button></form><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$entries): ?><tr><td colspan="10" class="empty-state">Nenhuma movimentação cadastrada.</td></tr><?php endif; ?>
  </table></div>
</section>
