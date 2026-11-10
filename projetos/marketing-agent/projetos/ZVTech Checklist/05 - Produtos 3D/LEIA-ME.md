# Brindes 3D — ZVTech Checklist

Pasta central de produção, atualizada em 05/10/2026. Originais em `C:\desenvolvimento\projetos_3D` preservados como histórico.

## Chave de boca — revisão da argola

Pasta `chave-boca`:

- `Chave_78mm_rebaixo_argola_completa.stl`: sólido completo para uma cor. STL não contém cores. União do texto validada com tolerância de 0,005 mm; os arquivos bicolores não receberam essa tolerância.
- `Chave_corpo_PETG_metal_rebaixo.stl` e `Chave_site_PETG_preto.stl`: partes nas mesmas coordenadas para impressão bicolor. Importar juntas como partes de um único objeto, sem reposicioná-las individualmente.
- `Chave_78mm_rebaixo_argola_01.3mf` e `Chave_78mm_rebaixo_argola_10.3mf`: uma ou dez unidades, com corpo PETG metalizado e texto PETG preto. Refatiar antes de imprimir; não reutilizar G-code antigo.
- `Chave_78mm_rebaixo_argola.blend`: fonte editável.
- `Previa_modelo_real.png`: render da geometria efetiva.
- `validacao.json`: verificações da malha.

Rebaixo somente superior na região do furo, com patamar de 3,2 mm e transições graduais. Comprimento de 78 mm, endereço ampliado, gravação inferior e canaletas preservados. Compatibilidade com a corrente específica e resistência da região ainda exigem teste físico; recomenda-se uma unidade antes do lote. Conferir A3 metalizado e A2 preto no envio, pois os filamentos físicos podem ter mudado.

## Beijo / bocas — PLA

Pasta `beijo-abridor`, cópia sem alteração geométrica da versão anterior:

- `Beijo_zvtech_PLA_10_pecas.3mf`: lote de dez bocas.
- `Beijo_zvtech_PLA_01_pecas.3mf`: uma unidade.
- `Beijo_zvtech_baixo_relevo.stl` e `Beijo_zvtech.blend`: modelo e fonte.
- `base_gravada.png`: visualização da gravação traseira.

Endereço em baixo-relevo de 0,6 mm; PLA de uma cor, sem torre de purga. Confirmar PLA no A1 do AMS no envio. Última estimativa: 3h49 / 71,12 g para dez. O fatiador apontou saliência sem apoio; considerar na avaliação física. O modelo de terceiros mantém sua licença; a centralização não altera os direitos de uso.

Nenhuma impressão iniciada nesta atualização.
