# RQCODE: operação do suporte central

Revisão: 29/09/2026. Fonte: controllers Support/Knowledge, serviços SupportBridge/KnowledgePublisher e migração 016.

## Rotina de quem atende sozinho

1. Abrir Suporte; filtrar produto, estado ou fila. A página limita a 100 chamados, priorizando estados abertos e impactos maiores.
2. Conferir protocolo, cliente e contexto. Uma captura de tela autorizada pode ser baixada em acesso autenticado.
3. Escrever resposta pública para o usuário. Marcar Nota interna somente quando o texto não deve sair da central.
4. Resposta pública muda o estado para waiting_customer e registra primeira resposta. Mensagem do cliente reabre o atendimento. Encerrar/resolver quando concluído.
5. Se a dúvida for recorrente, revisar o artigo em Conhecimento: manter fonte verificável, perfis e permissão corretos; publicar após revisão.

Somente owner/admin acessa estes módulos. Conta de contabilidade ou outra função não ganha acesso aos chamados. Mensagens são assíncronas: o aplicativo consulta a central periodicamente. E-mails ao responsável e ao solicitante são disparados pelo conector do produto; não prometem presença online.

Os prazos configurados nas filas são horas corridas e metas internas. Não significam compromisso comercial publicado. Primeira resposta atrasada considera first_responded_at, não apenas a situação atual do chamado.

## Base de conhecimento sem respostas inventadas

Cada documento tem produto, público (cliente/suporte/admin/agente), estado, versão, fonte e revisão. Somente published + customer + pt-BR sai pela API. Perfis e permissões restringem a exibição no aplicativo.

Editar gera snapshot imutável; edição simultânea com versão antiga é recusada. Histórico permite consultar as revisões anteriores (até 30 por visualização), sem apagar o restante. Reverter conteúdo é uma nova revisão, não alteração do histórico.

Artigos do Checklist são inicialmente importados de seu manifesto pelo comando:

`php bin/import-support-catalog.php /var/www/zvtech.com.br/current/docs/help`

Importação repetida não gera versões desnecessárias. Conteúdo editado manualmente na central é preservado pelo importador para revisão humana. O importador antigo não republica o antigo acervo do Checklist depois da adoção do catálogo curado. Outros produtos não são alterados.

Configurações antigas de provedor de IA continuam disponíveis em uma seção avançada, mas não conectam um modelo ao Checklist nesta fase. A busca é documental, e ausência de fonte suficiente deve encaminhar ao atendimento. Não afirmar funções de outro produto com base em um artigo do Checklist.

## Conector e fronteiras de confiança

Endpoint POST `/api/support/v1`, separado da sessão administrativa. Autenticação HMAC com timestamp e nonce, controle de replay, limite por produto, idempotência e auditoria. Ações disponíveis: health, ticket.create, ticket.message, ticket.read e knowledge.list.

`SUPPORT_PRODUCT_KEYS` no .env contém um objeto JSON de produto para segredo de pelo menos 32 caracteres. O Checklist usa slug `checklist`. Valor real nunca entra no repositório. Sessão administrativa e API têm mecanismos distintos: a API não aceita o login do navegador como credencial.

Contrato do chamador: autenticar usuário e empresa, obter identidade do servidor, não confiar em tenant_id/user_id fornecidos pelo navegador. O bridge valida produto + tenant + solicitante em cada leitura ou mensagem.

Dados de anexos não aparecem em knowledge.list/ticket.read. Download exige autenticação administrativa e é auditado; resposta no-store, nosniff e Content-Disposition attachment. Texto é escapado, sem executar HTML/Markdown do usuário.

## Operação e limites

Backup diário dos bancos e arquivos/envs privados deve incluir as novas tabelas support_* e knowledge_document_versions. Cópias fora do VPS e ensaio de restauração continuam indispensáveis; esta entrega gera backup pré-publicação, não substitui uma política completa de disaster recovery.

Auditoria contém identificadores, ação, produto e data. Nenhum dump, segredo, email ou imagem deve ser publicado na documentação. Retenção/exclusão de dados deve ser definida pelo responsável e aplicada nos dois produtos; não há expurgo automático de conversas nesta fase.

Catálogo Checklist: 19 artigos. Documentação técnica do conector: `projetos/checklist/SUPORTE_INTEGRACAO.md`. Produtos sem conector próprio não são automaticamente integrados só porque existem na lista de sistemas.

Verificação automatizada: `php tests/support_bridge_test.php rqcode_support_test_<identificador>` em checkout de teste com MySQL dedicado; o script cria e remove apenas o banco cujo nome usa esse prefixo. Nunca apontar testes destrutivos ao banco central_saas.
