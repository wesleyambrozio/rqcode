---
name: rqcode-project-standard
description: Auditar, criar, migrar ou evoluir produtos da RQCODE segundo o padrão oficial de arquitetura, segurança, UI/UX, administração central, voz, internacionalização, pagamentos e operação. Usar em todo projeto RQCODE novo ou existente, especialmente ao padronizar PHP 8.1+, MariaDB, dashboards, integrações com o administrativo RQCODE, deploy local/VPS e documentação operacional.
---

# Aplicar o padrão RQCODE

1. Ler `references/standard.md` antes de alterar arquitetura, banco, autenticação, UI, voz, pagamentos ou integração central.
2. Inventariar stack, branch, alterações locais, banco, rotas, autenticação, papéis, segredos e deploy. Preservar trabalho não relacionado.
3. Executar `scripts/audit-project.ps1 -ProjectPath <caminho>` e registrar lacunas.
4. Preferir PHP 8.1+ com Composer e MariaDB 10.6+. Em migrações, criar transição incremental e reversível; não apagar a implementação anterior antes de validar paridade.
5. Implementar isolamento multiempresa, RBAC, CSRF, rate limit, auditoria e armazenamento seguro desde a fundação.
6. Aplicar o RQCODE Command Center conforme a seção UI/UX de `references/standard.md`: menu completo em drawer no mobile, gutters compartilhados, hierarquia tipográfica por tokens, KPIs compactos, cards de lista resumidos, grids sem scroll fantasma, estados vazios úteis e temas claro/escuro.
7. Entregar voz para navegação, busca, criação e edição com confirmação em ações destrutivas.
8. Entregar PT-BR, EN e ES sem textos de interface fixos fora dos catálogos de tradução.
9. Integrar contas, usuários, planos, cobrança, suporte, saúde e métricas ao administrativo RQCODE por API autenticada ou sincronização idempotente.
10. Criar `.env.example`, scripts locais/deploy, migrations e seed demonstrativo idempotente com no mínimo três registros por entidade operacional aplicável.
11. Implementar PWA offline-first: cache versionado das áreas operacionais, fila persistente de mutações, sincronização, idempotência no backend, indicador de estado e limpeza de dados privados no logout.
12. Validar lint, migrations, seed repetido, testes, build, login, dashboard, grids em larguras larga/estreita e cenário online/offline/reconexão em celular real fora da rede local. Medir alinhamento e overflow no navegador; nunca afirmar conclusão sem evidência.
13. Manter segredos somente no cofre ou ambiente. Em documentação, armazenar apenas a referência do segredo e o procedimento de rotação.

Se um requisito não puder ser concluído com segurança, registrar estado, evidência, bloqueio e próximo comando exato; não mascarar migração parcial como conclusão.
