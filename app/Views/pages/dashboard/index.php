<section class="dashboard-hero">
  <div>
    <span class="hero-label">VISÃO EXECUTIVA</span>
    <h2>Controle toda a operação em um só lugar.</h2>
    <p>Acompanhe clientes, receita, suporte e desempenho dos produtos RQCODE em tempo real.</p>
  </div>
  <div class="hero-orbit" aria-hidden="true"><span>RQ</span></div>
</section>

<section class="grid cols-4 metric-grid">
  <article class="card metric metric-blue"><span class="metric-icon">◎</span><div><span class="muted">Contas</span><strong><?= (int) $metrics['accounts'] ?></strong><small>Base total</small></div></article>
  <article class="card metric metric-cyan"><span class="metric-icon">＋</span><div><span class="muted">Novas contas hoje</span><strong><?= (int) $metrics['new_accounts'] ?></strong><small>Novas adesões</small></div></article>
  <article class="card metric metric-violet"><span class="metric-icon">◇</span><div><span class="muted">Usuários ativos</span><strong><?= (int) $metrics['active_users'] ?></strong><small>Na plataforma</small></div></article>
  <article class="card metric metric-green"><span class="metric-icon">●</span><div><span class="muted">Usuários online</span><strong><?= (int) $metrics['online_users'] ?></strong><small>Agora</small></div></article>
  <article class="card metric metric-amber"><span class="metric-icon">!</span><div><span class="muted">Pagamentos pendentes</span><strong><?= money($metrics['pending_payments']) ?></strong><small>A receber</small></div></article>
  <article class="card metric metric-green"><span class="metric-icon">$</span><div><span class="muted">Recebido em 30 dias</span><strong><?= money($metrics['paid_payments']) ?></strong><small>Fluxo confirmado</small></div></article>
  <article class="card metric metric-red"><span class="metric-icon">?</span><div><span class="muted">Chamados abertos</span><strong><?= (int) $metrics['open_tickets'] ?></strong><small>Aguardando equipe</small></div></article>
</section>

<section class="grid cols-2 dashboard-sections">
  <div class="card data-card">
    <div class="card-heading"><div><span class="section-kicker">FINANCEIRO</span><h2>Próximos vencimentos</h2></div><span class="heading-icon">↗</span></div>
    <div class="table-wrap">
      <table>
        <tr><th>Descrição</th><th>Tipo</th><th>Valor</th><th>Vence</th></tr>
        <?php foreach ($entries as $entry): ?>
          <tr><td><strong><?= e($entry['description']) ?></strong></td><td><?= e($entry['direction']) ?></td><td><?= money($entry['amount']) ?></td><td><?= e($entry['due_date']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$entries): ?><tr><td colspan="4" class="empty-state">Nenhum vencimento próximo.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
  <div class="card data-card">
    <div class="card-heading"><div><span class="section-kicker">ATENDIMENTO</span><h2>Chamados recentes</h2></div><span class="heading-icon">?</span></div>
    <div class="table-wrap">
      <table>
        <tr><th>Sistema</th><th>Cliente</th><th>Assunto</th><th>Status</th></tr>
        <?php foreach ($tickets as $ticket): ?>
          <tr><td><strong><?= e($ticket['system_name']) ?></strong></td><td><?= e($ticket['customer_name']) ?></td><td><?= e($ticket['subject']) ?></td><td><span class="badge"><?= e($ticket['status']) ?></span></td></tr>
        <?php endforeach; ?>
        <?php if (!$tickets): ?><tr><td colspan="4" class="empty-state">Nenhum chamado recente.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
</section>
