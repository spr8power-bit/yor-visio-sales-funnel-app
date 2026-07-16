from pathlib import Path
from PIL import Image

SOURCE = Path(r"C:\Users\Admin\Desktop\YOR INT'L\YOR Onboarding Files\Yor Vision Creatives\YOR VISION - Eye Drop - No BG.png")
ASSET_DIR = Path(__file__).parent / "public" / "images" / "yor-vision"


def fit_transparent(image, size):
    image = image.convert("RGBA")
    image.thumbnail(size, Image.LANCZOS)
    canvas = Image.new("RGBA", size, (255, 255, 255, 0))
    x = (size[0] - image.width) // 2
    y = (size[1] - image.height) // 2
    canvas.alpha_composite(image, (x, y))
    return canvas


def main():
    ASSET_DIR.mkdir(parents=True, exist_ok=True)
    original = Image.open(SOURCE).convert("RGBA")
    original.save(ASSET_DIR / "yor-vision-eye-drop-no-bg.png")
    fit_transparent(original.copy(), (620, 920)).save(ASSET_DIR / "yor-vision-eye-drop.webp", "WEBP", quality=92, method=6)
    fit_transparent(original.copy(), (280, 420)).save(ASSET_DIR / "yor-vision-eye-drop-card.webp", "WEBP", quality=90, method=6)
    fit_transparent(original.copy(), (220, 220)).save(ASSET_DIR / "yor-vision-eye-drop-thumb.webp", "WEBP", quality=90, method=6)


if __name__ == "__main__":
    main()
