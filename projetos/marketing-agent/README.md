# Agente de Marketing RQCode

## Estrutura principal a partir de 08/10/2026

Os materiais passam a ser organizados por produto em `projetos/`, com nomes legíveis para uso diário. Comece em [projetos/LEIA-ME.md](projetos/LEIA-ME.md).

- `projetos/ZVTech Checklist/`: fonte central dos materiais do ZVTech Checklist.
- `scripts/gerar_redes_sociais_zvtech.py`: regenera os arquivos de Facebook e Instagram a partir da identidade oficial.
- As pastas antigas `assets/`, `knowledge/` e `output/` permanecem temporariamente para compatibilidade com os scripts e documentos anteriores. Novos materiais devem entrar em `projetos/<Nome do projeto>/`.

## Atualização de 05/10/2026

Comece por [COMECE-AQUI-2026-10-05.md](COMECE-AQUI-2026-10-05.md): flyers digitais, PDFs A4 com 4 cópias, instruções de impressão e estado atual do agente. Base factual em `knowledge/` e contrato operacional em `workflows/`.

Os documentos de setembro abaixo são históricos. Estimativas, pendências, preços de ferramentas e disponibilidade de APIs precisam ser revalidados; não representam orçamento autorizado. Nenhuma publicação ou despesa foi ativada.

Estrutura operacional para planejar, produzir, aprovar, publicar e medir conteúdo dos sistemas RQCode.

## Brindes físicos

Os modelos 3D da chave de boca e do abridor Beijo estão centralizados em [output/impressao-3d/LEIA-ME.md](output/impressao-3d/LEIA-ME.md), com STL, fontes editáveis e projetos 3MF de uma e dez peças.

## Produto inicial

O primeiro produto será o **ZVTech Checklist**. A expansão para Fleet Way, Gestor ECV e os demais sistemas só começa depois que o processo estiver estável e mensurável.

## Princípios

- Automação com supervisão humana: nenhuma publicação paga, promessa comercial ou resposta sensível é liberada sem aprovação.
- Fonte única da verdade: posicionamento, preços, funcionalidades e provas precisam vir de materiais aprovados.
- Conteúdo reutilizável: uma pauta deve gerar vídeo curto, carrossel, post, story e texto comercial.
- Mensuração até a venda: todo link usa UTM e todo lead recebe origem/campanha.
- Baixo acoplamento: geradores de texto, imagem, vídeo e publicadores ficam atrás de adaptadores substituíveis.
- Segredos somente em cofre/variáveis de ambiente; nunca em prompts, documentos ou repositório.

## Documentos

- `CHECKLIST-INICIAL.md`: ações em ordem, responsáveis e critérios de aceite.
- `RELATORIO-IMPLANTACAO.md`: escopo, arquitetura, operação, riscos, cronograma e orçamento.

## Estados do conteúdo

`ideia -> briefing -> rascunho -> revisão -> aprovado -> agendado -> publicado -> medido -> reaproveitado`
