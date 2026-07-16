from pathlib import Path
from PIL import Image

path = Path(r"C:\Users\Admin\Desktop\YOR INT'L\YOR Onboarding Files\Project YOR Vision Sales Page\yv-front page.png")
with Image.open(path) as image:
    print(image.size)
