# Operação de suporte e RAG da RQCODE

RAG significa *Retrieval-Augmented Generation* — em português, geração aumentada por recuperação. O agente não deve receber toda a documentação em cada conversa: ele recupera poucos trechos publicados, filtrados pelo sistema, idioma, público e tipo de documento.

## Contrato do agente

1. Identificar `system_id`, usuário, tenant e intenção antes de buscar conteúdo.
2. Recuperar apenas documentos `published` do mesmo sistema e público compatível.
3. Priorizar procedimentos e políticas sobre FAQs; usar a versão mais recente.
4. Nunca revelar notas `admin`, segredos, dados de outro tenant ou dados pessoais desnecessários.
5. Não inventar menus, preços, prazos, estados de conta ou ações concluídas.
6. Pedir somente os dados mínimos para diagnosticar; nunca solicitar senha, token ou chave.
7. Ações que alteram acesso, cobrança, exclusão ou dados precisam de autenticação e confirmação.
8. Se a evidência for insuficiente, registrar/encaminhar ticket com resumo, evidências e tentativas.

## Metadados obrigatórios

- Sistema, título, slug e idioma.
- Tipo: `guide`, `faq`, `troubleshooting`, `policy` ou `runbook`.
- Público: `customer`, `support`, `admin` ou `agent`.
- Estado: `draft`, `review`, `published` ou `archived`.
- Resumo de recuperação, tags, fonte verificável, versão e data de revisão.

## Tamanho recomendado

Um documento cobre uma intenção e deve preferencialmente ficar entre 250 e 900 tokens. Procedimentos longos devem ser divididos por tarefa. O título e o resumo precisam conter as palavras que o usuário realmente usaria.

## Ciclo editorial

`draft → review → published → archived`. Toda mudança funcional relevante invalida ou incrementa a versão. Tickets resolvidos recorrentes devem virar FAQ ou diagnóstico somente após revisão humana.
