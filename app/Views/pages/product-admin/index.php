<?php if(!empty($_SESSION['flash'])):?><div class="alert" role="alert"><?=e($_SESSION['flash']);unset($_SESSION['flash']);?></div><?php endif;?>
<?php if(!empty($_SESSION['flash_success'])):?><div class="alert alert-success" role="status"><?=e($_SESSION['flash_success']);unset($_SESSION['flash_success']);?></div><?php endif;?>
<?php
$planById=[];foreach($plans as $p)$planById[$p['external_id']]=$p;
$stateLabel=static fn($desired,$applied,$error)=>$error?'Requer atenção':((int)$desired>(int)$applied?'Pendente de aplicação':'Aplicado');
$planForm=static function(array $p,bool $new=false)use($systemId,$features,$limits){
  $ff=json_decode($p['features_json']??'{}',true)?:[];$ll=json_decode($p['limits_json']??'{}',true)?:[];
?>
<form method="post" action="/administracao-produtos/plano" class="form-grid module-inner">
  <?=csrf_field()?>
  <input type="hidden" name="system_id" value="<?=$systemId?>"><input type="hidden" name="external_id" value="<?=e($p['external_id']??'')?>"><input type="hidden" name="revision" value="<?=e($p['revision']??0)?>">
  <label>Nome<input name="name" value="<?=e($p['name']??'')?>" maxlength="120" required></label>
  <label>Código permanente<input name="slug" value="<?=e($p['slug']??'')?>" pattern="[a-z0-9][a-z0-9-]*" maxlength="120" <?=$new?'required':'readonly'?>></label>
  <label>Preço mensal (R$)<input name="price_monthly" type="number" step="0.01" min="0" max="99999999.99" value="<?=e($p['price']??'')?>"><small>Referência comercial; não gera cobrança.</small></label>
  <label>Status<select name="active"><option value="1" <?=($p['active']??1)?'selected':''?>>Ativo</option><option value="0" <?=isset($p['active'])&&!$p['active']?'selected':''?>>Inativo</option></select></label>
  <label>Ordem<input type="number" name="sort_order" min="0" max="1000000" value="<?=e($p['sort_order']??0)?>" required></label>
  <label class="span-3">Descrição<textarea name="description" maxlength="1000" rows="2"><?=e($p['description']??'')?></textarea></label>
  <fieldset class="span-3"><legend>Recursos permitidos</legend><div class="form-grid">
    <?php foreach($features as $key=>$label):?><label><span><input type="checkbox" name="features[<?=e($key)?>]" value="1" <?=!empty($ff[$key])?'checked':''?>> <?=e($label)?></span></label><?php endforeach;?>
  </div></fieldset>
  <fieldset class="span-3"><legend>Limites</legend><p class="muted">-1 = ilimitado. Vazio = regra padrão do sistema; não significa necessariamente ilimitado. Limites menores podem impedir novas operações, sem apagar dados.</p><div class="form-grid">
    <?php foreach($limits as $key=>$label):?><label><?=e($label)?><input name="limits[<?=e($key)?>]" type="number" step="1" min="-1" max="1000000000" value="<?=e($ll[$key]??'')?>"></label><?php endforeach;?>
  </div></fieldset>
  <label class="span-3">Motivo da alteração<input name="reason" maxlength="500" required placeholder="Ex.: ajuste comercial aprovado"></label>
  <label class="span-3"><span><input type="checkbox" name="confirm" value="1" required> Confirmo: estas regras passam a valer para todas as contas vinculadas ao plano após a sincronização.</span></label>
  <div class="actions span-3"><button type="submit"><?=$new?'Criar plano':'Salvar alterações'?></button></div>
</form>
<?php }; ?>
<section class="card module-card">
  <h2>Administração central dos produtos</h2>
  <p>Planos, limites e acesso das contas são administrados aqui. O produto confirma cada alteração; uma solicitação pendente ainda não significa que foi aplicada.</p>
  <p class="muted">Não gera cobranças no Asaas, não altera senhas e não exclui checklists, clientes ou execuções.</p>
  <form method="get" class="actions"><label>Produto<select name="system_id"><?php foreach($systems as $s):?><option value="<?=$s['id']?>" <?=(int)$s['id']===$systemId?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></label><button>Consultar</button></form>
  <?php if(!$system):?><p>Nenhum produto foi habilitado para administração integrada. A habilitação exige configurar e validar o conector do produto.</p><?php endif;?>
</section>
<?php if($system): $stale=!$system['last_sync_at']||strtotime($system['last_sync_at'])<time()-180; ?>
<section class="grid cols-3" style="margin-top:16px">
  <article class="card"><h3>Conexão <?=$stale?'a verificar':'recente'?></h3><p>Último contato: <?=e($system['last_sync_at']??'Ainda não sincronizado')?> (servidor)</p><strong><?=$pending?> alteração(ões) pendente(s)</strong><?php if($stale):?><p>O produto mantém a última configuração válida enquanto a conexão não retorna.</p><?php endif;?></article>
  <article class="card"><h3>Contas e uso</h3><p><?=e($health['accounts']??0)?> contas · <?=e($health['active_accounts']??0)?> ativas · <?=e($health['users']??0)?> usuários</p><p><?=e($health['executions_month']??0)?> execuções principais no mês</p></article>
  <article class="card"><h3>Suporte integrado</h3><p><?=e($health['support_open']??0)?> abertos · <?=e($health['support_pending']??0)?> aguardando protocolo · <?=e($health['support_errors']??0)?> com sincronização pendente</p><div class="actions"><a class="btn secondary" href="/suporte?system_id=<?=$systemId?>">Atender chamados</a><a class="btn secondary" href="/conhecimento?system_id=<?=$systemId?>">Editar ajuda</a></div></article>
</section>
<?php require __DIR__.'/referrals.php'; ?>
<section class="card module-card" id="planos" style="margin-top:16px">
  <h2>Planos de <?=e($system['name'])?></h2>
  <p class="muted">Planos antigos e de teste foram preservados. Não são substituídos ou excluídos automaticamente. O plano Comunidade permanece gratuito e ativo para novos cadastros.</p>
  <?php foreach($plans as $p):?><details class="card module-inner"><summary><strong><?=e($p['name'])?></strong> · <?=$p['price']===null?'Preço não definido':money($p['price']).'/mês'?> · <?=$p['accounts_count']?> conta(s) · <?=$p['active']?'Ativo':'Inativo'?> · <?=e($stateLabel($p['revision'],$p['applied_revision'],$p['sync_error']))?> (v<?=$p['revision']?>)</summary><?php if($p['sync_error']):?><p role="alert"><?=e($p['sync_error'])?></p><?php endif;?><?php $planForm($p);?></details><?php endforeach;?>
  <details class="card module-inner"><summary>Criar novo plano integrado</summary><?php $planForm([],true);?></details>
</section>
<section class="card module-card" id="contas" style="margin-top:16px">
  <h2>Contas dos clientes</h2><form method="get" class="actions"><input type="hidden" name="system_id" value="<?=$systemId?>"><label>Buscar por nome<input name="q" value="<?=e($query)?>" maxlength="120"></label><button>Buscar</button></form>
  <p><?=$total?> conta(s) · página <?=$page?></p>
  <?php foreach($accounts as $a):$usage=json_decode($a['usage_json']??'{}',true)?:[];?>
  <details class="card module-inner"><summary><strong><?=e($a['name'])?></strong> · <?=e(['driver'=>'Motorista','workshop'=>'Oficina','fleet'=>'Empresa/Frota'][$a['profile']]??$a['profile'])?> · <?=e($planById[$a['plan_external_id']]['name']??'Sem plano')?> · <?=$a['active']?'Ativa':'Suspensa'?> · <?=e($stateLabel($a['revision'],$a['observed_revision'],$a['sync_error']))?></summary>
    <p class="muted">Uso: <?=e($usage['users']??0)?> usuários · <?=e($usage['vehicles']??0)?> veículos · <?=e($usage['templates']??0)?> modelos · <?=e($usage['executions_month']??0)?> execuções/mês · <?=number_format(($usage['storage_bytes']??0)/1048576,1,',','.')?> MB. Dados em <?=e($a['last_seen_at'])?>.</p>
    <p>Confirmado no produto: <?=e($planById[$a['observed_plan_external_id']]['name']??'Sem plano')?> · <?=$a['observed_active']?'Ativa':'Suspensa'?> · revisão <?=$a['observed_revision']?>. <?php if($a['sync_error']):?><strong role="alert"><?=e($a['sync_error'])?></strong><?php endif;?></p>
    <form method="post" action="/administracao-produtos/conta" class="form-grid">
      <?=csrf_field()?><input type="hidden" name="system_id" value="<?=$systemId?>"><input type="hidden" name="external_id" value="<?=e($a['external_id'])?>"><input type="hidden" name="revision" value="<?=$a['revision']?>">
      <label>Plano<select name="plan_external_id" required><?php foreach($plans as $p):if(!$p['active']&&$p['external_id']!==$a['plan_external_id'])continue;?><option value="<?=e($p['external_id'])?>" <?=$p['external_id']===$a['plan_external_id']?'selected':''?>><?=e($p['name'])?></option><?php endforeach;?></select></label>
      <label>Acesso<select name="active"><option value="1" <?=$a['active']?'selected':''?>>Ativo</option><option value="0" <?=!$a['active']?'selected':''?>>Suspenso</option></select></label>
      <label>Motivo<input name="reason" maxlength="500" required></label>
      <label class="span-3"><span><input type="checkbox" name="confirm" value="1" required> Confirmo a mudança de plano/acesso desta conta. Suspender impede o uso, mas preserva os dados; reativar restaura o acesso.</span></label>
      <div class="actions span-3"><button>Aplicar nesta conta</button><a class="btn secondary" href="/suporte?system_id=<?=$systemId?>&tenant_id=<?=e($a['external_id'])?>">Chamados desta conta</a></div>
    </form>
  </details><?php endforeach;?>
  <nav class="actions" aria-label="Páginas de contas"><?php if($page>1):?><a href="?<?=e(http_build_query(['system_id'=>$systemId,'q'=>$query,'page'=>$page-1]))?>#contas">Anterior</a><?php endif;?><?php if($page*50<$total):?><a href="?<?=e(http_build_query(['system_id'=>$systemId,'q'=>$query,'page'=>$page+1]))?>#contas">Próxima</a><?php endif;?></nav>
</section>
<details class="card module-card" style="margin-top:16px"><summary>Histórico administrativo — últimas 50 alterações</summary>
  <?php foreach($audit as $row):?><details class="module-inner"><summary><?=e($row['created_at'])?> · <?=e($row['actor_name']??'Importação do produto')?> · <?=e($row['entity_type']==='plan'?'Plano':'Conta')?> · <?=e($row['reason'])?></summary><p>ID: <?=e($row['external_id'])?></p><div class="grid cols-2"><div><strong>Antes</strong><pre style="white-space:pre-wrap;overflow-wrap:anywhere"><?=e(json_encode(json_decode($row['before_json']??'null',true),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))?></pre></div><div><strong>Depois</strong><pre style="white-space:pre-wrap;overflow-wrap:anywhere"><?=e(json_encode(json_decode($row['after_json'],true),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))?></pre></div></div></details><?php endforeach;?>
</details>
<?php endif;?>
