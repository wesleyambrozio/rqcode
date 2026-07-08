<section class="card">
  <h2>Registrar chamado</h2>
  <form method="post" action="/suporte" class="form-grid">
    <?= csrf_field() ?>
    <label>Sistema <select name="system_id" required><?php foreach ($systems as $system): ?><option value="<?= e($system['id']) ?>"><?= e($system['name']) ?></option><?php endforeach; ?></select></label>
    <label>ID externo <input name="external_id"></label>
    <label>Cliente <input name="customer_name"></label>
    <label>E-mail <input type="email" name="customer_email"></label>
    <label>Assunto <input name="subject" required></label>
    <label>Prioridade <select name="priority"><option>low</option><option>normal</option><option>high</option><option>urgent</option></select></label>
    <label>Status <select name="status"><option>open</option><option>in_progress</option><option>waiting_customer</option><option>closed</option></select></label>
    <label>Abertura <input type="datetime-local" name="opened_at" value="<?= date('Y-m-d\TH:i') ?>"></label>
    <div class="actions"><button type="submit">Salvar chamado</button></div>
  </form>
</section>
<section class="card" style="margin-top:16px">
  <h2>Chamados</h2>
  <div class="table-wrap"><table><tr><th>Abertura</th><th>Sistema</th><th>Cliente</th><th>Assunto</th><th>Prioridade</th><th>Status</th></tr>
    <?php foreach ($tickets as $ticket): ?><tr><td><?= e($ticket['opened_at']) ?></td><td><?= e($ticket['system_name']) ?></td><td><?= e($ticket['customer_name']) ?></td><td><?= e($ticket['subject']) ?></td><td><?= e($ticket['priority']) ?></td><td><span class="badge"><?= e($ticket['status']) ?></span></td></tr><?php endforeach; ?>
  </table></div>
</section>
