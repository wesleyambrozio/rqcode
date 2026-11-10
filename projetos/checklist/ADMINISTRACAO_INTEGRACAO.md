# Administração integrada do ZVTech Checklist

Revisão 29/09/2026. Fonte técnica canônica: `C:/desenvolvimento/checklist/refatoracao_laravel/docs/ADMINISTRACAO_RQCODE.md`.

Entrada: `/administracao-produtos?product=checklist`, menu **Administrar produtos**. Produto habilitado explicitamente em `product_admin_state`. O cadastro `/sistemas` permanece para produtos sem conector; não criar um segundo catálogo manual para o Checklist.

RQCODE administra planos (incluindo recursos/limites) e vínculo/ativação de contas. Checklist aplica e confirma revisões, mantém dados operacionais e publica contagens. Atualizações assíncronas, com filas limitadas: verificar **Aplicado**, não apenas a mensagem de salvamento.

Chamados, artigos e anexos privados continuam no contrato `SUPORTE_INTEGRACAO.md`. O novo painel oferece filtros por cliente, diagnóstico de sincronização e atalhos para atendimento e conhecimento. Permissões administrativas são reavaliadas em cada requisição.

**Não afirmar:** que Asaas está conectado, que mudar preço gera cobrança, que há presença online em tempo real, que é possível excluir clientes ou trocar senhas por este conector, ou que todos os demais produtos já estão integrados. Nenhuma dessas funções foi implantada nesta entrega.

Tabelas novas: `product_admin_state`, `product_accounts`, `product_admin_audit`; `plans` recebe identificação externa, regras e revisões. Migração 017 MySQL/PostgreSQL. Implementação verificada em MySQL (central) e PostgreSQL (Checklist); variante PostgreSQL da central não foi exercitada nesta publicação.

Testes: `tests/product_administration_test.php` e `tests/product_administration_http_test.php` exigem ambientes isolados. Nunca rodá-los contra produção. Manter os testes de suporte ao evoluir o protocolo compartilhado.

Próximas etapas possíveis na RQCODE, ainda não executadas: cobrança/assinaturas Asaas com webhooks idempotentes; política comercial de transição dos planos antigos; conectores específicos dos demais produtos; restauração integral de backup em homologação e monitoramento externo da disponibilidade.
