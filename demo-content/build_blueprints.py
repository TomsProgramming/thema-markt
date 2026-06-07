"""Compose test-themes/<theme>/blueprint.json from a base config plus the
per-theme demo-content/<theme>/setup.php (embedded into a runPHP step).

Keeping the PHP in a real .php file means it stays readable and lintable
instead of being squeezed onto one JSON line by hand. Run this after editing
any setup.php. The generated blueprint.json is what Playground consumes.
"""
import json
import os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
RAW = "https://github.com/TomsProgramming/thema-markt/raw/refs/heads/main"

THEMES = {
    "blossom-feminine": {
        "zip": "test-themes/blossom-feminine/blossom-feminine.1.5.2.zip",
        "wxr": "demo-content/blossom-feminine/blossom-demo.xml",
        "plugins": ["blossomthemes-toolkit"],
        "siteOptions": {
            "blogname": "Petal & Bloom",
            "blogdescription": "A lifestyle, fashion & travel journal",
            "permalink_structure": "/%postname%/",
            "posts_per_page": "9",
        },
    },
    "storefront": {
        "zip": "test-themes/storefront/storefront.4.6.2.zip",
        "wxr": "demo-content/storefront/storefront-demo.xml",
        "plugins": ["woocommerce"],
        "siteOptions": {
            "blogname": "Maple & Stone",
            "blogdescription": "Homeware and decor for considered living",
            "permalink_structure": "/%postname%/",
            "posts_per_page": "12",
        },
    },
    "zakra": {
        "zip": "test-themes/zakra/zakra.4.2.1.zip",
        "wxr": "demo-content/zakra/zakra-demo.xml",
        "plugins": [],
        "siteOptions": {
            "blogname": "Lumen Consulting",
            "blogdescription": "Strategy, brand & technology consultancy",
            "permalink_structure": "/%postname%/",
            "posts_per_page": "9",
        },
    },
}


def build(theme, cfg):
    setup_path = os.path.join(ROOT, "demo-content", theme, "setup.php")
    if not os.path.exists(setup_path):
        print("skip", theme, "(no setup.php yet)")
        return
    code = open(setup_path, encoding="utf-8").read()
    bp = {
        "$schema": "https://playground.wordpress.net/blueprint-schema.json",
        "login": True,
    }
    if cfg["plugins"]:
        bp["plugins"] = cfg["plugins"]
    bp["siteOptions"] = cfg["siteOptions"]
    bp["steps"] = [
        {
            "step": "installTheme",
            "themeData": {"resource": "url", "url": f"{RAW}/{cfg['zip']}"},
            "options": {"activate": True},
        },
        {
            "step": "importWxr",
            "file": {"resource": "url", "url": f"{RAW}/{cfg['wxr']}"},
        },
        {"step": "runPHP", "code": code},
    ]
    out = os.path.join(ROOT, "test-themes", theme, "blueprint.json")
    with open(out, "w", encoding="utf-8") as f:
        json.dump(bp, f, indent=2, ensure_ascii=False)
        f.write("\n")
    print("wrote", os.path.relpath(out, ROOT))


if __name__ == "__main__":
    for theme, cfg in THEMES.items():
        build(theme, cfg)
