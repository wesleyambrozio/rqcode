from pathlib import Path

from PIL import Image, ImageDraw


ROOT = Path(r"C:\desenvolvimento")
SOURCE = ROOT / "checklist" / "site-www"
TARGET = (
    ROOT
    / "rqcode"
    / "projetos"
    / "marketing-agent"
    / "projetos"
    / "ZVTech Checklist"
    / "02 - Redes Sociais"
)

NAVY = (3, 38, 91)
BLUE = (5, 57, 132)
GREEN = (0, 224, 143)
WHITE = (255, 255, 255)


def contain(image: Image.Image, box: tuple[int, int]) -> Image.Image:
    copy = image.copy()
    copy.thumbnail(box, Image.Resampling.LANCZOS)
    return copy


def paste_center(canvas: Image.Image, image: Image.Image, y: int | None = None) -> None:
    x = (canvas.width - image.width) // 2
    top = (canvas.height - image.height) // 2 if y is None else y
    canvas.paste(image, (x, top), image if image.mode == "RGBA" else None)


def gradient(size: tuple[int, int]) -> Image.Image:
    width, height = size
    canvas = Image.new("RGB", size, NAVY)
    draw = ImageDraw.Draw(canvas)
    for y in range(height):
        ratio = y / max(height - 1, 1)
        color = tuple(int(NAVY[i] * (1 - ratio) + BLUE[i] * ratio) for i in range(3))
        draw.line((0, y, width, y), fill=color)
    return canvas


def profile_asset(output: Path) -> None:
    icon = Image.open(SOURCE / "zvtech-checklist-icone-aplicativo.png").convert("RGBA")
    logo = Image.open(SOURCE / "zvtech-checklist-logo-oficial-fundo-branco.png").convert("RGB")
    canvas = Image.new("RGB", (1080, 1080), WHITE)
    fitted_icon = contain(icon, (560, 560))
    fitted_logo = contain(logo, (760, 254))
    paste_center(canvas, fitted_icon, 120)
    paste_center(canvas, fitted_logo, 690)
    canvas.save(output, "PNG", optimize=True)


def icon_only_asset(output: Path) -> None:
    icon = Image.open(SOURCE / "zvtech-checklist-icone-aplicativo.png").convert("RGBA")
    canvas = Image.new("RGB", (1080, 1080), WHITE)
    fitted = contain(icon, (880, 880))
    paste_center(canvas, fitted)
    canvas.save(output, "PNG", optimize=True)


def facebook_cover(output: Path) -> None:
    logo = Image.open(SOURCE / "zvtech-checklist-logo-oficial-fundo-branco.png").convert("RGB")
    canvas = Image.new("RGB", (1640, 624), WHITE)
    fitted = contain(logo, (1180, 394))
    paste_center(canvas, fitted)
    canvas.save(output, "PNG", optimize=True)


def presentation_asset(size: tuple[int, int], output: Path) -> None:
    logo = Image.open(SOURCE / "zvtech-checklist-logo-oficial-fundo-branco.png").convert("RGB")
    canvas = gradient(size)
    draw = ImageDraw.Draw(canvas)

    # Área branca central mantém a marca legível e consistente, sem recriar o logotipo.
    margin_x = int(size[0] * 0.10)
    margin_y = int(size[1] * 0.15)
    card = (margin_x, margin_y, size[0] - margin_x, size[1] - margin_y)
    radius = max(32, int(size[0] * 0.045))
    draw.rounded_rectangle(card, radius=radius, fill=WHITE)

    fitted = contain(logo, (int(size[0] * 0.72), int(size[0] * 0.34)))
    paste_center(canvas, fitted)

    # Filete verde identifica a peça mesmo quando vista em miniatura.
    line_width = max(12, int(size[0] * 0.018))
    draw.rounded_rectangle(
        (margin_x, size[1] - margin_y - line_width, size[0] - margin_x, size[1] - margin_y),
        radius=line_width // 2,
        fill=GREEN,
    )
    canvas.save(output, "PNG", optimize=True)


def main() -> None:
    facebook = TARGET / "Facebook"
    instagram = TARGET / "Instagram"
    facebook.mkdir(parents=True, exist_ok=True)
    instagram.mkdir(parents=True, exist_ok=True)

    profile_asset(facebook / "01 - Foto de perfil Facebook - 1080x1080.png")
    facebook_cover(facebook / "02 - Capa da pagina Facebook - 1640x624.png")
    icon_only_asset(facebook / "03 - Alternativa de perfil somente icone - 1080x1080.png")

    profile_asset(instagram / "01 - Foto de perfil Instagram - 1080x1080.png")
    presentation_asset(
        (1080, 1350),
        instagram / "02 - Publicacao de apresentacao Feed - 1080x1350.png",
    )
    presentation_asset(
        (1080, 1920),
        instagram / "03 - Apresentacao Story - 1080x1920.png",
    )
    icon_only_asset(instagram / "04 - Alternativa de perfil somente icone - 1080x1080.png")

    print(f"Arquivos gerados em: {TARGET}")


if __name__ == "__main__":
    main()
