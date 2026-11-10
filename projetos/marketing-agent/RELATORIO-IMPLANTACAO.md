# Relatório de implantação — Agente Autônomo de Marketing RQCode

Data-base: 03/09/2026  
Primeiro produto: ZVTech Checklist

## 1. Recomendação executiva

A melhor primeira versão não é um robô com liberdade total para publicar e gastar. É uma **operação semiautônoma com aprovação humana**, capaz de pesquisar pautas, criar um lote semanal, adaptar formatos, agendar, medir resultados e propor melhorias. Publicação paga, preço, promessa de produto, depoimento e resposta sensível permanecem sob aprovação.

O repositório mostra que o Checklist já possui build, testes, demonstração local e integração de métricas. Porém, ainda faltam homologação/VPS, validação multi-tenant e algumas credenciais. Portanto, o marketing deve começar agora pela fundação e pelo conteúdo educativo, enquanto oferta e demonstração são preparadas; a mídia de conversão começa após o P0 do checklist.

Objetivo dos primeiros 90 dias: criar um canal previsível de **lead qualificado -> demonstração -> proposta -> venda**, e não apenas aumentar seguidores.

## 2. O que será construído

### Núcleo operacional

1. **Base de verdade:** produto, público, oferta, preços, objeções, provas, identidade e limites de comunicação.
2. **Planejador:** calendário mensal e pautas semanais ligadas a dores reais e estágio do funil.
3. **Estúdio:** roteiros, legendas, imagens, carrosséis, narração e montagem de vídeos curtos.
4. **Controle de qualidade:** fatos, marca, LGPD, direitos autorais, promessas e duplicidade.
5. **Aprovação:** painel/fila com prévia, edição, aprovação, rejeição e justificativa.
6. **Publicação:** agendamento por plataforma autorizada, com UTM, idempotência e registro de retorno.
7. **Mensuração:** alcance, retenção, engajamento, clique, lead, demonstração, venda, CAC e receita atribuída.
8. **Otimização:** relatório semanal, reaproveitamento de vencedores e pausa do que não funciona.

### Arquitetura de fácil manutenção

```text
Conhecimento aprovado
        |
Orquestrador -> Planejador -> Geradores (texto/imagem/vídeo)
        |                         |
        +----------> Validador ---+
                         |
                    Fila de aprovação
                         |
                 Adaptadores de publicação
                         |
        Instagram / Facebook / LinkedIn / YouTube / TikTok
                         |
                 Métricas + CRM + relatório
```

Os fornecedores ficam isolados por adaptadores (`text_provider`, `image_provider`, `video_provider`, `publisher`, `analytics`). Isso é especialmente importante porque APIs e políticas mudam: a própria documentação atual marca a API Sora como descontinuada em 24/09/2026. O sistema não deve depender de um único gerador de vídeo.

### Estrutura técnica proposta

```text
marketing-agent/
  config/          # canais, limites, horários e fornecedores
  knowledge/       # mensagens e fatos aprovados, versionados
  campaigns/       # objetivos, público, oferta, orçamento e UTMs
  templates/       # briefing, roteiro, legenda, carrossel e relatório
  workflows/       # planejar, produzir, revisar, aprovar, publicar, medir
  providers/       # adaptadores de IA, mídia e redes sociais
  policies/        # marca, LGPD, segurança e aprovação
  storage/         # ativos, versões, retornos e métricas
  tests/           # contratos dos adaptadores e fluxos críticos
  runbooks/        # falha, pausa, token expirado e incidente
```

## 3. Cadência inicial recomendada

- 3 publicações por semana em até 3 canais.
- 1 vídeo curto, 1 carrossel e 1 post de prova/objeção por semana.
- Stories derivados das peças principais, sem exigir uma produção separada.
- 20 minutos úteis por dia para interação humana.
- 60–90 minutos por semana do proprietário para aprovação e decisões.
- Relatório toda segunda-feira: resultados da semana anterior e próximos testes.

No primeiro mês, o agente produz um lote maior do que publica. Isso cria reserva editorial e permite descartar peças fracas.

## 4. O que depende da sua ação

### Obrigatório antes de publicar oferta

- Escolher público prioritário, oferta, preço e CTA.
- Confirmar o nome da marca/produto e as funcionalidades que podem ser prometidas.
- Entregar acessos empresariais às redes e habilitar 2FA, sem compartilhar senhas pessoais.
- Aprovar política de marca, privacidade, uso de imagem, depoimentos e termos comerciais.
- Fornecer ou aprovar conta demo, telas, casos, resultados e materiais reais.
- Definir teto mensal e diário de anúncios; cadastrar o meio de pagamento diretamente nas plataformas.
- Indicar responsável por leads e garantir resposta rápida.

### Rotina recorrente

- Aprovar/rejeitar o lote semanal.
- Responder dúvidas técnicas, comerciais ou sensíveis escaladas pelo agente.
- Participar de vídeos quando autenticidade pessoal trouxer vantagem; 30–60 minutos de gravação podem render várias semanas de cortes.
- Analisar mensalmente vendas, CAC, capacidade de atendimento e decisão de escala.

## 5. Controles indispensáveis

- Nunca inventar cliente, depoimento, número, integração, preço ou funcionalidade.
- Nunca publicar dados de produção, telas com dados reais ou segredos.
- Não responder reclamações, ameaças, temas jurídicos ou pedidos de reembolso automaticamente.
- Não aumentar orçamento de mídia sem regra e aprovação.
- Limites diários/mensais, alerta de gasto e pausa geral.
- Tokens de cada plataforma com privilégio mínimo, rotação e revogação documentadas.
- Prévia visual antes da aprovação; logs e versão exata do conteúdo publicado.
- Política para música, imagens, fontes, voz e rosto; usar somente ativos licenciados/autorizados.
- Revisão humana do primeiro mês e amostragem obrigatória depois da estabilização.

## 6. Orçamento mensal

Valores de planejamento em reais, arredondados. Conversões em dólar usam referência de **R$ 5,16/US$** (PTAX venda de 01/09/2026). Impostos, IOF e variação cambial podem elevar assinaturas internacionais. Mídia paga é orçamento de teste, não garantia de vendas.

| Componente | Mínimo validável | Operação recomendada | Escala agressiva |
|---|---:|---:|---:|
| Texto/IA e automação | R$ 50–200 | R$ 200–600 | R$ 600–2.000 |
| Imagem, vídeo, voz e ativos | R$ 0–200 | R$ 300–1.200 | R$ 1.500–5.000 |
| Agendamento/analytics | R$ 0–100 | R$ 80–350 | R$ 350–1.500 |
| Infraestrutura, logs e backup | R$ 30–150 | R$ 150–500 | R$ 500–2.000 |
| Mídia paga | R$ 300–800 | R$ 2.000–5.000 | R$ 8.000–25.000 |
| Apoio humano externo | R$ 0 | R$ 500–2.000 | R$ 3.000–10.000 |
| **Total mensal estimado** | **R$ 380–1.450** | **R$ 3.230–9.650** | **R$ 13.950–45.500** |

### Faixa indicada para a RQCode

Começar por **R$ 1.000–1.500/mês durante 30 dias**, mantendo produção interna e aprovação do proprietário. Após validar rastreamento, página e atendimento, migrar para **R$ 3.500–5.000/mês**, dos quais cerca de 60–70% devem ir para mídia e testes de aquisição. Não recomendo a faixa máxima antes de haver conversão e capacidade de atendimento comprovadas.

### Custo de implantação (uma vez)

- Feito internamente sobre a estrutura RQCode: desembolso externo de aproximadamente **R$ 0–1.500**, além do tempo de desenvolvimento e configuração.
- Com apoio especializado parcial (identidade, templates, tracking e edição): **R$ 4.000–12.000**.
- Implantação completa terceirizada, integrações e painel próprio: **R$ 15.000–50.000+**.

O orçamento real só pode ser fechado após definir canais, volume de vídeo, ferramenta de publicação e quanto do desenvolvimento/edição será interno.

## 7. Premissas verificáveis de custo

- O Buffer oferece plano gratuito com até 3 canais e 10 posts agendados por canal; o Essentials custa US$ 6/canal/mês no mensal ou US$ 5/canal/mês no anual. Isso permite validar a operação sem comprar uma plataforma cara.
- A documentação OpenAI lista geração de imagem por uso e, para vídeo Sora 2, US$ 0,10 por segundo; porém a API de vídeo está marcada para encerramento em 24/09/2026. A estimativa reserva verba para um fornecedor substituto e não assume disponibilidade contínua.
- O custo de texto costuma ser pequeno perto de edição, atendimento e anúncios. Mesmo assim, devem existir alertas e limite de gasto por projeto.

## 8. Plano de 90 dias

### Dias 1–15 — Fundação

- Fechar decisões do P0 e P1.
- Preparar conta demo, landing page, analytics, CRM de leads e identidade.
- Construir base de verdade e primeiros templates.

### Dias 16–30 — Piloto editorial

- Produzir e aprovar o lote inicial.
- Publicar organicamente em até três canais.
- Corrigir tom, qualidade, tempo de produção e rastreamento.

### Dias 31–60 — Aquisição controlada

- Ativar pequenos testes de mídia com teto rígido.
- Medir lead qualificado e demonstração, não só clique.
- Automatizar relatórios, variações e reaproveitamento.

### Dias 61–90 — Otimização

- Concentrar orçamento em mensagem, formato e público vencedores.
- Documentar playbook do ZVTech Checklist.
- Decidir entre aprofundar esse produto ou replicar o processo para o próximo.

## 9. Critérios de sucesso e decisão

O agente estará funcionando quando:

- 90% ou mais do calendário aprovado for publicado sem erro ou duplicidade.
- 100% dos links comerciais tiverem UTM e os leads tiverem origem registrada.
- Nenhuma peça contiver promessa não aprovada ou dado sensível.
- O tempo semanal do proprietário ficar abaixo de duas horas, sem prejudicar a qualidade.
- Houver relatório ligando conteúdo e mídia a leads, demonstrações, propostas e vendas.

A escala só é autorizada quando a empresa conhece o custo por lead qualificado, a conversão para demonstração/venda e a capacidade de atender novos clientes.

## 10. Fontes consultadas

- OpenAI, API e agentes: https://platform.openai.com/docs/quickstart/make-your-first-api-request
- OpenAI, referência de Batch API: https://developers.openai.com/api/reference/resources/batches
- OpenAI, modelo de vídeo Sora 2: https://developers.openai.com/api/docs/models/sora-2
- OpenAI, aviso de encerramento da API de vídeo: https://developers.openai.com/api/reference/typescript/resources/videos/methods/create
- OpenAI, geração de imagens: https://developers.openai.com/api/docs/models/gpt-image-1.5
- Buffer, preços: https://buffer.com/pricing
- Banco Central do Brasil, última PTAX consultada: https://ptax.bcb.gov.br/ptax_internet/consultarUltimaCotacaoDolar.do
