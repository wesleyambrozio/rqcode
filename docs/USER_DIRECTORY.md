# Diretório completo de usuários — 03/11/2026

O diretório `/usuarios-sistemas` recebe todos os vínculos retornados pelo snapshot autenticado do produto, incluindo inativos. Um usuário presente em várias contas aparece uma vez por vínculo. A administração de contas (`product_accounts`) é sincronizada separadamente por `central:sync` no ZVTech.

## Correção

O pull de usuários ficou sem resposta desde 13/10/2026: a VPS não alcança seu próprio IP público nessa conexão. `ProjectSnapshotClient` permite configurar `CHECKLIST_INTEGRATION_CONNECT_IP=127.0.0.1` somente no ambiente da VPS. O endereço continua `https://zvtech.com.br`: assinatura HMAC, hostname, SNI e validação do certificado são preservados. Redirecionamentos são recusados. Não desabilitar TLS nem alterar o firewall. Fora da VPS, deixar o IP vazio para usar DNS normal. Requer PHP com cURL.

O job `RQCODE_CHECKLIST_SYNC` usa PHP 8.3 e roda a cada 5 minutos com flock. Recuperação manual: `cd /var/www/rqcode.com.br && php8.3 bin/sync-projects.php checklist`. O upsert mantém IDs estáveis e não apaga registros históricos.

## Ordenação

Nome, data de inclusão e último acesso têm filtros e cabeçalhos clicáveis, com ambas as direções e manutenção do filtro por plataforma. Padrão: inclusões mais recentes. Inclusão usa a data original do cadastro (fallback para registros legados). Último acesso é o último login recebido do produto, não a data de sincronização. Sem login registrado permanece ao fim em ambas as direções. SQL usa somente campos/direções permitidos e desempate por nome/ID. Última sincronização fica visível na tela.

## Validação

`php -r 'require "tests/system_user_directory_test.php";'`: 27 verificações de ordenação, isolamento de filtro, todas as situações de status, fallback, ausência de acesso, entradas malformadas/injeção, transporte/HMAC/IP, compatibilidade v1/v2 e falhas sem vazar segredos. Lint nos cinco PHP alterados. Conferir no servidor a paridade dos IDs de contas/vínculos com o snapshot após rodar a sincronização duas vezes. Não rodar migrations: a migração 019 já está aplicada em produção; foi apenas registrada no Git com o baseline existente.

Mudanças anteriores nos três arquivos publicados foram registradas num commit de baseline separado para preservar o comportamento já em produção. Auditoria do padrão: PHP/env/migrations/seeds/traduções/integração/voz presentes; lacunas preexistentes em deploy padronizado e offline-first, fora deste ajuste.
