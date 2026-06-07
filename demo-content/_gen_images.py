"""Generate soft-gradient placeholder images (blossom style) for demo content.

Each image is 1200x800 with a diagonal pastel gradient and a centered serif
label, matching the look of the existing blossom-feminine demo images.
"""
import os
from PIL import Image, ImageDraw, ImageFont

W, H = 1200, 800
FONT_REG = "C:/Windows/Fonts/georgia.ttf"
FONT_BOLD = "C:/Windows/Fonts/georgiab.ttf"
HERE = os.path.dirname(__file__)


def lerp(a, b, t):
    return tuple(int(a[i] + (b[i] - a[i]) * t) for i in range(3))


def gradient(c1, c2):
    img = Image.new("RGB", (W, H), c1)
    px = img.load()
    for y in range(H):
        ty = y / H
        for x in range(W):
            t = (x / W + ty) / 2
            px[x, y] = lerp(c1, c2, t)
    return img


def make_size(path, size, c1, c2, label, text_color, sub=None, label_size=64, sub_size=30):
    global W, H
    oldW, oldH = W, H
    W, H = size
    img = gradient(c1, c2)
    if label:
        draw = ImageDraw.Draw(img)
        font = ImageFont.truetype(FONT_BOLD, label_size)
        bbox = draw.textbbox((0, 0), label, font=font)
        tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
        x = (W - tw) / 2 - bbox[0]
        y = (H - th) / 2 - bbox[1]
        if sub:
            y -= 26
        draw.text((x, y), label, fill=text_color, font=font)
        if sub:
            sfont = ImageFont.truetype(FONT_REG, sub_size)
            sb = draw.textbbox((0, 0), sub, font=sfont)
            sx = (W - (sb[2] - sb[0])) / 2 - sb[0]
            draw.text((sx, y + th + 30), sub.upper(), fill=text_color, font=sfont)
    img.save(path, "JPEG", quality=84)
    print("wrote", os.path.relpath(path, HERE))
    W, H = oldW, oldH


def make(path, c1, c2, label, text_color, sub=None):
    img = gradient(c1, c2)
    draw = ImageDraw.Draw(img)
    font = ImageFont.truetype(FONT_BOLD, 64)
    bbox = draw.textbbox((0, 0), label, font=font)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    x = (W - tw) / 2 - bbox[0]
    y = (H - th) / 2 - bbox[1]
    if sub:
        y -= 28
    draw.text((x, y), label, fill=text_color, font=font)
    if sub:
        sfont = ImageFont.truetype(FONT_REG, 30)
        sb = draw.textbbox((0, 0), sub, font=sfont)
        sw = sb[2] - sb[0]
        sx = (W - sw) / 2 - sb[0]
        sy = y + th + 34
        draw.text((sx, sy), sub.upper(), fill=text_color, font=sfont)
    img.save(path, "JPEG", quality=82)
    print("wrote", os.path.relpath(path, HERE))


# ---------------------------------------------------------------- Storefront
# "Maple & Stone" - homeware & decor shop. Warm neutral palette per category.
SF = os.path.join(HERE, "storefront", "images")
os.makedirs(SF, exist_ok=True)

CAT_COLORS = {
    "Living":  ((241, 230, 218), (206, 180, 156), (92, 64, 51)),
    "Kitchen": ((226, 232, 222), (176, 196, 174), (60, 78, 58)),
    "Decor":   ((242, 225, 213), (211, 158, 130), (110, 62, 45)),
}

storefront_products = [
    ("linen-throw-pillow", "Linen Throw Pillow", "Living"),
    ("stoneware-mug-set", "Stoneware Mug Set", "Kitchen"),
    ("oak-serving-board", "Oak Serving Board", "Kitchen"),
    ("ceramic-vase", "Ceramic Vase", "Decor"),
    ("wool-blanket", "Wool Blanket", "Living"),
    ("brass-candle-holder", "Brass Candle Holder", "Decor"),
    ("linen-apron", "Linen Apron", "Kitchen"),
    ("woven-basket", "Woven Basket", "Decor"),
]

for slug, name, cat in storefront_products:
    c1, c2, tc = CAT_COLORS[cat]
    make(os.path.join(SF, slug + ".jpg"), c1, c2, name, tc, cat)

# category cards for the Storefront homepage product-categories section
for cat in CAT_COLORS:
    c1, c2, tc = CAT_COLORS[cat]
    make(os.path.join(SF, "category-" + cat.lower() + ".jpg"), c1, c2, cat, tc)

# ---------------------------------------------------------------------- Zakra
# "Lumen Consulting" - strategy & digital consultancy. Cool professional palette.
ZK = os.path.join(HERE, "zakra", "images")
os.makedirs(ZK, exist_ok=True)

NAVY = (26, 41, 66)
TEAL = (38, 86, 102)
SLATE = (74, 94, 120)
MIST = (214, 226, 232)
STEEL = (120, 150, 168)

zakra_posts = [
    ("digital-strategy", "Digital Strategy", "Strategy"),
    ("customer-research", "Customer Research", "Marketing"),
    ("tech-stack-growth", "The Right Tech Stack", "Technology"),
    ("time-to-rebrand", "Time to Rebrand", "Branding"),
    ("data-into-decisions", "Data Into Decisions", "Insights"),
    ("leading-hybrid-team", "Leading Hybrid Teams", "Leadership"),
]
for slug, name, cat in zakra_posts:
    make(os.path.join(ZK, slug + ".jpg"), TEAL, MIST, name, NAVY, cat)

# hero + section imagery for the static homepage / pages
make(os.path.join(ZK, "lumen-hero.jpg"), NAVY, TEAL, "Lumen Consulting", MIST,
     "Strategy / Brand / Technology")
make(os.path.join(ZK, "about-team.jpg"), SLATE, MIST, "Our Team", NAVY)
make(os.path.join(ZK, "services.jpg"), STEEL, MIST, "What We Do", NAVY)

# ----------------------------------------------------- upgraded showcase art
# Storefront wide hero (no text; cover block overlays its own heading)
make_size(os.path.join(SF, "hero.jpg"), (1600, 900),
          (236, 224, 210), (188, 150, 124), "", (0, 0, 0))
# Zakra wide hero (cool, no text)
make_size(os.path.join(ZK, "hero.jpg"), (1600, 900), NAVY, TEAL, "", MIST)
# Zakra CTA band background
make_size(os.path.join(ZK, "cta-bg.jpg"), (1600, 600), TEAL, NAVY, "", MIST)

# Blossom featured category cards (pink palette to match the theme)
BL = os.path.join(HERE, "blossom-feminine", "images")
PINK1, PINK2, PLUM = (250, 224, 234), (243, 201, 221), (124, 74, 98)
BLUSH1, BLUSH2 = (252, 235, 226), (244, 214, 201)
for slug, label in [("card-fashion", "Fashion"), ("card-travel", "Travel"),
                    ("card-beauty", "Beauty")]:
    make_size(os.path.join(BL, slug + ".jpg"), (800, 600), PINK1, PINK2,
              label, PLUM, label_size=58)
# Blossom wide hero strip for the slider fallback / about widget
make_size(os.path.join(BL, "about-emma.jpg"), (800, 800), BLUSH1, BLUSH2,
          "Emma", PLUM, label_size=70)
