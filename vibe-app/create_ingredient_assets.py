from pathlib import Path
from PIL import Image

SOURCE = Path(__file__).parent / "public" / "images" / "yor-vision" / "yor-vision-ingredients.webp"
OUT_DIR = Path(__file__).parent / "public" / "images" / "yor-vision" / "ingredients"

INGREDIENTS = [
    ("glycerin", (24, 62, 161, 205)),
    ("vitamin-c", (178, 62, 317, 205)),
    ("zinc", (329, 62, 471, 205)),
    ("lutein", (485, 62, 626, 205)),
    ("zeaxanthin", (637, 62, 779, 205)),
    ("astaxanthin", (790, 62, 895, 205)),
]


def main():
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    with Image.open(SOURCE) as image:
        image = image.convert("RGBA")
        for name, box in INGREDIENTS:
            crop = image.crop(box)
            canvas = Image.new("RGBA", (180, 180), (255, 255, 255, 0))
            crop.thumbnail((156, 156), Image.LANCZOS)
            canvas.alpha_composite(crop, ((canvas.width - crop.width) // 2, (canvas.height - crop.height) // 2))
            canvas.save(OUT_DIR / f"{name}.webp", "WEBP", quality=92, method=6)
            canvas.save(OUT_DIR / f"{name}.png")
            print(f"Saved {name}")


if __name__ == "__main__":
    main()
