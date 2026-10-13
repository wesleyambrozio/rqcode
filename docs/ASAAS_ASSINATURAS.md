# Assinaturas recorrentes — RQCODE e ZVTech Checklist

A assinatura mensal é o fluxo principal: o dono/administrador da conta acessa `/plano-assinatura` no Checklist, escolhe um plano, informa nome, e-mail e CPF/CNPJ do pagador e confirma expressamente o valor mensal. Starter usa o preço vigente do catálogo central; na implantação, R$ 79/mês. O cadastro gratuito continua sem CPF obrigatório para faturamento.

## Funcionamento

- A API `/api/billing/v1` da RQCODE autentica o produto por HMAC, timestamp e nonce, com segredo já usado na integração. O Checklist deriva conta e usuário da sessão e exige papel owner/admin, inclusive para contas pessoais de motorista. CPF/CNPJ e preço são validados novamente na Central.
- A Central cadastra um cliente Asaas e uma assinatura `MONTHLY`. A primeira fatura vence no dia da contratação; valor, plano, aceite, usuário e versão dos termos ficam registrados. As próximas faturas são geradas pelo Asaas. Alterar o catálogo não muda contratos existentes.
- O padrão `UNDEFINED` permite escolher cartão, Pix ou boleto na fatura Asaas, conforme habilitação da conta. Escolher cartão pode estabelecer débito automático nas renovações. Pix e boleto convencionais exigem pagamento de cada fatura; Pix Automático não foi contratado neste fluxo.
- Reservas são persistidas antes dos POSTs. Timeout não resulta em repetição automática: a recuperação pesquisa a referência externa e bloqueia emissão se não localizar um resultado único. Suporte deve investigar tentativas sem confirmação; não apagar reservas enquanto houver possibilidade de cadastro remoto.
- Webhooks consultam a cobrança atual sob bloqueio da assinatura. `CONFIRMED` de cartão libera o plano, mas não liquida caixa. Pix provisoriamente confirmado não libera. `RECEIVED`/`RECEIVED_IN_CASH` liberam e liquidam os lançamentos em produção. Dados de sandbox nunca mudam o plano real nem os recebíveis reais.
- O plano passa a valer até o próximo aniversário mensal do vencimento pago, com ajuste para o último dia em meses curtos. Pagamentos atrasados não reiniciam a vigência a partir do dia do pagamento. A Central publica a alteração de plano com revisão; o Checklist aplica pela consulta e sincronização administrativa existente.
- Cancelar remove a recorrência e as faturas pendentes/vencidas no Asaas. O período pago permanece ativo; no fim, retorna a Comunidade sem apagar registros. Não há proporcionalidade automática na troca: outro plano pode ser contratado ao terminar o período já pago. Estorno ou chargeback podem retirar a cobertura afetada. Restituições e direitos legais são atendidos pelo suporte, sem promessa de reembolso automático.
- A rotina `php bin/reconcile-subscriptions.php` recupera callbacks e recalcula vigência. Agendar com PHP 8.3 a cada cinco minutos. Não liquidar lançamentos ligados a assinaturas manualmente.

## Dados e termos

- Versão: `2026-10-13.billing.1`, em `/termos` e `/privacidade` no Checklist; links no cadastro, reaceite e contratação. O aceite geral e o aceite específico da recorrência são distintos.
- CPF/CNPJ completo é usado no formulário e transmissão HTTPS ao Asaas; não é persistido nas tabelas de cobrança da Central. Armazenam-se máscara dos últimos quatro dígitos, HMAC com `BILLING_DOCUMENT_KEY`, identificador Asaas e evidências do contrato. Sessão de validação e auditoria excluem documentos. Não coletar documento pelo chat ou suporte não protegido.
- CPF é dado pessoal. O tratamento necessário à execução da assinatura não utiliza um aceite genérico como consentimento para publicidade. A política informa finalidade, destinatário financeiro, conservação conforme contrato/obrigações e canal de direitos. O Asaas mantém seu cadastro do pagador segundo suas obrigações.
- Guardar chaves apenas em ambiente/cofre. Chaves e tokens de webhook de produção e sandbox são separados. A chave de proteção do documento deve ser aleatória, estável e conter ao menos 32 caracteres; perdê-la impede validar o documento previamente registrado.

## Configuração e homologação

1. Aplicar migration `021_asaas_subscriptions_mysql.sql` ou equivalente PostgreSQL; requer administração integrada (migrations 016/017).
2. Configurar `ASAAS_ENV`, `ASAAS_API_KEY`, `ASAAS_WEBHOOK_TOKEN`, `BILLING_DOCUMENT_KEY`. Para preservar homologação paralela, `ASAAS_SANDBOX_API_KEY` e `ASAAS_SANDBOX_WEBHOOK_TOKEN` ficam independentes.
3. Webhook ativo em `https://rqcode.com.br/api/asaas/webhook`, eventos de cobrança e de assinatura disponíveis, envio sequencial. O receptor escolhe o ambiente pelo token; nunca pelo corpo do evento.
4. Configurar `ZAP_TERMS_VERSION` e `ZAP_PRIVACY_VERSION` com a versão acima no Checklist e renovar caches de configuração e rotas.
5. Validar assinatura, recuperação após timeout, fatura, recebimento, estorno, cancelamento, bloqueio de outro tenant e limites após ativação. Não criar assinatura real para um usuário sem seu formulário preenchido e confirmação do valor.

Testes: `php tests/asaas_subscriptions_test.php`, `php tests/asaas_payments_test.php` e `BillingFlowTest` no Checklist. Os testes automatizados usam dados fictícios e transporte simulado.

Fontes: [API de assinaturas](https://docs.asaas.com/reference/criar-nova-assinatura), [cadastro do pagador](https://docs.asaas.com/reference/criar-novo-cliente), [cancelamento](https://docs.asaas.com/reference/remover-assinatura), [formas de pagamento recorrentes](https://central.ajuda.asaas.com/hc/pt-br/articles/54672181332379-Quais-s%C3%A3o-as-formas-de-pagamento-dispon%C3%ADveis-para-cobran%C3%A7as-por-assinatura), [LGPD](https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm).
