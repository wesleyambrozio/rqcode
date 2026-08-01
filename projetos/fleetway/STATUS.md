# Status executivo — Fleetway

## Já existente

- Multi-tenant, RBAC e quatro painéis.
- Português, inglês e espanhol.
- Design system RQCODE e comandos de voz documentados.
- Estrutura de gateways de pagamento e suporte com base de conhecimento.
- Scripts de segurança, smoke tests e configuração Nginx.

## Bloqueadores para operação comercial segura

1. Criar repositório privado `fleetway` no GitHub e habilitar proteção da branch `main`.
2. Rotacionar todas as credenciais presentes no arquivo legado e remover o arquivo após backup seguro aprovado.
3. Substituir logins demonstrativos previsíveis em produção e confirmar `APP_DEBUG=false`.
4. Validar ponta a ponta Asaas: assinatura, webhook, idempotência, estorno e conciliação no RQCODE.
5. Integrar Fleetway ao administrativo RQCODE por API/webhooks com autenticação de serviço e trilha de auditoria.
6. Definir backups automáticos de banco/uploads e testar restauração.
7. Executar teste comercial, acessibilidade, responsividade e fluxos nos três idiomas.

## Resultado técnico desta rodada

- Build Vite: aprovado.
- Testes de segurança: 69 aprovados, 0 falhas, incluindo cofre de credenciais, PIX e rate limit.
- Banco local: MariaDB 11.4 em `127.0.0.1:3307/fleetflow_local`; 25 migrations e seed executados.
- Idiomas: o verificador principal registra 345/345 chaves nos três idiomas, mas o scanner amplo encontra 8 lacunas em PT-BR/ES e 9 em EN; unificar os scanners antes do lançamento.
- PHP local: Sodium habilitado; extensões inexistentes de Interbase, Oracle, DB2 e Informix desativadas. Backup do `php.ini` preservado com sufixo `rqcode-backup-20260801`.
- Produção: HTTPS 200, SSH funcional, Nginx/PHP-FPM ativos e 40% do disco utilizado.
- Integração local Fleetway → RQCODE: HMAC aprovado; requisição sem assinatura retorna 401 e snapshot assinado gravou métricas no RQCODE.
- Git Fleetway: repositório local criado no commit `8fccef2`, origin configurado para `https://github.com/wesleyambrozio/fleetflow-hub.git`; push bloqueado por autenticação GitHub inválida nesta máquina.

## Decisão de arquitetura

O RQCODE deve ser o plano de controle: catálogo de sistemas, contas, planos, cobranças, suporte, usuários administrativos e métricas. O Fleetway continua responsável pelo domínio de frota. Evitar acesso direto do RQCODE às tabelas internas do Fleetway; usar API versionada e webhooks assinados.
