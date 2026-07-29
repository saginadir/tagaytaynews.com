---
name: favicon-generator
description: "Generate all favicon variants (ICO, PNG, SVG) from a source image. Activates when the user asks to create, update, or replace favicons, app icons, apple-touch-icon, or android-chrome icons. Handles background removal, trimming whitespace, and generating all standard sizes."
metadata:
  author: project
---

# Favicon Generator

## Prerequisites

- ImageMagick 7+ must be installed (`brew install imagemagick` on macOS).
- For SVG conversion: there is no reliable CLI tool for color raster-to-SVG conversion. **Ask the user** to use [VTracer online](https://www.vtracer.xyz/) to convert the processed PNG to SVG, then use the exported file.

## Background Removal from Source Image

When the source image has a white/light background around a logo (e.g., rounded square icon on white):

### Step 1: Trim white sides

```bash
magick <source>.png -fuzz 15% -trim +repage /tmp/trimmed.png
```

- `-fuzz 15%` handles near-white pixels that aren't perfectly `#FFFFFF`.
- `-trim` removes uniform border color.
- `+repage` resets the canvas to the trimmed size.

### Step 2: Make corners transparent (for rounded logos)

This is a multi-step process. Do NOT try to do it in a single command with `-level` or `-blur` on the whole image — that will destroy colors.

```bash
# Get dimensions
SIZE=$(magick identify -format "%w %h" /tmp/trimmed.png)
W=$(echo $SIZE | cut -d' ' -f1)
H=$(echo $SIZE | cut -d' ' -f2)
MAXW=$((W-1))
MAXH=$((H-1))

# Floodfill all 4 corners with transparency (high fuzz for gradient edges)
magick /tmp/trimmed.png -alpha set -fuzz 30% \
  -fill none -draw "color 0,0 floodfill" \
  -fill none -draw "color ${MAXW},0 floodfill" \
  -fill none -draw "color 0,${MAXH} floodfill" \
  -fill none -draw "color ${MAXW},${MAXH} floodfill" \
  /tmp/with_alpha.png
```

### Step 3: Clean up white fringing on edges

After floodfill, anti-aliased edges will have faint white fringe pixels. Fix by eroding and softening the alpha channel **separately** — never apply blur/level to the color channels:

```bash
magick /tmp/with_alpha.png \
  \( +clone -channel alpha -separate +channel \
     -morphology erode disk:1 -blur 0x0.5 \) \
  -compose CopyOpacity -composite \
  output.png
```

- `-morphology erode disk:1` eats 1px into the alpha edge to remove white fringe.
- `-blur 0x0.5` softens the alpha boundary for a clean look.
- The color data is untouched — only the alpha mask is modified.

### Handling logos that already have transparency

Many source PNGs already have a transparent background. **Always check first** before doing any background removal:

```bash
magick source.png -channel alpha -separate -format "%[fx:mean]" info:
```

- A value of `1.0` means fully opaque (no transparency) — background removal is needed.
- A value less than `1.0` means the image already has transparency — **skip background removal entirely**.

If the source already has transparency, just trim and resize directly:

```bash
magick source.png -trim +repage output.png
```

#### Preserving inner white areas when the source has transparency

Logos with transparent backgrounds often have **inner white areas** (e.g., a white chevron cutout) that are also transparent — by design, these are meant to show through on a white page background. For favicons, you want those inner areas to be **solid white**, not transparent.

Fix this with a flatten-then-remask approach:

```bash
# 1. Flatten onto white — fills ALL transparent areas with white
magick source.png -background white -flatten /tmp/flat.png

# 2. Make it square with white padding
magick /tmp/flat.png -gravity center -background white -extent 400x400 /tmp/square.png

# 3. Floodfill corners to re-add outer transparency (low fuzz prevents leaking)
magick /tmp/square.png -alpha set -fuzz 3% \
  -fill none -draw "color 0,0 floodfill" \
  -fill none -draw "color 399,0 floodfill" \
  -fill none -draw "color 0,399 floodfill" \
  -fill none -draw "color 399,399 floodfill" \
  /tmp/final.png
```

This works because flattening fills the inner white areas, and the logo's colored shapes form closed boundaries that prevent the subsequent floodfill from reaching them.

### Making the image square

When using `-extent` to make a non-square image square, **always** pass `-background none` to avoid filling padding with white:

```bash
magick input.png -background none -gravity center -extent 400x400 output.png
```

Without `-background none`, ImageMagick defaults to white padding, which silently destroys transparency.

### Common mistakes to avoid

- **Do NOT** use `-level` or `-blur` on the full image — it crushes/darkens colors.
- **Do NOT** use `-alpha extract -negate` to invert the mask — the logic is inverted. Use `-alpha extract` directly from the floodfilled image.
- **Do NOT** try to do trim + floodfill + erode in a single command. Use intermediate files to avoid pipeline errors.
- **Do NOT** use `-extent` without `-background none` — it fills transparent padding with white.
- **Do NOT** assume a PNG with a white-looking background needs floodfill — check the alpha channel first. The "white" may already be transparent.
- Always verify alpha exists: `magick output.png -channel alpha -separate -format "%[fx:mean]" info:` — value < 1.0 confirms transparency is present.
- Always verify visually by compositing on a bright color: `magick -size 200x200 xc:"#FF00FF" output.png -composite /tmp/check.png`

## Generating Favicon Variants

Once you have a clean PNG with transparent corners (`output.png`), generate all variants:

```bash
SRC=output.png
PUB=public

# Standard favicon PNGs
magick "$SRC" -resize 16x16   "$PUB/favicon-16x16.png"
magick "$SRC" -resize 32x32   "$PUB/favicon-32x32.png"

# Apple touch icon (180x180)
magick "$SRC" -resize 180x180 "$PUB/apple-touch-icon.png"

# Android Chrome icons
magick "$SRC" -resize 192x192 "$PUB/android-chrome-192x192.png"
magick "$SRC" -resize 512x512 "$PUB/android-chrome-512x512.png"
# If source is already 512x512, just copy instead of resizing

# Multi-size ICO (contains 16, 32, 48)
magick "$SRC" -define icon:auto-resize=48,32,16 "$PUB/favicon.ico"
```

### SVG favicon

There is no good CLI tool for multi-color raster-to-SVG tracing:
- `potrace` only produces monochrome (single color) output — not suitable for multi-color logos.
- `vtracer` supports color but is difficult to install via CLI (cargo auth issues, pip PEP 668 blocks).

**Ask the user to convert the processed PNG to SVG using [VTracer online](https://www.vtracer.xyz/)**, then copy the exported file to `favicon.svg`.

## Standard Favicon File List

| File | Size | Purpose |
|------|------|---------|
| `favicon.ico` | 16+32+48 | Legacy browsers |
| `favicon.svg` | vector | Modern browsers |
| `favicon-16x16.png` | 16x16 | Fallback |
| `favicon-32x32.png` | 32x32 | Standard tab icon |
| `apple-touch-icon.png` | 180x180 | iOS home screen |
| `android-chrome-192x192.png` | 192x192 | Android home screen |
| `android-chrome-512x512.png` | 512x512 | Android splash screen |
