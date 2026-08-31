<section class="card module-card workflow-form-card">
  <div class="card-heading"><div><span class="section-kicker">ENVIO SEGURO</span><h2>Enviar documento para a empresa</h2></div><span class="badge success">Acesso do contador</span></div>
  <form method="post" action="/contabilidade/documentos" enctype="multipart/form-data" class="form-grid module-inner">
    <?=csrf_field()?>
    <label class="span-2">Titulo<input name="title" maxlength="180" required></label>
    <label>Categoria<select name="category"><option value="tax">Imposto/guia</option><option value="report">Relatorio contabil</option><option value="payroll">Folha/pro-labore</option><option value="bank_slip">Boleto</option><option value="payment_receipt">Comprovante</option><option value="invoice">Nota fiscal</option><option value="bank_statement">Extrato</option><option value="contract">Contrato</option><option value="other">Outro</option></select></label>
    <label>Competencia<input type="month" name="reference_month" value="<?=date('Y-m')?>" required></label>
    <label class="span-2">Arquivo PDF, JPG ou PNG<input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required></label>
    <label class="span-3">Observacoes<textarea name="notes" maxlength="2000"></textarea></label>
    <div class="actions span-3"><button>Enviar documento</button></div>
  </form>
</section>
