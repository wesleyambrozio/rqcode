# Plano de finalização — Central de suporte RQCODE

Atualizado em 30/08/2026.

## Objetivo

Usar o RQCode como plano de controle de todos os produtos: sistemas, empresas, usuários, saúde, tickets, SLAs, documentos, integrações e indicadores. Cada produto permanece como fonte oficial do próprio domínio; a central não consulta diretamente tabelas internas.

## Entregue nesta etapa

- Acervo administrável de conhecimento por sistema, tipo, público, idioma, estado, fonte e versão.
- Importação idempotente dos documentos versionados em `projetos/<sistema>/kb`.
- Oito documentos iniciais publicados para Checklist e BIELFRA.
- Fila de tickets com categoria, prioridade, prazo da primeira resposta, responsável, estados, respostas e notas internas.
- Diretório central de usuários por produto/tenant, preparado para sincronização.
- Contrato anti-alucinação, proteção de dados e regra de escalonamento do agente.

## Decisões técnicas

1. Integrações server-to-server usam API versionada, HMAC/webhook, timestamp, idempotência e auditoria.
2. RQCode armazena uma projeção administrativa; escrita no domínio do produto ocorre somente por API autorizada.
3. A recuperação do RAG filtra `system_id + status=published + audience + language`; depois ranqueia por intenção/tags.
4. Começar com busca lexical curada. Adicionar embeddings somente quando o acervo justificar, mantendo filtro relacional antes da busca vetorial.
5. Tickets são a saída segura quando a confiança ou a evidência forem insuficientes.

## Próximas entregas, em ordem

### P0 — antes do primeiro cliente

- Trocar a senha seed do RQCode, configurar produção, HTTPS, backup e restore testado.
- Concluir no BIELFRA: identidade jurídica, SMTP real, estratégia de cobrança, HTTPS do app, migrations 055–057, auditoria sem dados demo e validação física PWA/mobile.
- Homologar o Checklist PHP no VPS, executar testes de isolamento multi-tenant e validar backup/restore.
- Criar endpoints de eventos/tickets/usuários nos dois produtos e autenticação de serviço com rotação de segredo.
- Aplicar RBAC no RQCode (`owner`, `support`, `finance`, `accountant`, somente leitura) e auditoria das mudanças novas.

### P1 — operação assistida

- Sincronização incremental de tenants e usuários com cursor e `last_seen_at`.
- Portal de suporte embutido em cada produto, autenticado pelo usuário local; RQCode recebe o ticket sem compartilhar sessão.
- Alertas de SLA, falha de integração e indisponibilidade; dashboard de saúde e última sincronização.
- Tela de edição completa, pré-visualização Markdown e histórico imutável de versões dos documentos.
- Busca híbrida com citações do documento/versão e avaliação de respostas.

### P2 — escala

- Resumos automáticos de ticket revisados por humano, sugestão de resposta e detecção de duplicados.
- Conversão assistida de resoluções recorrentes em FAQ rascunho.
- SSO administrativo com MFA/passkeys, trilha de auditoria exportável e políticas de retenção/LGPD.
- Métricas: tempo de primeira resposta, resolução, reabertura, cobertura documental, taxa de escalonamento e satisfação.

## Critério de produção

Nenhum produto é declarado pronto apenas por build/teste local. A liberação exige evidência do ambiente: HTTPS, segredos, migrations, backup e restore, isolamento de tenant, e-mail, cobrança escolhida, observabilidade, fluxo crítico e teste físico mobile/PWA quando aplicável.
