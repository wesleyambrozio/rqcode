# FLEETWAY — QUADRO DE EXECUÇÃO

Data de início: ____/____/______   Meta de lançamento: ____/____/______

## 1. Segurança primeiro

- [ ] Rotacionar credenciais do arquivo legado
- [ ] Adotar gerenciador de senhas e MFA
- [ ] Confirmar `APP_DEBUG=false` em produção
- [ ] Testar backup e restauração

## 2. Fonte da verdade

- [ ] Criar repositório GitHub privado `fleetway`
- [ ] Executar `publish-github.bat -RepositoryUrl <URL>`
- [ ] Revisar commit e executar novamente com `-Push`
- [ ] Proteger branch `main` e configurar CI

## 3. Produto comercial

- [ ] Onboarding e teste grátis completos
- [ ] Asaas completo e conciliado
- [ ] Suporte IA com escalonamento humano
- [ ] PT-BR, EN e ES completos
- [ ] Voz com confirmação segura
- [ ] UX mobile/desktop aprovada

## 4. RQCODE central

- [ ] Contas, usuários e planos sincronizados
- [ ] Cobranças e inadimplência centralizadas
- [ ] Suporte e SLA centralizados
- [ ] Auditoria e métricas executivas

## 5. Lançamento

- [ ] Rodar `validate.bat`
- [ ] Rodar `deploy-vps.bat`
- [ ] Smoke test e rollback conferidos
- [ ] Monitoramento, alertas e suporte de lançamento

Regra diária: escolher 3 entregas, concluir antes de abrir novas frentes.

