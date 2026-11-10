# Marketing — ZVTech Checklist

Esta é a pasta central e humana para localizar os materiais de marketing do produto.

## Facebook

Pasta: `02 - Redes Sociais/Facebook`

- `01 - Foto de perfil Facebook - 1080x1080.png`: enviar em **Editar foto do perfil**; mantém o ícone acima do nome.
- `02 - Capa da pagina Facebook - 1640x624.png`: enviar em **Editar foto da capa**. A marca foi reduzida e centralizada para suportar o recorte entre celular e computador.
- `03 - Alternativa de perfil somente icone - 1080x1080.png`: alternativa mais legível quando a foto aparece muito pequena.

## Instagram

Pasta: `02 - Redes Sociais/Instagram`

- `01 - Foto de perfil Instagram - 1080x1080.png`: usar em **Editar perfil > Editar foto ou avatar**; mantém o ícone acima do nome e tem margem para o recorte circular.
- `02 - Publicacao de apresentacao Feed - 1080x1350.png`: primeira publicação de apresentação no feed, formato vertical 4:5.
- `03 - Apresentacao Story - 1080x1920.png`: apresentação em Story, formato 9:16.
- `04 - Alternativa de perfil somente icone - 1080x1080.png`: alternativa para comparar a legibilidade no recorte circular.

O Instagram não possui imagem de capa da conta. A identificação visual da conta é feita principalmente pela foto de perfil, biografia e publicações fixadas.

## Regeneração

Execute no PowerShell:

```powershell
& 'C:\Users\Micro\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' 'C:\desenvolvimento\rqcode\projetos\marketing-agent\scripts\gerar_redes_sociais_zvtech.py'
```

O script preserva a marca oficial; ele não recria o logotipo com IA.
