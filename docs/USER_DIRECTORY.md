# Diretório completo de usuários — 03/11/2026

O diretório `/usuarios-sistemas` recebe todos os vínculos retornados pelo snapshot autenticado do produto, incluindo inativos. Um usuário presente em várias contas aparece uma vez por vínculo. A administração de contas (`product_accounts`) é sincronizada separadamente por `central:sync` no ZVTech.

## Correção

O pull de usuários ficou sem resposta desde 13/10/2026: a VPS não alcança seu próprio IP público nessa conexão. `ProjectSnapshotClient` permite configurar `CHECKLIST_INTEGRATION_CONNECT_IP=127.0.0.1` somente no ambiente da VPS. O endereço continua `https://zvtech.com.br`: assinatura HMAC, hostname, SNI e validação do certificado são preservados. Redirecionamentos são recusados. Não desabilitar TLS nem alterar o firewall. Fora da VPS, deixar o IP vazio para usar DNS normal. Requer PHP com cURL.

O job `RQCODE_CHECKLIST_SYNC` usa PHP 8.3 e roda a cada 5 minutos com flock. Recuperação manual: `cd /var/www/rqcode.com.br && php8.3 bin/sync-projects.php checklist`. O upsert mantém IDs estáveis e não apaga registros históricos.

Uma segunda falha foi confirmada após restaurar a rede: o endpoint do ZVTech lia `env('RQCODE_INTEGRATION_SECRET')` diretamente, deixando de obter a chave com `config:cache` ativo. As duas chaves estavam configuradas e eram iguais. Correção no produto: `config/rqcode.php` carrega o segredo e o endpoint lê `config('rqcode.integration_secret')`; o cache foi regenerado, sem trocar a chave. Código publicado também na branch `production` do Checklist para persistir nos próximos releases.

## Ordenação

Nome, data de inclusão e último acesso têm filtros e cabeçalhos clicáveis, com ambas as direções e manutenção do filtro por plataforma. Padrão: inclusões mais recentes. Inclusão usa a data original do cadastro (fallback para registros legados). Último acesso é o último login recebido do produto, não a data de sincronização. Sem login registrado permanece ao fim em ambas as direções. SQL usa somente campos/direções permitidos e desempate por nome/ID. Última sincronização fica visível na tela.

## Validação

`php -r 'require "tests/system_user_directory_test.php";'`: 27 verificações de ordenação, isolamento de filtro, todas as situações de status, fallback, ausência de acesso, entradas malformadas/injeção, transporte/HMAC/IP, compatibilidade v1/v2 e falhas sem vazar segredos. Lint nos cinco PHP alterados. Conferir no servidor a paridade dos IDs de contas/vínculos com o snapshot após rodar a sincronização duas vezes. Não rodar migrations: a migração 019 já está aplicada em produção; foi apenas registrada no Git com o baseline existente.

Mudanças anteriores nos três arquivos publicados foram registradas num commit de baseline separado para preservar o comportamento já em produção. Auditoria do padrão: PHP/env/migrations/seeds/traduções/integração/voz presentes; lacunas preexistentes em deploy padronizado e offline-first, fora deste ajuste.

## Resultado em produção

- Antes: 103 vínculos, última sincronização em 13/10/2026. Depois: 23 contas e 107 usuários/vínculos.
- Paridade por IDs de todas as contas e de todos os vínculos: zero faltantes, zero extras. Nova execução preservou os IDs das linhas, sem duplicações.
- Rotina automática confirmada novamente em 03/11/2026 às 13:05:02 UTC (10:05:02 em São Paulo).
- Seis combinações de ordenação preservam todos os 107 registros. Snapshot assinado funciona com cache ativo; snapshot sem assinatura continua HTTP401; diretório sem sessão continua redirecionando ao login (HTTP302).
- RQCODE: commit de implementação `746dc0f`; baseline `0ac040b`. Checklist: implementação `78435da` mais ajuste do teste, publicado em production. Apenas cinco fontes centrais e dois arquivos do produto enviados; sem migrations.
- Testes RQCODE: 27 verificações. Checklist: 16 testes/57 assertions (`RqcodeSnapshotTest` e `CentralAdministrationTest`), SQLite isolado com fixtures e senha de teste; bootstrap redirecionou namespaces para o worktree, usando o vendor compartilhado. PHP lint OK.
- Prévia do mesmo componente com dados fictícios: clique no cabeçalho de último acesso produziu Carlos/Bruna/Ana (sem login ao fim); filtro por nome crescente produziu Ana/Bruna/Carlos e manteve a plataforma. Desktop 1280/1280px, tabela 1230/1230px; mobile 375/375px com scroll restrito à tabela (326/766px). Nenhum overflow no documento. A sessão administrativa real não estava aberta no navegador: a validação de dados reais foi feita no servidor, e a visual em fixture.
- Backup central: `/var/www/rqcode.com.br/backups/user-directory-20261103T125941Z` (fontes, .env e crontab anterior).
- Backup produto: `/var/www/zvtech.com.br/backups/directory-config-20261103T130317Z` (controller e cache de configuração anterior, diretório privado).
