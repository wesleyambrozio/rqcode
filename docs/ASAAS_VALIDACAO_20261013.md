# Validação da implantação — 13/10/2026

## Resultado

- Central: `https://rqcode.com.br`, Asaas produção autenticado, webhook de produção cadastrado e sandbox preservado com chave/token próprios.
- Checklist: `https://zvtech.com.br/plano-assinatura?plano=starter`. Preço Starter consultado no catálogo central: R$ 79,00/mês.
- Termos e privacidade publicados na versão `2026-10-13.billing.1`, com finalidade do CPF/CNPJ, compartilhamento com Asaas, conservação e direitos.
- Contratação real depende do preenchimento e confirmação do usuário no checkout. Não foram criados clientes ou contratos reais por esta implantação.

## Evidências

- 30 cenários da assinatura e 19 cenários de cobranças avulsas aprovados em SQLite isolado, com transporte simulado.
- Checklist: 25 testes, 181 verificações aprovadas, abrangendo BillingFlowTest, auditoria, cadastro, limites e isolamento das contas.
- Sandbox real: assinatura Starter R$ 79/mês criada, pagamento simulado, callbacks PAYMENT_CREATED e PAYMENT_RECEIVED recebidos, contrato ACTIVE e cancelamento confirmado como CANCELLED. O plano da conta real foi preservado.
- Produção: consulta Checklist → Central por HTTPS/HMAC aprovada; sync administrativo retornou 10 planos, 19 contas e zero falhas.
- Requisição de cobrança sem assinatura HMAC rejeitada com HTTP 401. Termos e privacidade responderam HTTP 200.
- Prévia visual com dados fictícios: em viewport móvel de 390px, clientWidth/scrollWidth 375/375; em desktop de 1280px, 1265/1265. Menu de motorista responsável mostrou Plano e assinatura; Atualizar status ficou disponível no celular. Título final medido em 20px, peso 800.
- Build de publicação preparado a partir das fontes vigentes do servidor com alterações de assinatura, preservando trabalho local não relacionado.

## Correções operacionais

O IP público da VPS estava bloqueado na cadeia MAILCOW. A comunicação interna usa RQCODE_CENTRAL_CONNECT_IP=127.0.0.1, com o domínio oficial, certificado HTTPS e HMAC preservados. Não foram alteradas as regras do Mailcow.

A janela do limitador passou a usar o relógio do banco, evitando contar nonces antigos como recentes quando PHP/FPM e banco usam fusos diferentes. Vencimentos e vigência comercial usam America/Sao_Paulo. A conciliação de produção está agendada a cada cinco minutos.

## Código e recuperação

- RQCODE: commits cb7e027, 455883c, 2d5cdb6 e d38e8b9, enviados para main.
- Checklist: commits 3a76a26, 435891b e 2fd7276, enviados para codex/recurring-billing; publicação cirúrgica sobre a release vigente.
- Backup inicial Central: `/var/www/rqcode.com.br/backups/recurring-20261013T215545Z`.
- Backup da correção de relógio: `/var/www/rqcode.com.br/backups/billing-clock-20261013T223205Z`.
- Backup inicial Checklist: `/var/www/zvtech.com.br/backups/billing-20261013T215545Z`.
- Backup da conexão interna: `/var/www/zvtech.com.br/backups/billing-network-20261013T220049Z`.
- Backup da última publicação visual: `/var/www/zvtech.com.br/backups/billing-ui-20261013T224959Z`.

Arquivos de credenciais permanecem no ambiente e backups privados; valores não constam deste registro. A primeira contratação e o primeiro pagamento real ainda devem ser acompanhados pelo responsável.
