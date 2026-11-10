# Contrato de suporte — ZVTech Checklist

Revisão 29/09/2026. Produto autenticado: checklist. Domínio: zvtech.com.br. Central administrativa: rqcode.com.br/suporte.

Fonte funcional do cliente: repositório Checklist, `refatoracao_laravel/docs/help/catalog.json` e seus 19 arquivos Markdown. Fonte técnica: `refatoracao_laravel/docs/SUPORTE_ONBOARDING_IMPLEMENTACAO.md`. Roteiro de aceite: `refatoracao_laravel/docs/SUPORTE_VALIDACAO_2026-09-29.md`.

Este documento não contém credenciais. Segredos permanecem nos envs privados do VPS. Não inferir regras do Fleetway, impressão 3D ou outros produtos a partir destas fontes.

O Checklist autentica usuários e empresas e mantém uma fila local; a RQCODE é o centro de atendimento e de edição/publicação de conhecimento. Chamados usam protocolos RQ-N; o identificador local permanece estável. O conector importa mensagens públicas, nunca notas internas.

Desde a publicação `20260929111651`, a RQCODE também administra planos e vínculo/ativação de contas. Contrato separado em `ADMINISTRACAO_INTEGRACAO.md`. O painel do produto mostra diagnóstico da fila de suporte e filtra chamados por conta. Permissões administrativas de suporte/conhecimento são reavaliadas no banco por requisição; revogar um administrador interrompe esse acesso sem depender de logout.

Ajuda e onboarding cobrem Motorista, Oficina, Empresa/Frota e papéis operacionais. Os quatro tipos de modelo são Simples, Total, Agrupado e Guiado; Relato Rápido e Relato em Campo são recursos pontuais distintos. Não anunciar envio recorrente agendado, IA generativa de suporte ou execução totalmente offline se a funcionalidade não consta no código e no artigo publicado.

O atendimento é assíncrono. Para repetir a integração em outro produto, criar segredo próprio, conector com identidade autenticada, catálogo revisado e testes de isolamento. Cadastrar apenas o nome do sistema não concede integração.
