# RQCode - Sistema de Gestao

Central administrativa para acompanhar contas, usuarios, pagamentos, vendas, comissoes, financeiro, integracoes e suporte de multiplos SaaS.

## Primeira etapa entregue

- Dashboard executivo com contas, novos clientes, usuarios ativos/online, pagamentos e chamados.
- Cadastros base: vendedores, sistemas, planos, vendas, lancamentos financeiros e integracoes.
- Registro de comissoes recorrentes ou avulsas.
- Financeiro com contas a pagar/receber, vencimentos, liquidacao e status.
- Central de suporte unificada para visualizar chamados por sistema.
- SQL inicial para MariaDB/MySQL e Supabase/Postgres.
- Comando CLI para disparar aviso de vencimentos por e-mail.

## Instalacao local

```bash
composer install
copy .env.example .env
php -S 127.0.0.1:8080 -t public
```

Acesse `http://127.0.0.1:8080`.

## Banco de dados

Configure o `.env` e rode:

```bash
composer migrate-seed
```

Arquivos SQL disponiveis:

- MariaDB/MySQL: `database/migrations/001_central_saas_mysql.sql`
- Supabase/Postgres: `database/migrations/001_central_saas_postgres.sql`

Login inicial do seed:

- E-mail: `admin@central.local`
- Senha: `Admin@123456`

Troque este usuario e senha antes de usar em producao.

## Deploy em VPS

Requisitos:

- PHP 8.1 ou superior.
- Composer.
- Extensao PDO do banco usado, normalmente `pdo_mysql` para MariaDB ou `pdo_pgsql` para Supabase/Postgres.
- Document root apontando para a pasta `public`.

Passos basicos:

```bash
git clone https://github.com/wesleyambrozio/rqcode.git
cd rqcode
composer install --no-dev --optimize-autoloader
cp .env.example .env
nano .env
composer migrate-seed
```

Agendamento sugerido para avisos de vencimento:

```cron
0 8 * * * cd /caminho/rqcode && php bin/due-alerts.php >> storage/logs/due-alerts.log 2>&1
```

## Proximas integracoes recomendadas

- Sincronizar usuarios/contas dos SaaS por webhook ou job agendado.
- Webhooks de pagamento para conciliacao automatica.
- SSO administrativo e RBAC por perfil.
- SLA de suporte, fila por prioridade e alertas.
- Auditoria completa de acoes administrativas.
- Metricas SaaS: MRR, churn, LTV, CAC, inadimplencia, expansao e contracao.
