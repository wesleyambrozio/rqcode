# Referência operacional

## Endereços

- Aplicação local: `C:\desenvolvimento\fleetway`
- Administração: `C:\desenvolvimento\rqcode\projetos\fleetway`
- Produção: `https://fleetway.com.br`
- SSH: `root@fleetway.com.br`
- Caminho remoto: `/var/www/fleetway.com.br`

## Comandos seguros

```powershell
cd C:\desenvolvimento\fleetway
.\validate.bat
powershell -NoProfile -File C:\desenvolvimento\rqcode\skills\fleetway-operations\scripts\health-check.ps1
```

## Critérios de liberação

- Build sem erro.
- Auditoria de idiomas sem lacunas bloqueadoras.
- Testes de segurança e smoke locais aprovados.
- SSH, serviços e espaço em disco saudáveis.
- Backup remoto criado antes da cópia.
- `.env`, uploads, logs e chaves fora do pacote.
- Smoke test de produção aprovado.

## Rollback

O script de deploy informa `/var/backups/fleetway/<timestamp>/app.tgz`. Preparar comando de restauração específico para esse arquivo e pedir autorização antes de sobrescrever produção.

