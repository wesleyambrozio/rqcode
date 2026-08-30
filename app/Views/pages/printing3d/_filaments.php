<?php $editing=$editingFilament??null; ?>
<section class="list-first-stack">
  <div class="card data-card">
    <div class="card-heading compact"><div><span class="section-kicker">ESTOQUE</span><h2>Filamentos cadastrados</h2></div><button type="button" class="small-button" data-workflow-open="filament-form">Novo filamento</button></div>
    <div class="table-wrap"><table><thead><tr><th>ID / Filamento</th><th>Origem</th><th>Rolos</th><th>Saldo</th><th>Custo total</th><th>Custo/g</th><th>Status</th><th>Acoes</th></tr></thead><tbody>
      <?php foreach($filaments as $f):?><tr><td><strong><?=e($f['inventory_code']?:'FIL-'.str_pad((string)$f['id'],6,'0',STR_PAD_LEFT))?> · <?=e($f['name'])?></strong><br><small><?=e($f['material'].($f['color']?' · '.$f['color']:''))?></small></td><td><?=e($f['supplier_name']?:'-')?><br><small><?=e($f['batch_code']?:'Cadastro manual')?></small></td><td><?=number_format($f['spool_quantity']??1,0)?></td><td><?=number_format($f['current_weight_g'],0)?> g</td><td><?=money($f['purchase_price'])?></td><td><?=money($f['cost_per_g'])?></td><td><span class="badge <?=((float)$f['current_weight_g']<=(float)$f['minimum_stock_g'])?'warning':'success'?>"><?=((float)$f['current_weight_g']<=(float)$f['minimum_stock_g'])?'Repor':'OK'?></span></td><td><a class="btn secondary-button small-button" href="/impressao-3d?view=filamentos&amp;edit_filament=<?=$f['id']?>">Editar</a></td></tr><?php endforeach;?>
      <?php if(!$filaments):?><tr><td colspan="8" class="empty-state">Nenhum filamento cadastrado.</td></tr><?php endif;?>
    </tbody></table></div>
  </div>
  <div class="card workflow-form-card" id="filament-form" <?=$editing?'data-form-open':''?> <?=$editing?'':'hidden'?>>
    <div class="card-heading compact"><div><span class="section-kicker"><?=$editing?'EDICAO':'CADASTRO'?></span><h2><?=$editing?'Editar '.e($editing['inventory_code']?:$editing['name']):'Novo filamento'?></h2></div><a class="btn secondary-button small-button" href="/impressao-3d?view=filamentos">Cancelar</a></div>
    <form method="post" action="/impressao-3d/filamentos" class="form-grid"><?=csrf_field()?><?php if($editing):?><input type="hidden" name="id" value="<?=$editing['id']?>"><?php endif;?>
      <label>Fornecedor<select name="supplier_id"><option value="">Sem fornecedor</option><?php foreach($suppliers as $s):?><option value="<?=$s['id']?>" <?=((int)($editing['supplier_id']??0)===(int)$s['id'])?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></label>
      <label>Identificacao<input name="name" required value="<?=e($editing['name']??'')?>"></label>
      <label>Material<select name="material"><?php foreach(['PLA','PETG','ABS','TPU','ASA','Nylon','Resina','Outro'] as $m):?><option <?=($editing['material']??'')===$m?'selected':''?>><?=$m?></option><?php endforeach;?></select></label>
      <label>Marca<input name="brand" value="<?=e($editing['brand']??'')?>"></label><label>Cor<input name="color" value="<?=e($editing['color']??'')?>"></label><label>Diametro mm<input type="number" min="0.01" step="0.01" name="diameter_mm" value="<?=e($editing['diameter_mm']??'1.75')?>"></label>
      <label>Peso liquido g<input type="number" min="0.01" step="0.01" name="spool_net_weight_g" required value="<?=e($editing['spool_net_weight_g']??'')?>"></label><label>Saldo atual g<input type="number" min="0" step="0.01" name="current_weight_g" value="<?=e($editing['current_weight_g']??'')?>"></label><label>Custo total<input type="number" min="0" step="0.01" name="purchase_price" required value="<?=e($editing['purchase_price']??'')?>"></label>
      <label>Lote<input name="batch_code" value="<?=e($editing['batch_code']??'')?>"></label><label>Compra<input type="date" name="purchase_date" value="<?=e($editing['purchase_date']??'')?>"></label><label>Estoque minimo g<input type="number" min="0" step="0.01" name="minimum_stock_g" value="<?=e($editing['minimum_stock_g']??'')?>"></label>
      <div class="actions span-3"><button><?=$editing?'Salvar alteracoes':'Cadastrar filamento'?></button><a class="btn secondary-button" href="/impressao-3d?view=filamentos">Cancelar</a></div>
    </form>
  </div>
</section>
