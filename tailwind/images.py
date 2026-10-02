# Builds the section images from the store screenshots into site/assets/screens/.
#   hero-desktop: screenshot 1 without the white Windows title bar and the empty left margin
#   hero-mobile:  Speed Score card stacked over the Main Problems card (readable on a phone)
# Each is written as PNG + WebP. Run: python3 images.py
from PIL import Image

SRC = "../site/assets/screens/1-home-speed-score.png"
OUT = "../site/assets/screens/"

def save(im, name):
    im.save(OUT + name + ".png", optimize=True)
    im.save(OUT + name + ".webp", quality=86, method=6)
    print(name, im.size)

src = Image.open(SRC).convert("RGB")
save(src.crop((290, 23, 1920, 1040)), "hero-desktop")  # no title bar, no empty left margin

bg = src.getpixel((280, 300))
score = src.crop((306, 94, 758, 524))
problems = src.crop((762, 94, 1214, 524))
gap = 16
pad = 32  # empty space under the cards for the SCAN COMPLETE readout
mobile = Image.new("RGB", (score.width, score.height + gap + problems.height + pad), bg)
mobile.paste(score, (0, 0))
mobile.paste(problems, (0, score.height + gap))
save(mobile, "hero-mobile")

# Small icons: top bar (shown at 32px, 2x) and favicon.

icon = Image.open("../site/assets/icon.png").convert("RGBA")
for size, name in ((64, "icon-64.png"), (32, "favicon-32.png"), (180, "apple-touch-icon.png")):
    icon.resize((size, size), Image.LANCZOS).save("../site/assets/" + name, optimize=True)
    print(name)

# Tablet (768-1023px): the same 2 cards side by side.
tablet = Image.new("RGB", (problems.width * 2 + gap, score.height + pad), bg)
tablet.paste(score, (0, 0))
tablet.paste(problems, (score.width + gap, 0))
save(tablet, "hero-tablet")

# Deep scan section (screenshot 2, "What was measured").
#   scan-desktop: the 4 cards with problems in a 2x2 grid (Startup, Background / Drive space, Memory),
#                 so the text stays readable in a 7-column frame
#   scan-mobile:  Startup impact card stacked over Background processes card
# The margin keeps the first card's corner clear of the frame's 28px chamfer.
src2 = Image.open("../site/assets/screens/2-what-was-measured.png").convert("RGB")
bg2 = src2.getpixel((300, 300))

def padded(im, m):
    out = Image.new("RGB", (im.width + 2 * m, im.height + 2 * m), bg2)
    out.paste(im, (m, m))
    return out

startup = src2.crop((313, 517, 624, 854))
background = src2.crop((638, 517, 948, 854))
memory = src2.crop((962, 517, 1273, 854))
# Drive space is 21px taller than the others: drop 21 rows from its empty middle band so the rows line up.
drive = Image.new("RGB", (311, 337), bg2)
drive.paste(src2.crop((962, 145, 1273, 320)), (0, 0))
drive.paste(src2.crop((962, 341, 1273, 503)), (0, 175))

gap, m = 15, 24
cw, ch = 311, 337
desk = Image.new("RGB", (2 * cw + gap + 2 * m, 2 * ch + gap + 2 * m), bg2)
desk.paste(startup, (m, m))
desk.paste(background, (m + cw + gap, m))
desk.paste(drive, (m, m + ch + gap))
desk.paste(memory, (m + cw + gap, m + ch + gap))
save(desk, "scan-desktop")

top, bottom = padded(startup, 20), padded(background, 20)
mobile2 = Image.new("RGB", (top.width, top.height + bottom.height - 20), bg2)
mobile2.paste(bottom, (0, top.height - 20))
mobile2.paste(top.crop((0, 0, top.width, top.height - 20)), (0, 0))
save(mobile2, "scan-mobile")

# One tap section (screenshot 3, "Speed up my computer" result).
#   fix-desktop: the 7/7 header and the 7 step cards, without the totals row (that belongs to Proof)
#   fix-tablet:  4 step cards in a 2x2 grid (Responsiveness, Power plan / Junk files, Recycle Bin)
#   fix-mobile:  Responsiveness, Junk files and Recycle Bin cards stacked
src3 = Image.open("../site/assets/screens/3-speed-up-result.png").convert("RGB")
bg3 = src3.getpixel((1270, 300))
save(src3.crop((316, 138, 1594, 632)), "fix-desktop")

cards3 = {
    "tweaks": src3.crop((328, 294, 634, 461)),
    "power": src3.crop((644, 294, 950, 461)),
    "junk": src3.crop((644, 471, 950, 618)),
    "bin": src3.crop((960, 471, 1266, 618)),
}

def stack(rows, m, gap):
    # rows: list of lists of card images; each row as tall as its tallest card
    w = max(sum(c.width for c in r) + gap * (len(r) - 1) for r in rows)
    h = sum(max(c.height for c in r) for r in rows) + gap * (len(rows) - 1)
    out = Image.new("RGB", (w + 2 * m, h + 2 * m), bg3)
    y = m
    for r in rows:
        x = m
        for c in r:
            out.paste(c, (x, y))
            x += c.width + gap
        y += max(c.height for c in r) + gap
    return out

save(stack([[cards3["tweaks"], cards3["power"]], [cards3["junk"], cards3["bin"]]], 24, 10), "fix-tablet")
save(stack([[cards3["tweaks"]], [cards3["junk"]], [cards3["bin"]]], 20, 10), "fix-mobile")

# Proof section (screenshot 4): the Before and After card only (no drawn callout, no red arrow).
#   proof: the card with its empty middle cut out (label column + Before column + After column) and the
#          footnote row dropped, so it stays readable in a 5-column frame. Margin clears the 28px chamfer.
src4 = Image.open("../site/assets/screens/4-startup-programs.png").convert("RGB")
bg4 = src4.getpixel((300, 700))
top4, bottom4 = 587, 943          # card edges (1px border rows included)
rows_end, foot_start = 900, 925   # keep the rows, then the card's bottom padding and border
parts = [(313, 560), (1140, 1262), (1475, 1597)]
cw4 = sum(b - a for a, b in parts)
ch4 = (rows_end - top4) + (bottom4 - foot_start)
card4 = Image.new("RGB", (cw4, ch4), bg4)
x = 0
for a, b in parts:
    card4.paste(src4.crop((a, top4, b, rows_end)), (x, 0))
    card4.paste(src4.crop((a, foot_start, b, bottom4)), (x, rows_end - top4))
    x += b - a
m4 = 24
proof = Image.new("RGB", (cw4 + 2 * m4, ch4 + 2 * m4), bg4)
proof.paste(card4, (m4, m4))
save(proof, "proof")
