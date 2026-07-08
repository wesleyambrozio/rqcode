# Central SaaS

Central administrativa para acompanhar contas, usuários, pagamentos, vendas, comissões, financeiro, integrações e suporte de múltiplos SaaS.

## Primeira etapa entregue

- Dashboard executivo com contas, novos clientes, usuários ativos/online, pagamentos e chamados.
- Cadastros base: vendedores, sistemas, planos, vendas, lançamentos financeiros e integrações.
- Registro de comissões recorrentes ou avulsas.
- Financeiro com contas a pagar/receber, vencimentos, liquidação e status.
- Central de suporte unificada para visualizar chamados por sistema.
- SQL inicial para MariaDB/MySQL e Supabase/Postgres.
- Comando CLI para disparar aviso de vencimentos por e-mail.

## Instalação local

```bash
composer install
copy .env.example .env
php -S 127.0.0.1:8080 -t public
```

Acesse `http://127.0.0.1:8080`.

## Banco de dados

Use uma das migrations diretamente ou rode:

```bash
composer migrate-seed
```

Arquivos SQL disponíveis:

- MariaDB/MySQL: `database/migrations/001_central_saas_mysql.sql`
- Supabase/Postgres: `database/migrations/001_central_saas_postgres.sql`

Depois rode o seed correspondente em `database/seeds`.

## Próximas integrações recomendadas

- Sincronizar usuários/contas dos SaaS por webhook ou job agendado.
- Webhooks de pagamento para conciliação automática.
- SSO administrativo e RBAC por perfil.
- SLA de suporte, fila por prioridade e alertas.
- Auditoria completa de ações administrativas.
- Métricas SaaS: MRR, churn, LTV, CAC, inadimplência, expansão e contração.
