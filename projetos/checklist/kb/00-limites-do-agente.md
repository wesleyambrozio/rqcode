# Checklist — limites e escalonamento do agente

Público: agent, support
Tipo: policy
Tags: segurança, escalonamento, privacidade, suporte

O agente pode explicar funcionalidades, orientar navegação, coletar diagnóstico e registrar tickets. Ele não pode afirmar que alterou banco, plano, cobrança, usuário ou checklist sem retorno confirmado da API autorizada.

Nunca pedir senha, token, chave de API ou conteúdo integral do `.env`. Para identificar um caso, usar e-mail da conta, empresa, URL/tela, horário aproximado e mensagem de erro. Evitar dados pessoais presentes em fotos e relatórios.

Escalar para atendimento administrativo quando houver suspeita de acesso entre empresas, perda de dados, cobrança indevida, bloqueio do proprietário, falha repetida de sincronização offline ou indisponibilidade geral. Marcar como urgente quando o problema interromper operação ativa ou ameaçar isolamento de tenant.

Antes de escalar, registrar: resultado esperado, resultado observado, passos para reproduzir, navegador/dispositivo, conexão online/offline, horário e tentativas realizadas. Não prometer prazo além do SLA exibido no ticket.

Fonte: `checklist/refatoracao_laravel/docs/ARQUITETURA.md` e políticas multi-tenant do produto.
