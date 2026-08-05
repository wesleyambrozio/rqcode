# Padrão oficial de desenvolvimento RQCODE

## Plataforma

- Backend: PHP 8.1 ou superior, Composer, front controller, serviços e controllers separados.
- Banco: MariaDB 10.6 ou superior, InnoDB, `utf8mb4`, migrations versionadas e seeds idempotentes.
- Frontend: HTML semântico e JavaScript progressivo; React pode permanecer durante migração, consumindo API PHP.
- Ambientes: local, homologação e produção com configurações isoladas.
- Deploy: script repetível, backup, migration, smoke test e rollback documentado.

## Produto obrigatório

- Visões comercial, operacional e administrativa.
- Multiempresa/tenant com isolamento por `tenant_id`.
- Administração central RQCODE de contas, usuários, planos, cobranças, suporte e indicadores.
- Pagamentos Asaas em sandbox e produção, com webhook validado, idempotência e conciliação.
- Suporte por IA com escalonamento humano e histórico auditável.
- Comandos de voz para navegação, filtros, criação, edição e acionamento de funções.
- Confirmação visual/verbal antes de exclusão, pagamento, envio externo ou alteração sensível.
- Catálogos completos para português brasileiro, inglês e espanhol.
- Tema claro/escuro, acessibilidade WCAG AA e experiência responsiva.
- PWA instalável e offline-first nas rotinas operacionais, com cache versionado, fila IndexedDB, sincronização ao reconectar, idempotência por requisição e indicador claro de pendências/falhas.

## Operação offline obrigatória

- Permitir leitura offline das telas operacionais previamente visitadas; informar quando uma tela nunca foi armazenada.
- Enfileirar mutações autorizadas no aparelho e sincronizar automaticamente ou por ação manual ao reconectar.
- Usar chave idempotente validada no servidor para que perda de resposta e repetição da fila não dupliquem dados.
- Preservar ordem, data, tenant, usuário e anexos compatíveis; sinalizar conflitos, sessão expirada e falhas permanentes sem descartar silenciosamente a alteração.
- Não prometer offline para login inicial, pagamentos externos, IA ou conteúdo nunca carregado.
- Limpar cache privado e fila no logout. Exigir HTTPS, política de armazenamento e orientação para bloqueio de tela em aparelhos compartilhados.
- Testar em aparelho real usando domínio público: 4G/5G, modo avião, criação offline, reconexão e confirmação de registro único.

## RQCODE Command Center

- Sidebar azul-marinho com identidade do produto, perfil e estado da sessão.
- Superfícies claras, respiro controlado e acento âmbar; modo escuro equivalente.
- Título de página forte, contexto temporal e ações primárias visíveis.
- KPIs compactos com valor, comparação, estado e destino acionável.
- Gráficos e alertas apenas quando ajudam uma decisão.
- Ícones vetoriais consistentes; não depender de emoji como iconografia final.
- Tabelas com busca, filtros, paginação, exportação e estados de carregamento/vazio/erro.

### Geometria e menu

- Definir um único token de espaçamento lateral por breakpoint e compartilhá-lo entre topbar, cabeçalhos de página, grids e listas.
- No mobile, alinhar as bordas das listas às bordas externas dos controles do cabeçalho; referência atual: `8px` de gutter. Em telas maiores, usar gutter fluido entre `14px` e `24px`.
- Não somar padding do shell com padding horizontal do conteúdo. O contêiner interno deve usar `padding-inline: 0` quando o shell já fornece o gutter.
- Usar sidebar persistente/recolhível no desktop e drawer completo no mobile. O menu hambúrguer deve abrir todas as opções disponíveis ao papel do usuário, com overlay, fechamento explícito e bloqueio do scroll do corpo.
- Manter logo/nome do cliente na primeira faixa do cabeçalho e controles de voz, tema e notificações com área mínima de toque de `44px`, mesmo tamanho e alinhamento.
- Não usar menu reduzido no rodapé como substituto do drawer completo. Atalhos inferiores são opcionais e nunca podem ocultar rotas autorizadas.

### Grids e listas

- Usar um componente/classe geral para todos os grids tabulares; não criar regras de overflow por tela.
- Aplicar `width: 100%`, `max-width: 100%`, `min-width: 0` e `box-sizing: border-box` nos contêineres e filhos de grid.
- Habilitar `overflow-x: auto` somente quando `scrollWidth > clientWidth + 3px`. Até `3px` são tolerância de bordas/arredondamento e não justificam barra de rolagem.
- Recalcular overflow com `ResizeObserver` quando a largura do contêiner ou tabela mudar. Sem suporte, recalcular no evento `resize`.
- Em desktop, exibir somente a tabela. Em mobile, exibir somente os cards resumidos. Nunca renderizar visualmente tabela e cards duplicados na mesma resolução.
- Alinhar a coluna de ações à direita, manter os botões na mesma linha no desktop e padronizar altura mínima, padding e largura mínima. Permitir quebra apenas no mobile quando necessária.
- Cards de lista devem mostrar somente identificação principal, estado e ações. Detalhes completos pertencem às telas `Ver`, `Editar` ou equivalente autorizado.
- Cards de totais/KPIs devem ter altura e alinhamento uniformes. Quando houver espaço, todos os totais do mesmo grupo ficam em uma única linha com colunas iguais; em telas menores, refluem sem criar scroll horizontal decorativo.
- Validar pelo menos uma lista ampla e uma estreita no navegador, registrando `clientWidth`, `scrollWidth`, estado de overflow e visibilidade exclusiva de tabela/cards.

### Cards

- Usar raio, borda, sombra, padding e superfície definidos por tokens do design system; não repetir estilos inline por cadastro.
- Manter cards do mesmo grupo com alturas coerentes e alinhamento vertical dos valores e ações.
- Evitar cards dentro de cards sem função semântica. Formulários de criação secundários devem abrir por ação explícita em modal ou página própria, sem ocupar permanentemente a listagem.
- Cards recolhidos devem reduzir à altura do cabeçalho/resumo, sem espaço vazio residual.
- Estados claro e escuro devem preservar contraste WCAG AA e a mesma hierarquia visual.

### Escala tipográfica

Usar tokens e uma hierarquia sem tamanhos arbitrários por tela:

| Uso | Tamanho | Peso/observação |
|---|---:|---|
| Título de página | `20px` (`1.25rem`) | 800, linha 1.2 |
| Título de card/seção | `16px` (`1rem`) | 700–800 |
| Corpo, formulário e célula | `14px` (`.875rem`) | linha 1.45–1.5 |
| Botão compacto | `13px` (`.8125rem`) | 600–700 |
| Cabeçalho de tabela e auxiliar | `12px` (`.75rem`) | 700–800 |
| Total/KPI compacto | `16px` (`1rem`) ou token de KPI | 700–900 |

- Valores de destaque podem usar tokens maiores de KPI, mantendo o mesmo tamanho entre cards equivalentes.
- Internacionalização não pode quebrar a hierarquia: permitir crescimento horizontal do botão ou truncamento acessível, sem reduzir a fonte isoladamente.
- Validar tamanhos computados no navegador, não apenas as classes declaradas, pois ordem de CSS e cache podem alterar o resultado.

## Segurança e dados

- Senhas com `password_hash`; sessões regeneradas; CSRF e cookies seguros.
- RBAC e autorização por recurso, além do isolamento de tenant.
- Rate limiting em login, recuperação, IA, contato e webhooks.
- Upload validado por MIME, extensão, tamanho e autorização de leitura.
- Segredos fora do Git, logs e documentos. Arquivos administrativos guardam somente referências do cofre.
- Logs de auditoria para autenticação e toda mutação administrativa.
- Dados demonstrativos explicitamente fictícios e restritos a local/homologação.
- Backup antes de migrations destrutivas e plano de rollback testado.

## Contrato com a Central RQCODE

Cada sistema deve expor ou sincronizar, no mínimo:

- identidade do sistema, versão, ambiente e saúde;
- tenants/contas, usuários e papéis;
- planos, assinaturas, faturas e inadimplência;
- métricas de uso e atividade;
- tickets, SLA e estado do suporte por IA;
- status do Asaas, SMTP, DNS, banco, deploy e último backup;
- eventos de auditoria relevantes.

Usar credencial de integração exclusiva por sistema, escopo mínimo, rotação e assinatura HMAC. Sincronizações devem ser incrementais e idempotentes.

## Critério de pronto

Um projeto só está pronto quando migrations e seed repetido passam, a conta demo autentica, três registros aparecem nas entidades aplicáveis, PT/EN/ES funcionam, comandos de voz essenciais funcionam, a Central RQCODE recebe os dados, build/testes passam, grids não exibem scroll fantasma, tabela/cards não aparecem duplicados, menu mobile contém todas as rotas autorizadas, tipografia computada segue os tokens, os scripts local/VPS foram verificados e o roteiro online/offline/reconexão foi aprovado em celular real fora da rede local.
