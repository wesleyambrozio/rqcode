# Indicações do Checklist no RQCODE

Painel em **Administrar produtos → Checklist → Indicações e início de uso** (`/administracao-produtos?product=checklist#indicacoes`). Usa autorização administrativa já existente. Não cria login público nem permite que participantes vejam cadastros alheios.

Endpoint existente `/api/support/v1`, ação `referrals.sync`, versão 1. HMAC, identificação do produto, nonce antirreplay e transação seguem `SupportBridge`. Produto precisa estar habilitado para administração. A sincronização não altera planos nem chamados.

Migração aditiva `018_referrals_*`: `product_referral_state` e `product_referrers`. Implantar a migração antes dos arquivos do painel. O Checklist é fonte das contagens; a central mantém snapshots idempotentes por produto/usuário. Não somar contagens entre chamadas.

O resumo abrange novos cadastros desde a implantação, incluindo os sem indicação. Cada indicador tem cadastros atribuídos, e-mails confirmados e primeiro uso. Confirmação e primeiro uso são independentes. Busca por nome/código, páginas de 50 indicadores e até 100 segmentos por perfil/cidade/UF; localização opcional. Não é ranking público ou controle de pagamentos.

Última sincronização informa pendência após dez minutos. Lotes de 100 indicadores são rotativos; com base grande, as linhas podem ter atraso maior que o resumo. Falha da central não impede cadastro ou uso no Checklist. Após recuperação, próxima execução reenvia o snapshot.

Teste isolado: `php8.3 tests/referrals_test.php rqcode_referral_test_<id>`. Cria e remove somente o banco de teste nomeado. Cobre assinatura, replay, isolamento de produto, idempotência, limites, rollback de payload inválido e segmentos. Complementar com testes existentes de administração e ensaio HTTP Checklist → RQCODE.
