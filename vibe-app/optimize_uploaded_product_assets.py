from pathlib import Path
from PIL import Image, ImageOps

SOURCE_DIR = Path(r"C:\Users\Admin\.codex\attachments\485e41cb-c87d-4ea0-ae35-9000d70add0f")
ASSET_DIR = Path(__file__).parent / "public" / "images" / "yor-vision"
UPLOADED_DIR = ASSET_DIR / "uploaded"
INGREDIENT_DIR = ASSET_DIR / "ingredients"

ASSETS = {
    "image-7.png": ("yor-vision-product-promo-square", 1200),
    "image-8.png": ("yor-vision-product-promo-wide", 1600),
    "image-9.png": ("yor-vision-bottle-uploaded", 900),
    "image-10.png": ("yor-vision-logo-uploaded", 900),
}

INGREDIENTS = {
    "image-6.png": "glycerin",
    "image-3.png": "vitamin-c",
    "image-4.png": "zinc",
    "image-5.png": "lutein",
    "image-1.png": "zeaxanthin",
    "image-2.png": "astaxanthin",
}


def save_variants(source_path: Path, out_path: Path, max_width: int):
    with Image.open(source_path) as image:
        image = ImageOps.exif_transpose(image).convert("RGBA")
        original = image.copy()
        original.save(out_path.with_suffix(".png"))

        web = image.copy()
        if web.width > max_width:
            new_height = round(web.height * (max_width / web.width))
            web = web.resize((max_width, new_height), Image.LANCZOS)
        web.save(out_path.with_suffix(".webp"), "WEBP", quality=88, method=6)

        for scale in (0.5, 0.75):
            variant_width = max(180, round(max_width * scale))
            if image.width > variant_width:
                variant_height = round(image.height * (variant_width / image.width))
                variant = image.resize((variant_width, variant_height), Image.LANCZOS)
            else:
                variant = image.copy()
            variant.save(out_path.with_name(f"{out_path.name}-{variant_width}w").with_suffix(".webp"), "WEBP", quality=86, method=6)
        return web.size


def save_trimmed_logo():
    source_path = SOURCE_DIR / "image-10.png"
    out_path = UPLOADED_DIR / "yor-vision-logo-lockup"
    with Image.open(source_path) as image:
        image = ImageOps.exif_transpose(image).convert("RGBA")
        alpha_bounds = image.getchannel("A").getbbox()
        logo = image.crop(alpha_bounds) if alpha_bounds else image
        padding = 18
        canvas = Image.new("RGBA", (logo.width + padding * 2, logo.height + padding * 2), (255, 255, 255, 0))
        canvas.alpha_composite(logo, (padding, padding))
        canvas.save(out_path.with_suffix(".png"))
        canvas.save(out_path.with_suffix(".webp"), "WEBP", quality=90, method=6)
        for width in (360, 720):
            variant = canvas.copy()
            if variant.width > width:
                height = round(variant.height * (width / variant.width))
                variant = variant.resize((width, height), Image.LANCZOS)
            variant.save(out_path.with_name(f"{out_path.name}-{width}w").with_suffix(".webp"), "WEBP", quality=88, method=6)
        print(f"yor-vision-logo-lockup.webp {canvas.width}x{canvas.height}")


def main():
    UPLOADED_DIR.mkdir(parents=True, exist_ok=True)
    INGREDIENT_DIR.mkdir(parents=True, exist_ok=True)

    for filename, (name, max_width) in ASSETS.items():
        size = save_variants(SOURCE_DIR / filename, UPLOADED_DIR / name, max_width)
        print(f"{name}.webp {size[0]}x{size[1]}")

    save_trimmed_logo()

    for filename, name in INGREDIENTS.items():
        size = save_variants(SOURCE_DIR / filename, INGREDIENT_DIR / name, 360)
        print(f"{name}.webp {size[0]}x{size[1]}")


if __name__ == "__main__":
    main()
