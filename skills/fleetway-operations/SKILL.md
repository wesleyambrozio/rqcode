---
name: fleetway-operations
description: Operar, validar, publicar e diagnosticar o Fleetway com segurança. Usar ao preparar servidor local, auditar i18n/segurança, criar commit GitHub, publicar no VPS, verificar produção, planejar rollback ou atualizar o registro administrativo do Fleetway no RQCODE.
---

# Operações Fleetway

## Regras

- Tratar `C:\desenvolvimento\fleetway` como aplicação e `C:\desenvolvimento\rqcode\projetos\fleetway` como registro administrativo.
- Usar o nome **Fleetway** em textos; usar `fleetway` em pasta/repositório e `FLEETWAY_*` em variáveis.
- Nunca ler segredos em voz alta, registrar valores de `.env`, copiar chaves ou incluir credenciais em Git.
- Executar diagnósticos remotos sem mutação antes de qualquer deploy.
- Exigir validação local, backup remoto e smoke test. Informar o caminho do backup para rollback.
- Não publicar, cobrar, excluir, rotacionar segredos nem alterar DNS sem autorização explícita.

## Fluxo local

1. Ler `references/operations.md`.
2. Conferir `git status` quando houver repositório.
3. Executar `validate.bat`.
4. Para desenvolvimento, executar `start-local.bat`.
5. Registrar resultado e pendências em `projetos/fleetway/STATUS.md`, sem segredos.

## Fluxo GitHub

1. Confirmar que o repositório privado já foi criado e obter a URL.
2. Executar `publish-github.bat -RepositoryUrl <URL>` sem `-Push`.
3. Revisar arquivos staged/commit e varrer segredos.
4. Somente com autorização, repetir com `-Push`.

## Fluxo VPS

1. Executar `scripts/health-check.ps1`.
2. Se a validação passar e houver autorização, executar `deploy-vps.bat`.
3. Confirmar `DEPLOY_OK`, testes de produção e caminho do backup.
4. Em falha, parar. Não improvisar correções destrutivas; propor rollback usando o backup gerado.

