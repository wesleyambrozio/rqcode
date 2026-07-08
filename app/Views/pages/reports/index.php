<section class="grid cols-2">
  <div class="card metric"><span class="muted">MRR registrado</span><strong><?= money($mrr) ?></strong></div>
  <div class="card metric"><span class="muted">Comissões acumuladas</span><strong><?= money($commissions) ?></strong></div>
</section>
<section class="card" style="margin-top:16px">
  <h2>Ranking de vendedores</h2>
  <div class="table-wrap"><table><tr><th>Vendedor</th><th>Vendas</th><th>Total vendido</th></tr>
    <?php foreach ($vendors as $vendor): ?><tr><td><?= e($vendor['name']) ?></td><td><?= e($vendor['sales_count']) ?></td><td><?= money($vendor['total']) ?></td></tr><?php endforeach; ?>
  </table></div>
</section>
