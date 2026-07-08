<section class="grid cols-4">
  <div class="card metric"><span class="muted">Contas</span><strong><?= (int) $metrics['accounts'] ?></strong></div>
  <div class="card metric"><span class="muted">Novas contas hoje</span><strong><?= (int) $metrics['new_accounts'] ?></strong></div>
  <div class="card metric"><span class="muted">Usuários ativos</span><strong><?= (int) $metrics['active_users'] ?></strong></div>
  <div class="card metric"><span class="muted">Usuários online</span><strong><?= (int) $metrics['online_users'] ?></strong></div>
  <div class="card metric"><span class="muted">Pagamentos pendentes</span><strong><?= money($metrics['pending_payments']) ?></strong></div>
  <div class="card metric"><span class="muted">Pagamentos realizados 30d</span><strong><?= money($metrics['paid_payments']) ?></strong></div>
  <div class="card metric"><span class="muted">Chamados abertos</span><strong><?= (int) $metrics['open_tickets'] ?></strong></div>
</section>

<section class="grid cols-2" style="margin-top:16px">
  <div class="card">
    <h2>Próximos vencimentos</h2>
    <div class="table-wrap">
      <table>
        <tr><th>Descrição</th><th>Tipo</th><th>Valor</th><th>Vence</th></tr>
        <?php foreach ($entries as $entry): ?>
          <tr><td><?= e($entry['description']) ?></td><td><?= e($entry['direction']) ?></td><td><?= money($entry['amount']) ?></td><td><?= e($entry['due_date']) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
  <div class="card">
    <h2>Chamados recentes</h2>
    <div class="table-wrap">
      <table>
        <tr><th>Sistema</th><th>Cliente</th><th>Assunto</th><th>Status</th></tr>
        <?php foreach ($tickets as $ticket): ?>
          <tr><td><?= e($ticket['system_name']) ?></td><td><?= e($ticket['customer_name']) ?></td><td><?= e($ticket['subject']) ?></td><td><span class="badge"><?= e($ticket['status']) ?></span></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
</section>
