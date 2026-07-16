from pathlib import Path
from PIL import Image

ASSET_DIR = Path(__file__).parent / "public" / "images" / "yor-vision"
PRODUCT = ASSET_DIR / "yor-vision-product-reference.png"
BADGE = ASSET_DIR / "yor-vision-brand-badge.png"


def crop_resize(source: Path, target: str, box, size, quality=86):
    with Image.open(source) as image:
        image = image.convert("RGB")
        cropped = image.crop(box).resize(size, Image.LANCZOS)
        cropped.save(ASSET_DIR / target, "WEBP", quality=quality, method=6)


def full_resize(source: Path, target: str, max_size, quality=86):
    with Image.open(source) as image:
        image = image.convert("RGB")
        image.thumbnail(max_size, Image.LANCZOS)
        image.save(ASSET_DIR / target, "WEBP", quality=quality, method=6)


def main():
    with Image.open(PRODUCT) as image:
        width, height = image.size

    full_resize(PRODUCT, "yor-vision-hero.webp", (1280, 1280), 88)
    crop_resize(PRODUCT, "yor-vision-product-card.webp", (620, 70, 1085, 1120), (520, 720), 88)
    crop_resize(PRODUCT, "yor-vision-product-detail.webp", (520, 0, 1200, 1200), (760, 920), 88)
    crop_resize(PRODUCT, "yor-vision-ingredients.webp", (40, 620, 610, 800), (900, 320), 86)
    crop_resize(PRODUCT, "yor-vision-benefits.webp", (970, 430, width, 790), (680, 520), 86)
    crop_resize(PRODUCT, "yor-vision-lifestyle.webp", (50, 770, 520, 980), (820, 380), 86)
    crop_resize(PRODUCT, "yor-vision-checkout-thumbnail.webp", (650, 260, 1110, 1120), (520, 520), 88)
    full_resize(BADGE, "yor-vision-brand-badge.webp", (900, 900), 88)


if __name__ == "__main__":
    main()
