# Recebimentos Asaas

Implementado na Central em `/financeiro/asaas`: cobranças avulsas de lançamentos a receber, Pix/boleto/fatura com escolha do pagador, fatura hospedada, conciliação manual e webhook autenticado. A emissão exige administrador e confirmação no formulário. Clientes devem estar previamente cadastrados no mesmo ambiente do Asaas; utilize o identificador `cus_...`. Não há coleta de cartão na Central.

## Configuração

1. Execute `php bin/migrate.php` (migration 020 para MySQL/MariaDB ou PostgreSQL).
2. Configure no ambiente/cofre `ASAAS_ENV=sandbox`, `ASAAS_API_KEY` da conta sandbox e `ASAAS_WEBHOOK_TOKEN` exclusivo, aleatório, entre 32 e 255 caracteres, sem espaços. Não use a API key como token do webhook. Exemplo para gerar token local: `php -r "echo bin2hex(random_bytes(32));"`.
3. Configure `APP_URL` com o endereço público HTTPS da Central. Localhost não recebe callbacks externos.
   Se o PHP local retornar erro TLS por ausência de certificados, configure `ASAAS_CA_BUNDLE` com um arquivo PEM de autoridades confiáveis do sistema. Preserve a verificação HTTPS.
4. Cadastre no painel Asaas um webhook para `https://SEU-DOMINIO/api/asaas/webhook`, com o mesmo token, eventos de cobranças `PAYMENT_*` disponíveis e envio sequencial. A rota não utiliza sessão nem CSRF: valida `asaas-access-token`, limita corpo a 256 KiB e taxa a 120 chamadas autenticadas/minuto por servidor.
5. Cadastre um cliente fictício no sandbox, crie um lançamento a receber e emita uma cobrança pelo formulário. Simule pagamento, atraso e estorno no sandbox; confira estado e eventos. O sandbox preserva o estado financeiro real.
6. Após homologação, configure as credenciais e webhook independentes de produção, `ASAAS_ENV=production` e `APP_DEBUG=false`. Valide uma cobrança autorizada e seu recebimento real antes de liberar o uso comercial.

## Operação e limites

- Chave presente não significa conexão validada. Nenhuma credencial deve aparecer em documentação, Git ou mensagens.
- Uma reserva única por lançamento/ambiente é persistida antes de enviar a cobrança. Após timeout, repetir pelo mesmo lançamento apenas busca a cobrança por `externalReference`. Se não for encontrada, a emissão permanece bloqueada; investigue no painel antes de qualquer liberação manual. Não apague reservas sem confirmar que não existe cobrança remota.
- Webhooks conhecidos consultam o estado atual pela API sob bloqueio da cobrança, evitando regressão por evento atrasado. Falhas retornam 503 para reentrega; eventos duplicados são ignorados. Eventos de outros produtos são ignorados sem afetar lançamentos.
- Apenas `RECEIVED`/`RECEIVED_IN_CASH` liquidam produção; `CONFIRMED` permanece pendente porque pode ser provisório. Estorno integral cancela e limpa a data; estorno parcial exige análise manual. Valores brutos são conciliados; taxas e saldo líquido não são lançados automaticamente.
- Eventos armazenam identificador, tipo, cobrança e data, sem payload pessoal completo. A cobrança registra o administrador emissor. Histórico está isolado por ambiente.
- Assinaturas recorrentes, cadastro automático de clientes, liberação/bloqueio de planos nos produtos e comissões automáticas ainda precisam de implementação específica. Recorrências financeiras existentes não geram assinaturas Asaas.
- Para conciliar uma cobrança, use **Conciliar**. Cobranças listadas têm limite de 100 registros recentes; lançamentos selecionáveis, 200 pendentes.
- Reversão de implantação: desabilite o webhook no Asaas e remova o acesso às rotas; preserve tabelas, reservas e referências para auditoria. Não exclua cobranças remotas automaticamente.

## Validação local

`php tests/asaas_payments_test.php` executa cenários isolados em SQLite com transporte simulado, sem emitir cobranças externas. Homologação real e execução das migrations em ambos os bancos devem ser verificadas no ambiente de destino.

Referências: [criação de cobrança](https://docs.asaas.com/reference/criar-nova-cobranca), [listagem por referência](https://docs.asaas.com/reference/listar-cobrancas), [webhooks](https://docs.asaas.com/docs/sobre-os-webhooks), [eventos](https://docs.asaas.com/docs/webhook-para-cobrancas).
