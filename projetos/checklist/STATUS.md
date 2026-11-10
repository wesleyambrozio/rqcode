# Status — Checklist

## Situação vigente em 29/09/2026

- [x] Publicado em `https://zvtech.com.br` (Laravel/PostgreSQL no VPS).
- [x] Suporte integrado à RQCODE: chamados, mensagens públicas, notas internas privadas, artigos versionados e recuperação de falhas.
- [x] Administração de planos, recursos/limites e vínculo/ativação de contas pela RQCODE, com aplicação confirmada e auditoria.
- [x] 10 planos existentes e 16 contas importados sem modificar condições; modelos/execuções preservados por comparação de hashes.
- [x] Indicadores de contas/uso/suporte no painel Administrar produtos e dashboard central, sem duplicar snapshots antigos do mesmo produto.
- [ ] Cobrança e assinaturas automáticas Asaas (não implantadas).
- [ ] Suporte com IA generativa (a busca atual é documental, não generativa).

Fontes canônicas: `ADMINISTRACAO_INTEGRACAO.md` e `SUPORTE_INTEGRACAO.md`; roteiro de testes e evidências no projeto Checklist em `refatoracao_laravel/docs/ADMINISTRACAO_RQCODE.md`. Não usar as anotações históricas abaixo para afirmar estado atual de produção.

## Histórico de preparação local (anterior à publicação atual)

- [x] MariaDB local exclusivo
- [x] Migrations e seed idempotente
- [x] Conta demonstrativa local
- [x] Três registros nas entidades operacionais principais
- [x] Dashboard RQCODE Command Center
- [x] Voz e seletor PT-BR/EN/ES
- [x] Integração de métricas com a Central RQCODE
- [x] Build e 24 testes automatizados
- [ ] Migrar textos restantes das telas internas para catálogos i18n
- [ ] Configurar Asaas e suporte IA com credenciais do cofre
- [ ] Homologar e implantar no VPS

## Suporte central (30/08/2026)

- [x] Quatro documentos iniciais de suporte/RAG curados e importados no RQCode
- [x] Políticas de limite, privacidade e escalonamento do agente
- [x] Estrutura central para tickets, SLAs e diretório de usuários
- [ ] Publicar eventos de ticket e sincronização incremental de usuários/tenants
- [ ] Validar isolamento multi-tenant e fluxos críticos no ambiente candidato
