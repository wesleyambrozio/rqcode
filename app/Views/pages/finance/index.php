<section class="grid cols-4">
  <div class="card metric"><span class="muted">A receber</span><strong><?= money($summary['receivable']) ?></strong></div>
  <div class="card metric"><span class="muted">A pagar</span><strong><?= money($summary['payable']) ?></strong></div>
  <div class="card metric"><span class="muted">Pago/recebido</span><strong><?= money($summary['paid']) ?></strong></div>
  <div class="card metric"><span class="muted">Vencidos</span><strong><?= money($summary['overdue']) ?></strong></div>
</section>
<section class="card" style="margin-top:16px">
  <h2>Novo lançamento</h2>
  <form method="post" action="/financeiro" class="form-grid">
    <?= csrf_field() ?>
    <label>Descrição <input name="description" required></label>
    <label>Categoria <input name="category" placeholder="assinatura, comissão, imposto..."></label>
    <label>Tipo <select name="direction"><option value="receivable">Receber</option><option value="payable">Pagar</option></select></label>
    <label>Valor <input type="number" step="0.01" name="amount" required></label>
    <label>Vencimento <input type="date" name="due_date" required></label>
    <label>Status <select name="status"><option value="pending">Pendente</option><option value="paid">Pago</option><option value="cancelled">Cancelado</option></select></label>
    <label>Forma <input name="payment_method"></label>
    <label>Observações <textarea name="notes"></textarea></label>
    <div class="actions"><button type="submit">Salvar lançamento</button></div>
  </form>
</section>
<section class="card" style="margin-top:16px">
  <h2>Movimentações</h2>
  <div class="table-wrap"><table><tr><th>Vence</th><th>Descrição</th><th>Categoria</th><th>Tipo</th><th>Valor</th><th>Status</th><th>Ações</th></tr>
    <?php foreach ($entries as $entry): ?>
      <tr>
        <td><?= e($entry['due_date']) ?></td><td><?= e($entry['description']) ?></td><td><?= e($entry['category']) ?></td><td><?= e($entry['direction']) ?></td><td><?= money($entry['amount']) ?></td><td><span class="badge <?= $entry['status'] === 'paid' ? 'success' : 'warning' ?>"><?= e($entry['status']) ?></span></td>
        <td><?php if ($entry['status'] === 'pending'): ?><form class="inline" method="post" action="/financeiro/liquidar"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($entry['id']) ?>"><button type="submit">Liquidar</button></form><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
</section>
