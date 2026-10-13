<?php if (!empty($_SESSION['flash'])): ?><div class="alert"><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div><?php endif; ?>
<?php if (!empty($_SESSION['flash_success'])): ?><div class="alert alert-success"><?= e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div><?php endif; ?>
<section class="card">
  <h2>Configuração Asaas</h2>
  <p>Ambiente: <strong><?= e($environment) ?></strong> · API: <?= $configured?'chave configurada (conexão ainda deve ser validada)':'pendente' ?> · Token do webhook: <?= $webhookConfigured?'configurado':'pendente' ?></p>
  <p>URL do webhook: <code><?= e($webhookUrl) ?></code></p>
  <p class="muted">Credenciais são configuradas no ambiente do servidor. Sandbox não liquida lançamentos financeiros reais. Assinaturas automáticas exigem integração específica com cada produto.</p>
  <a href="/financeiro">Voltar ao financeiro</a>
</section>
<?php if ($migrationReady): ?>
<section class="card">
  <h2>Assinaturas mensais</h2>
  <p>O cliente contrata e cancela a renovação em Plano e assinatura no Checklist. O preço contratado fica registrado e não acompanha automaticamente alterações no catálogo.</p>
  <div class="table-wrap"><table><tr><th>Conta</th><th>Plano</th><th>Valor mensal</th><th>Situação</th><th>Período pago até</th></tr>
  <?php foreach($subscriptions as $subscription): ?><tr><td><?= e($subscription['account_name'] ?: $subscription['tenant_external_id']) ?></td><td><?= e($subscription['plan_name']) ?></td><td><?= money($subscription['amount']) ?></td><td><?= e($subscription['gateway_status']) ?></td><td><?= e($subscription['service_until'] ?: 'Aguardando pagamento') ?></td></tr><?php endforeach; ?>
  <?php if(!$subscriptions): ?><tr><td colspan="5">Nenhuma assinatura neste ambiente.</td></tr><?php endif; ?>
  </table></div>
</section>
<section class="card">
  <h2>Emitir cobrança avulsa</h2>
  <form method="post" action="/financeiro/asaas/criar" class="stack-form">
    <?= csrf_field() ?>
    <label>Lançamento a receber <select name="entry_id" required><option value="">Selecione</option><?php foreach($entries as $entry): ?><option value="<?= (int)$entry['id'] ?>"><?= e($entry['description'].' · '.money($entry['amount']).' · '.$entry['due_date']) ?></option><?php endforeach; ?></select></label>
    <label>Cliente cadastrado no Asaas <input name="customer_id" required pattern="cus_[a-zA-Z0-9_]+" placeholder="cus_..."></label>
    <label>Forma <select name="billing_type"><option value="PIX">Pix</option><option value="BOLETO">Boleto</option><option value="UNDEFINED">Cliente escolhe na fatura</option></select></label>
    <label class="checkbox-label"><input type="checkbox" name="confirm" value="1" required> Confirmo a emissão no ambiente <?= e($environment) ?> para o cliente e valor selecionados. O Asaas pode enviar notificações ao cliente.</label>
    <button <?= !$configured || !$webhookConfigured?'disabled':'' ?>>Emitir cobrança</button>
  </form>
</section>
<section class="card">
  <h2>Últimas cobranças</h2>
  <div class="table-wrap"><table><tr><th>Lançamento</th><th>Valor</th><th>Estado Asaas</th><th>Ações</th></tr>
    <?php foreach($charges as $charge): ?><tr><td><?= e($charge['description']) ?></td><td><?= money($charge['amount']) ?></td><td><?= e($charge['gateway_status']) ?></td><td>
      <?php if($charge['invoice_url']): ?><a href="<?= e($charge['invoice_url']) ?>" target="_blank" rel="noopener noreferrer">Abrir fatura</a><?php endif; ?>
      <?php if($charge['payment_id']): ?><form method="post" action="/financeiro/asaas/conciliar" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$charge['id'] ?>"><button class="small-button">Conciliar</button></form><?php else: ?>Tentativa sem confirmação. Selecione o mesmo lançamento para consultar a tentativa anterior.<?php endif; ?>
    </td></tr><?php endforeach; ?>
    <?php if(!$charges): ?><tr><td colspan="4">Nenhuma cobrança neste ambiente.</td></tr><?php endif; ?>
  </table></div>
</section>
<?php endif; ?>
