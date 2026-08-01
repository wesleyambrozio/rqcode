# Fleetway — administração RQCODE

Nome canônico: **Fleetway**. Identificadores técnicos: `fleetway` (repositório/pasta), `fleetway.com.br` (produção) e `FLEETWAY_*` (variáveis operacionais).

Esta pasta é o registro administrativo do projeto. Não armazenar senhas, tokens, chaves privadas ou conteúdo de `.env`. Registrar somente o local seguro onde o segredo está guardado, responsável e data de rotação.

## Estado verificado em 30/07/2026

- Aplicação local: `C:\desenvolvimento\fleetway`.
- Produção: `https://fleetway.com.br`, VPS `root@fleetway.com.br`, `/var/www/fleetway.com.br`.
- SSH por chave: funcional; Nginx e PHP-FPM ativos.
- Git: ausente na pasta local e na pasta de produção.
- Credenciais legadas: removidas do projeto e preservadas temporariamente com criptografia EFS em `C:\Users\Micro\.rqcode-vault\fleetway-legacy.txt`; concluir rotação antes de eliminar a cópia.
- Convenção incorreta encontrada: o arquivo vazio `FleetWay` foi removido; o nome canônico permanece Fleetway.
- OpenClaw: configuração localizada em `\\wsl.localhost\Ubuntu\home\micro\.openclaw`.

## Arquivos

- `STATUS.md`: diagnóstico e próximos marcos.
- `ACESSOS.template.md`: inventário seguro, sem segredos.
- `ROADMAP.md`: escopo comercial e técnico.
- `CHECKLIST-MESA.md`: acompanhamento diário resumido.
