# Padrão de Desenvolvimento RQCODE

Este documento é o contrato técnico obrigatório para todos os produtos RQCODE. A fonte operacional detalhada está em `skills/rqcode-project-standard/references/standard.md`.

## Regra permanente

Todo produto será construído ou migrado para PHP 8.1+ e MariaDB, administrável pela Central RQCODE, multiempresa, responsivo, seguro, comercial, offline-first e internacionalizado em português, inglês e espanhol. Também deverá oferecer comandos de voz para navegação e manutenção de dados, pagamentos Asaas, suporte por IA, visão administrativa e visão comercial.

## Entrega mínima

1. Dashboard RQCODE Command Center.
2. Autenticação, RBAC, auditoria e isolamento por tenant.
3. MariaDB com migrations e seed local idempotente.
4. Conta demonstrativa e pelo menos três registros por entidade operacional aplicável.
5. Voz para navegar, consultar, criar e editar, com confirmação de ações sensíveis.
6. PT-BR, EN e ES completos.
7. Integração central de usuários, planos, cobrança, suporte e métricas.
8. `.bat` local, script de deploy VPS, health check, backup e rollback.
9. Segredos somente em ambiente/cofre; documentação contém apenas referências.
10. Testes e evidências antes de declarar conclusão.
11. PWA instalável com leitura offline, fila persistente de alterações, sincronização, idempotência e teste em celular real usando 4G/5G, modo avião e reconexão.

## Padrão visual obrigatório

1. Usar um único gutter compartilhado pelo cabeçalho e conteúdo: `8px` no mobile e `14px–24px` nas telas maiores.
2. Menu completo em sidebar no desktop e drawer acionado pelo hambúrguer no mobile; todas as rotas autorizadas devem permanecer acessíveis.
3. Exibir tabelas no desktop e cards resumidos no mobile, nunca os dois ao mesmo tempo.
4. Cards de listas mostram identificação, estado e ações; detalhes são abertos por `Ver` ou `Editar`.
5. Grids usam configuração geral e só mostram rolagem horizontal com excesso real superior a `3px`, recalculado ao redimensionar.
6. Botões de ações permanecem alinhados na mesma linha no desktop e usam dimensões consistentes.
7. Cards de totais usam colunas iguais e uma única linha quando houver espaço, sem gerar scroll decorativo.
8. Escala tipográfica: página `20px`, seção/total `16px`, corpo/célula `14px`, botão `13px`, cabeçalho/auxiliar `12px`.
9. Validar medidas, fontes computadas, overflow e exclusividade tabela/cards diretamente no navegador antes do deploy.

Os detalhes implementáveis e critérios de teste estão em `skills/rqcode-project-standard/references/standard.md`.
