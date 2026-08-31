# Checklist — diagnóstico de uso offline

Público: customer, support
Tipo: troubleshooting
Tags: offline, sincronização, fila, pwa, conexão

Primeiro confirmar se o usuário está na implementação PHP atual e se o aplicativo foi instalado/aberto anteriormente com internet. Coletar dispositivo, navegador, horário, ação realizada e estado da conexão.

Não orientar repetição da mesma alteração enquanto ela estiver na fila; isso pode gerar duplicidade. Ao reconectar, manter a aplicação aberta e verificar o indicador de sincronização. Se persistir, registrar a quantidade aproximada de itens pendentes e a última mensagem exibida, sem apagar cache ou dados do navegador antes da análise.

Escalar quando a fila permanecer pendente após conexão estável, houver conflito não resolvido ou risco de perda. A validação física final em produção continua sendo requisito de lançamento e não deve ser declarada concluída apenas por teste automatizado.

Fonte: padrão PWA RQCODE e estado de produção do Checklist.
