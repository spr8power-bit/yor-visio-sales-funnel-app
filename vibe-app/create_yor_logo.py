from pathlib import Path
from PIL import Image

SOURCE = Path(r"C:\Users\Admin\Desktop\YOR INT'L\YOR Onboarding Files\Yor Vision Creatives\Yor Vision Logo.png")
ASSET_DIR = Path(__file__).parent / "public" / "images" / "yor-vision"


def main():
    ASSET_DIR.mkdir(parents=True, exist_ok=True)
    with Image.open(SOURCE) as image:
        image = image.convert("RGBA")
        image.save(ASSET_DIR / "yor-vision-logo-original.png")

        alpha_bounds = image.getchannel("A").getbbox()
        logo = image.crop(alpha_bounds) if alpha_bounds else image

        padding = max(12, round(max(logo.size) * 0.02))
        canvas = Image.new("RGBA", (logo.width + padding * 2, logo.height + padding * 2), (255, 255, 255, 0))
        canvas.alpha_composite(logo, (padding, padding))

        canvas.save(ASSET_DIR / "yor-vision-logo.png")
        canvas.save(ASSET_DIR / "yor-vision-logo.webp", "WEBP", quality=92, method=6)
        print(f"Saved logo assets at {canvas.width}x{canvas.height}")


if __name__ == "__main__":
    main()
