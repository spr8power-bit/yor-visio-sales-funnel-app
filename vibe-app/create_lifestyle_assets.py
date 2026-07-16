from pathlib import Path
from PIL import Image, ImageDraw, ImageFilter

OUT_DIR = Path(__file__).parent / "public" / "images" / "yor-vision" / "lifestyle"
NAVY = (11, 31, 51, 255)
ORANGE = (255, 105, 0, 255)
COPPER = (181, 71, 8, 255)
BLUE = (234, 246, 252, 255)
PALE = (255, 243, 232, 255)
WHITE = (255, 255, 255, 255)


def rounded(draw, box, radius, fill, outline=None, width=1):
    draw.rounded_rectangle(box, radius=radius, fill=fill, outline=outline, width=width)


def soft_shadow(size, box, radius, blur=20, offset=(0, 12)):
    shadow = Image.new("RGBA", size, (0, 0, 0, 0))
    d = ImageDraw.Draw(shadow)
    moved = (box[0] + offset[0], box[1] + offset[1], box[2] + offset[0], box[3] + offset[1])
    d.rounded_rectangle(moved, radius=radius, fill=(11, 31, 51, 38))
    return shadow.filter(ImageFilter.GaussianBlur(blur))


def base_canvas():
    image = Image.new("RGBA", (512, 512), (255, 255, 255, 0))
    bg = Image.new("RGBA", image.size, (255, 255, 255, 0))
    d = ImageDraw.Draw(bg)
    d.ellipse((46, 54, 466, 474), fill=(255, 243, 232, 205))
    d.ellipse((134, 38, 492, 360), fill=(234, 246, 252, 190))
    return Image.alpha_composite(image, bg.filter(ImageFilter.GaussianBlur(10)))


def save_asset(image, name):
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    image.save(OUT_DIR / f"{name}.png")
    image.save(OUT_DIR / f"{name}.webp", "WEBP", quality=92, method=6)


def draw_eye(draw, center, scale=1.0):
    cx, cy = center
    w, h = int(64 * scale), int(32 * scale)
    draw.ellipse((cx - w // 2, cy - h // 2, cx + w // 2, cy + h // 2), fill=WHITE, outline=ORANGE, width=max(2, int(3 * scale)))
    draw.ellipse((cx - int(13 * scale), cy - int(13 * scale), cx + int(13 * scale), cy + int(13 * scale)), fill=(71, 125, 156, 255))
    draw.ellipse((cx - int(6 * scale), cy - int(6 * scale), cx + int(6 * scale), cy + int(6 * scale)), fill=NAVY)
    draw.ellipse((cx + int(2 * scale), cy - int(7 * scale), cx + int(7 * scale), cy - int(2 * scale)), fill=WHITE)


def computer_users():
    img = base_canvas()
    img = Image.alpha_composite(img, soft_shadow(img.size, (104, 122, 408, 314), 28))
    d = ImageDraw.Draw(img)
    rounded(d, (104, 122, 408, 314), 28, WHITE, (255, 105, 0, 110), 4)
    rounded(d, (128, 148, 384, 284), 18, BLUE, (11, 31, 51, 35), 2)
    d.line((226, 315, 286, 315), fill=NAVY, width=12)
    rounded(d, (188, 340, 324, 364), 12, WHITE, (255, 105, 0, 120), 4)
    draw_eye(d, (256, 216), 1.18)
    d.arc((176, 178, 336, 306), 210, 330, fill=ORANGE, width=8)
    save_asset(img, "computer-users")


def smartphone_users():
    img = base_canvas()
    img = Image.alpha_composite(img, soft_shadow(img.size, (160, 78, 352, 430), 42))
    d = ImageDraw.Draw(img)
    rounded(d, (160, 78, 352, 430), 42, NAVY, None, 1)
    rounded(d, (180, 114, 332, 386), 26, WHITE, None, 1)
    rounded(d, (204, 145, 308, 274), 22, PALE, (255, 105, 0, 90), 3)
    draw_eye(d, (256, 210), 0.9)
    d.line((226, 101, 286, 101), fill=(255, 255, 255, 110), width=7)
    d.ellipse((244, 400, 268, 424), fill=WHITE)
    d.arc((198, 298, 314, 368), 200, 340, fill=ORANGE, width=8)
    save_asset(img, "smartphone-users")


def tablet_users():
    img = base_canvas()
    img = Image.alpha_composite(img, soft_shadow(img.size, (82, 118, 430, 350), 34))
    d = ImageDraw.Draw(img)
    rounded(d, (82, 118, 430, 350), 34, NAVY, None, 1)
    rounded(d, (112, 148, 400, 320), 22, WHITE, None, 1)
    rounded(d, (146, 176, 366, 292), 20, BLUE, (255, 105, 0, 90), 3)
    draw_eye(d, (256, 234), 1.12)
    d.ellipse((411, 222, 427, 246), fill=WHITE)
    d.arc((166, 190, 346, 310), 205, 335, fill=ORANGE, width=8)
    save_asset(img, "tablet-users")


def students_professionals():
    img = base_canvas()
    img = Image.alpha_composite(img, soft_shadow(img.size, (96, 146, 416, 350), 32))
    d = ImageDraw.Draw(img)
    rounded(d, (96, 146, 416, 350), 32, WHITE, (255, 105, 0, 110), 4)
    d.ellipse((150, 162, 238, 250), fill=PALE, outline=(255, 105, 0, 120), width=4)
    d.ellipse((274, 162, 362, 250), fill=BLUE, outline=(11, 31, 51, 60), width=4)
    d.ellipse((178, 186, 210, 218), fill=ORANGE)
    d.ellipse((302, 186, 334, 218), fill=NAVY)
    rounded(d, (138, 260, 250, 322), 24, (255, 105, 0, 235))
    rounded(d, (262, 260, 374, 322), 24, NAVY)
    d.line((148, 164, 238, 136), fill=NAVY, width=7)
    d.line((274, 152, 362, 152), fill=ORANGE, width=8)
    d.arc((182, 224, 330, 314), 205, 335, fill=ORANGE, width=7)
    save_asset(img, "students-professionals")


def main():
    computer_users()
    smartphone_users()
    tablet_users()
    students_professionals()


if __name__ == "__main__":
    main()
