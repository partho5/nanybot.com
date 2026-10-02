# Build log: AI Laptop Solver landing page

Internal file. Never upload. One section is built per session. This file tells the next session where things stand
and how to work.

## How to run a session

1. Read this file, then the section you are building in `landing-page-plan.md` (and plan sections 2, 3 and 6 if
   you have not read them this session: words, look, SEO).
2. Build **only the next section** marked `next` in the progress table. Don't start the one after it.
3. Insert it in `site/index.html` inside `<main>`, after the last built section, in plan order.
4. Rebuild the CSS, take screenshots at the test widths, look at them, fix what looks wrong (see "Check").
5. Update this file: mark the section `done`, mark the next one `next`, add a line to the session log, and note
   any new shared class, asset or decision.
6. Don't commit unless Partho asks.

## Progress

| # | Section | id | Status | Notes |
|---|---|---|---|---|
| 0 | Top bar + mobile menu | — | done | sticky, `<details>` menu below 768px |
| 1 | Hero | `top` | done | |
| 2 | Deep scan | `scan` | done | 2×2 crop of screenshot 2; list reveal |
| 3 | AI review | `ai` | done | centered column, 3 verdict rows in HTML |
| 4 | One tap | `fix` | done | 3 crops of screenshot 3; steps tick green |
| 5 | Safe | `safe` | done | 7/5 2×2 with a 1px cross; ends in a 240px blend band to `clean` |
| 6 | Proof | `proof` | done | white rows with giant after numbers (count from before value); `proof` crop of screenshot 4 |
| 7 | Backup | `backup` | done | text only; 3 outlined numerals joined by a line, 03 filled green |
| 8 | vs repair shop | `compare` | done | 4/8 split: H2 + side note left, rows right; our column on a white chamfered panel |
| 9 | Pricing | `pricing` | done | giant centered headline; Free / Pro chamfered panels, Pro = 2px `scan` edge on white |
| 10 | FAQ | `faq` | done | narrow column of 12 white `<details>` rows; `FAQPage` JSON-LD; ends in the 240px blend band to `void` |
| 11 | Final call to action | `start` | done | centered chamfered panel with HUD brackets, grid inside, scan line sweeps once on scroll |
| — | Footer | — | done | 4/3/2/3 columns (2 on tablet, 1 on phones), 48px link rows, mono bottom line |
| — | Legal pages re-skin | — | done | privacy, terms on `site.css`; text unchanged (checked by a text diff) |
| — | Refund page | — | done | `site/refund/`, from `refund-policy-draft.md`; draft's 5 open points still need Partho |
| — | robots.txt, sitemap.xml | — | done | 4 URLs in the sitemap |
| — | og:image | — | **next** | 1200×630, plan section 8 |

## Files

    site/index.html             the landing page (being built section by section)
    site/assets/site.css        compiled Tailwind output. Never edit by hand
    site/assets/v1.css          old stylesheet, still used by privacy/, terms/ and index-v1.html until they are re-skinned
    site/assets/fonts/          6 self-hosted woff2 files (copied by npm run fonts)
    site/assets/scan.mp3        6.4 s hero sound (~77 KB), cut from tailwind/sound/
    site/assets/screens/        store screenshots + generated crops (hero-*, scan-*, fix-*, .png + .webp)
    site/privacy/ terms/ refund/  legal pages (light, same top bar and footer as the landing page)
    site/robots.txt, sitemap.xml
    site/index-v1.html          old page, kept for reference. Remove from site/ before upload
    tailwind/input.css          Tailwind source: tokens, fonts, base, shared component classes
    tailwind/images.py          makes image crops, WebP copies and icons from the screenshots (Pillow)
    tailwind/sound/             full 98 s Pixabay sound (3 MB, source only; not uploaded)
    tailwind/fonts.mjs          copies fonts from node_modules
    tailwind/package.json       build scripts

## Commands

    cd tailwind
    npm install          # first time only
    npm run build        # compile site/assets/site.css (run after every HTML or CSS change)
    npm run watch        # same, on every save
    python3 images.py    # re-make crops and icons after adding a crop to the script
    npm run fonts        # only if fonts are missing

Tailwind scans everything in `site/` except `index-v1.html`, so new classes in the HTML are picked up by the build.

## Check (every section)

Screenshot at 1440, 1024, 768, 375 and 320 px wide with headless Chrome, then read the images:

    S=<scratchpad dir>; cd site
    for w in 1440 1024 768 375 320; do
      google-chrome --headless=new --disable-gpu --hide-scrollbars --virtual-time-budget=4000 \
        --window-size=$w,2400 --screenshot=$S/shot-$w.png "file://$PWD/index.html#<section-id>"
    done

Count-up runs very slowly under `--virtual-time-budget` (headless throttles `requestAnimationFrame`), so the
screenshot shows half-counted numbers. Add `--force-prefers-reduced-motion` to see the final values.

Look for: no sideways scroll, nothing cut off or overlapping, headings that wrap well, tap targets 48px or more,
and that the section's layout is different from the section before it (plan 3.5 and the layout table in plan 5).
`--virtual-time-budget` lets one-time animations finish before the screenshot.

## Conventions set so far

**Tokens.** Only the plan's colours exist (`@theme` resets the Tailwind palette). Use them as utilities:
`bg-void`, `bg-night`, `bg-panel`, `border-line-dark`, `text-text-hi`, `text-text-lo`, `bg-scan`, `text-scan-ink`,
`text-signal`, `bg-warn`, `bg-alert`, `bg-clean`, `text-ink`, `text-ink-2`, `text-scan-deep`, `border-line-light`.
Fonts: `font-display` (Chakra Petch 600/700), `font-sans` (Plex Sans 400/600, default; 500 italic for FAQ questions only), `font-mono` (Plex Mono 500). All self-hosted, never Google Fonts (plan 6 and 7: no third-party requests).

**Shared classes** (in `tailwind/input.css`, `@layer components`). Reuse these before adding new ones:

| Class | What |
|---|---|
| `.shell` | page width: max 1200px, 16px gutter on phones, 32px from 768px |
| `.btn`, `.btn-lg` | green chamfered button (cut bottom-right; fill on `::before` so the focus ring is not clipped). 48px / 60px tall |
| `.bridge` | cyan mono bridge line with a leading rule (rule stays on the first line when the text wraps). Markup: `<p class="mt-20 lg:mt-24"><a class="bridge" href="#next"><span>Question? <span aria-hidden="true">↓</span></span></a></p>` |
| `.hud-grid` | faint 48px grid (hero and final CTA only) |
| `.shot` + `.shot-frame` | screenshot frame: 1px line, top-left chamfer (`--cut`, default 28px) |
| `.hud .hud-tl/-tr/-bl/-br` | cyan corner brackets for a `.shot` (tl is a diagonal along the cut) |
| `.scanline` | one-time cyan sweep over a `.shot` (hero and final CTA only) |
| `.readout` + `.readout-dot` | mono label pinned to a frame (amber square dot) |
| `.h2` | section heading: Chakra Petch 700, `clamp(2rem, 4.2vw, 3rem)`, balanced. Add the colour class |
| `.label` | mono 14px uppercase label above a list or block (`text-text-lo`; override the colour if needed) |
| `.tag` | mono tag with a 1px `line-dark` border, 40px tall, for a wrapping row of tags |
| `data-reveal` | on a list: its children fade up one by one (150 ms apart) when scrolled into view. Give each child `style="--i:0"`, `"--i:1"`, ... The script adds `.reveal-ready`/`.is-in`; no JS or reduced motion = shown at once |
| `.verdict-row` | `panel` row, 16px chamfer bottom-right, grid: content + action (action moves right from 640px) |
| `.verdict` + `.lock` | mono verdict badge, border from its text colour (`text-signal` or `text-scan`); `.lock` is the 12×14 inline lock SVG |
| `.pro` | small mono `PRO` tag with a `scan` border; sized on its own, so it sits inside an H2 (use again in Backup) |
| `.tap-btn` | picture of the app's "Speed up my computer" button: outlined `scan`, chamfered, `aria-hidden`, not a link |
| `.steps` + `.step` + `.tick` | numbered step line joined by a 1px line: vertical below 1280px, 7 columns from 1280px. `data-reveal="tick"` on the `<ol>` makes the ticks turn green one by one (250 ms apart) instead of fading the items in |
| `.safe-grid` + `.safe-num`/`.safe-title`/`.safe-line` | 4 big statements: stacked with lines on phones, 7/5 2×2 divided by a 1px cross from 768px. Title = white label + green answer on its own line (`block text-scan`) |
| `.on-light` | put on every light section (6 to 10): focus ring turns `ink`, `.bridge` turns `scan-deep` (cyan is too faint on `clean`), `.label` turns `ink-2` |
| `.proof-table` > `.proof-inner` | white panel with a 1px `line-light` edge and 28px chamfer bottom-right (outer = line colour + 1px padding, inner = white). Reuse for the compare table and pricing columns if it fits |
| `.proof-row` (+ `.proof-head`, `.proof-label`, `.proof-before`, `.proof-arrow`, `.proof-after`) | before/after row: label on top on phones; 4 columns from 768px |
| `data-count-from` | on a number: the script counts it once from that value to the number in the HTML (1.2 s, ease-out) when scrolled into view. Wrap it in `aria-hidden` and put the final value in an `sr-only` span |
| `.on-light .pro` | `PRO` tag on light sections: `scan-deep` border and text (`scan` is too faint on `clean`) |
| `.backup-steps` (+ `.backup-head`, `.backup-num`, `.backup-rule`, `.backup-body`, `.backup-title`, `.backup-line`) | 3 steps with big outlined numerals (`-webkit-text-stroke`, `.is-done` = filled `scan-deep`) joined by a 1px `line-light` rule: numeral left + vertical rule on phones, 3 columns + horizontal rule from 768px |
| `.compare` (+ `.compare-head`, `.compare-row`, `.compare-shop`, `.compare-ours`, `.mark`) | 2-column comparison rows. White panel (1px `line-light` edge, 28px chamfer) drawn by `::before`/`::after`: covers the whole list on phones (rows stack shop over ours, head = marker legend), only the right column from 768px (head = column heads). Split is `--split`: 50% at 768, 5/12 from 1024. `.mark` = empty square for the shop side |
| `.on-light .tick` | `.tick` on light sections: `scan-deep` fill, white check (`scan` is too faint on white) |
| `.price-title` | pricing headline, the biggest text on the page: Chakra Petch 700, `clamp(3rem, 9vw, 7.5rem)` |
| `.price-cols` > `.price-col` (+ `.price-pro`, `.price-name`, `.price-for`, `.price-list`, `.price-notes`) | Free / Pro columns, stacked below 768px. Chamfered panel by `::before` (edge) / `::after` (fill): plain = 1px `line-light` on `clean`, `.price-pro` = 2px `scan` on white. `.price-list` rows have a `.tick` (outlined in Free, filled in Pro) and 1px lines; `.price-notes` = mono 14px lines with green squares |
| `.faq-row` (+ `.faq-q`, `.faq-icon`, `.faq-a`) | `<li>` around a `<details>`: white row, 1px `line-light` edge, 12px chamfer bottom-right (drawn on the `<li>` so the focus ring is not clipped). `h3.faq-q` inside `<summary>`; `.faq-icon` = outlined square with `+`, filled `scan-deep` with `−` when open; open row's edge turns `scan-deep` (`:has`). 64px rows, 72px from 768px. Answer goes in `div.faq-body > p.faq-a`: open/close slides and fades in 0.3 s (`::details-content` grid row 0fr → 1fr, `@starting-style` for the fade-in; browsers without `::details-content` open at once). Question text is Plex Sans Medium 500 Italic, not Chakra Petch |
| `.shot-panel` | solid `void` fill inside a `.shot-frame` that holds content instead of a screenshot (clipped to the frame's chamfer) |
| `data-sweep` | on a `.scanline`: the sweep waits until the reveal script adds `.is-in` (scrolled into view) instead of running on load. No JS or reduced motion = no sweep |
| `.cut-br`, `.cut-tl` | plain chamfer via `clip-path` for other elements (uses `--cut`) |
| `.foot-link` | footer link: `text-lo`, 48px tall row, `text-hi` on hover. Reuse in the legal pages' footer |
| `.legal-tabs`, `.legal` | legal pages: Privacy / Terms / Refund tab row (`aria-current` = green underline), and the 70ch prose column (Plex Sans H2s, green square bullets, `scan-deep` links) |
| `.menu`, `.menu-icon` | mobile menu |

**Section markup.** `<section id="..." aria-labelledby="...-title" class="...">`, one H2 per section with that id,
content inside `.shell`. Hero spacing is `pt-14 pb-[88px] md:pt-24 lg:pt-28 lg:pb-32`. Other sections use the
plan's 128–160px desktop and 88–96px mobile padding (for example `py-[88px] lg:py-36`). Each section ends with its
bridge line. `scroll-margin-top` for the sticky bar is already set for `section[id]`.

**Images.** Each crop is made by `tailwind/images.py` as PNG + WebP. Use `<picture>` with a WebP `<source>` and a
PNG `<img>`, real `width`/`height`, descriptive `alt`, `loading="lazy"` (only the hero uses
`fetchpriority="high"`). A full 1920px dashboard is unreadable on phones and tablets: make readable crops for
small screens, as the hero does.

**Motion.** Only what plan 3.4 lists, once, CSS first. The global `prefers-reduced-motion` rule in `input.css`
turns it off. The page has one inline script at the end of `<body>` (menu close, `data-reveal`). Add count-up code to that
same script and keep the total under about 80 lines. Content must be visible without JavaScript: hide things for a
reveal only after JS has added a class, never by default.

**Store link:** `https://apps.microsoft.com/detail/9PJG3G7PKJJR`

## Decisions (differ from the plan)

- H1 is capped at `3.25rem` on desktop (plan says up to 5rem). At 5rem it broke into 5 lines in the 5/12 column.
- Hero micro line uses small green squares between items instead of "·". With "·", a dot was left at the start
  of a line on wrap.
- Old CSS renamed to `v1.css` so the legal pages keep working until they are re-skinned.
- H2 is capped at `3rem` (plan says up to 3.5rem) so it never outgrows the capped H1.
- Deep scan image is not a plain crop: `images.py` puts the 4 problem cards of screenshot 2 (Startup, Background /
  Drive space, Memory) in a 2×2 grid, so the text is near 1:1 in the 7-column frame. The 3×2 crop was unreadable.
  Phones get Startup + Background stacked. Crops keep 20 to 24px of margin so the 28px chamfer misses the cards.
- Screenshot 2 was taken at a different time than screenshot 1: it shows 24 startup programs and 195 background
  programs, while the "found" list (from the plan, matching screenshot 1) says 26 and 198. Alt text describes
  the image as it is. A fresh screenshot 2 from the same scan would fix it.
- Deep scan: the 9 checks have a small `h3` label ("The 9 checks"); the plan gives no label.
- AI review: section has a 1px `line-dark` top border so it separates from Deep scan (both `night`). The
  Keep / Turn off pair is a picture of the app's control, not a control: `aria-hidden`, with an `sr-only`
  "AI picks: Turn off." Row label is "Startup programs / 3 of 26" (plan gives none). Centered bridge from 768px,
  left-aligned on phones (a centered wrapped bridge looked broken).
- Screenshots: `#id` in the URL does not scroll in headless Chrome. Use a 7000px-tall window and crop to the
  section (the last section ends where the `void` body background starts).
- One tap: the 7 steps go horizontal only from 1280px (at 1024 the labels wrapped to 3 lines). The app button
  picture is outlined, not filled green, so it is not mistaken for a real button; it is green, not the app's
  orange, because warning colours stop after section 2. The sub line sits under the step line. The section has a
  1px `line-dark` top border like AI review.
- One tap images: desktop = the 7/7 header and 7 step cards (totals row left out: it is Proof's material);
  768–1023px = 2×2 of Responsiveness, Power plan, Junk files, Recycle Bin; phones = Responsiveness, Junk files,
  Recycle Bin stacked. **Open question for Partho:** the desktop crop shows the Background apps card's message
  "couldn't reach the classifier to check which were safe to close — so nothin…". That is a failure message on
  the "it works" section. A fresh screenshot 3 where that step succeeded would be better.
- Safe: left-aligned H2 (One tap is centered) with "Undo any time." in `scan`. Each statement's answer half is
  green and on its own line, so all 4 share one rhythm (balanced wrapping split "even / after restart" badly).
  Small cyan mono numbers 01–04 (plan gives none). Statements are `h3`. No motion. The section has no bottom
  padding: after the bridge comes the blend band (`h-60 bg-linear-to-b from-night to-clean`, `aria-hidden`).
  **Proof must start on `bg-clean` with no top border** so the band runs straight into it. Until Proof exists,
  the band ends on the `void` body background.
- Proof: the "after" numbers count from the **before** value (50→53, 273→5, 26→24, 24.6→22.2), not from 0, so
  the change is what moves; "Empty" does not count. Plan says "count up". Final values are in the HTML (no JS =
  final values); each animated number is `aria-hidden` with an `sr-only` copy.
- Proof layout: H2 + sub left (7 cols) with the screenshot on the side (5 cols) in the first row; the 5 rows go
  full width below so the giant numbers have room. Phones: heading, rows, totals, note; the screenshot is
  **hidden below 640px** (at 50% scale it was unreadable and the readout covered rows; its numbers are the rows).
  The Before/After column head is `aria-hidden`; rows read as "Junk files 273 MB to 5 MB".
- Proof image (`proof`): screenshot 4's Before and After card with the empty middle cut out (label, Before and
  After columns spliced) and the footnote row dropped; 539×379. Callout and red arrow are gone. The app says
  "Emptied" and "Softwares starting with Windows"; the HTML rows use the plan's "Empty" and "Programs at start".
- Proof starts at `pt-12 lg:pt-16`: the 240px blend band above already gives the space.
- Backup: left H2 with "Lose nothing." in `scan-deep` and the `PRO` tag. Numerals 01 and 02 are outlined, 03 is
  filled green (the payoff). No motion. 1px `line-light` top border so it separates from Proof (both `clean`).
  The hint line is 3 mono items with green squares (same style as Proof's totals strip), above a 1px rule.
  "Wi-Fi" is kept on one line (`whitespace-nowrap` / `&nbsp;`): it broke at the hyphen at 768px.
  When the Backup / Restore screenshots exist, add one below the steps.
- vs repair shop: H2 + side note in a 4-column strip on the left (note pinned to the bottom, beside the last row),
  rows in 8 columns (from 1024px; stacked below). Plain ink H2, no green part (green on "repair shop" read wrong)
  and no sub line (plan gives none). Only our column sits on the white panel, so the shop side looks "unboxed".
  Shop items get an empty square, ours a filled `scan-deep` tick; the plan asks only for colour, but the markers
  make the stacked phone rows readable. Ours text is Chakra Petch 600. "(Pro)" is the `.pro` tag. Each cell has an
  `sr-only` "Repair shop:" / "AI Laptop Solver:" prefix; the visible head is `aria-hidden`. No motion. 1px
  `line-light` top border like Backup.
- Pricing: centered headline and sub (Compare is a left 4/8 split). "free." is `scan-deep`. Both columns are
  chamfered panels so they line up; Pro gets the 2px `scan` edge, white fill, green name and filled ticks, Free an
  outlined look. Column titles are `h3` "Free: Find and clean" / "Pro: More comfort, all automatic" (colon is
  `sr-only`). The 3 mono notes sit inside the Pro panel; "30-day refund" links to `refund/`.
  One "Start free" button below both columns, centered. Added a line under it that the
  plan only states as a rule: "Prices are shown in the app before you pay." (the bridge before asks "How much
  does it cost?", so the page says where the price is). No motion. 1px `line-light` top border like Compare.
  **FAQ must end with the 240px blend band from `clean` to `void`** (plan 3.1, between 10 and 11).
- FAQ: the plan gives no H2; used "Questions? Short answers." ("Short answers." in `scan-deep`). Narrow 800px
  column, centered block with left-aligned text (Pricing above is centered text with wide panels). Rows are
  separate white chamfered rows, all closed by default, no motion. Questions and answers are the plan's text word
  for word; the same text is in the `FAQPage` JSON-LD (refund answer links to the absolute refund URL there).
  "64-bit" and "1-tap" are kept on one line. No bridge line: the plan gives none, the blend band leads into the final CTA.
  Partho asked for two things after review: (1) a smooth open/close (0.3 s slide + fade; a motion the plan does
  not list, CSS only, off with reduced motion), and (2) questions in IBM Plex Sans Medium 500 Italic (self-hosted, 26 KB) instead of Chakra Petch,
  because the FAQ should feel personal and the sci-fi font read as insincere there. The H2 stays Chakra Petch.
  To check motion, use Playwright (installed globally: `PW=$(npm root -g)/playwright`) and sample heights per
  frame; `--virtual-time-budget` does not run transitions.
  Section has `pt` only; the blend band (`h-60 bg-linear-to-b from-clean to-void`) closes it, so **the final CTA
  must start on `bg-void` with no top border**.
- Screenshot trick for a lower section: copy `index.html` to a temporary `site/_test.html` with
  `<style>main>section:not(#faq){display:none}</style>` and `open` on some `<details>`, screenshot, then delete it.
- Sound on load (Partho's call; not in the plan): `assets/scan.mp3` plays once at 50% volume with the hero scan
  line. It is the first sound of the Pixabay track, 0 to 6.4 s (one continuous 98 s track; the second sound starts
  at about 6.5 s), mono 96 kbps, 0.3 s fade-in and 1.5 s fade-out
  (`ffmpeg -t 6.4 -af "afade=t=in:d=0.3,afade=t=out:st=4.9:d=1.5" -ac 1 -b:a 96k`). Partho chose this over
  playing the full 98 s track with a mute button. Browsers block sound before the first
  click, tap or key press, so when the load-time play is blocked it plays on the first such action, but only
  while the hero is still on screen. Script is now ~75 lines.
- Final CTA: the plan gives no screenshot, so the hero's frame is reused as an empty "scan area": a centered
  880px chamfered `.shot` with all 4 HUD brackets, `void` fill, the 48px grid **inside** the frame (radial fade),
  and the scan line sweeping once when scrolled into view (the hero's runs on load). Copy is the plan's word for
  word; "Today." is `scan`. Micro line uses green squares like the hero decision. Added a `READY TO SCAN` readout
  with a green dot (the hero's says `SCAN COMPLETE` with amber; amber stops after section 2), `aria-hidden`.
  No bridge line (last section). Starts at `pt-12 lg:pt-16` on `void` straight after FAQ's blend band, no top
  border. No sound. **Footer** follows on `void`: give it a 1px `line-dark` top rule so it separates.
- Footer: copy is the plan's word for word. Column 1's "Store button" is the green `.btn` "Get it free" (same as
  the top bar). Column titles are `.label` paragraphs, not headings (plan: one H2 per section, H3 only inside
  sections); Product and Legal are `<nav aria-labelledby>`. Plain Plex Sans links (no Chakra Petch: help text).
  The bottom line has no "·": © left, "Made for Windows 10 and 11." right from 768px, stacked on phones. The
  brand row is pulled up 14px from 768px so its text lines up with the column labels. "Refund Policy" links to
  `refund/`. **Legal pages must copy this footer** with `../` links (`../#scan`, `../privacy/`).
- Legal pages: dark top bar and footer copied from `index.html` with `../` links (`../#scan`, `../privacy/`;
  the footer marks the current page `aria-current`), light `main` (`on-light bg-clean`), tabs, Chakra Petch H1,
  everything else Plex Sans. Own title, description, canonical and `BreadcrumbList`. **If the top bar or footer
  changes in `index.html`, copy the change into all 3 legal pages.** No script on them (menu links leave the page).
- Refund page: the draft's text, with the bracketed values filled as drafted (2 working days, 5 to 10 working
  days) and dated 2 October 2026. The draft's 5 "points for you to check" are still open for Partho.
- Rule from Partho (2026-10-02): never leave a link to a missing page. If a section links to a page that does
  not exist, build that page in the same session.
- Hero has 3 image crops: desktop (no title bar, no empty left margin), tablet 768–1023px (2 cards side by side),
  phone (2 cards stacked).

## Session log

- 2026-10-01: Set up `tailwind/` build (Tailwind v4 CLI, fontsource fonts, image script). Built top bar, mobile
  menu and hero. Checked at 320–1920px.
- 2026-10-01: Built Deep scan (`#scan`): H2/sub, 7/5 split (screenshot left from 1024px, text first below),
  found list with reveal, Speed Score 63/100 with a meter, 9 check tags. Added `.h2`, `.label`, `.tag`,
  `data-reveal`; fixed `.bridge` wrapping. Checked at 320–1440px.
- 2026-10-01: Built AI review (`#ai`): centered H2/sub, 3 verdict rows (2 "Not needed at start" with
  Keep/Turn off, 1 "Protected" locked), hint line, bridge to `#fix`. Added `.verdict-row`, `.verdict`, `.lock`.
  Checked at 320–1440px.
- 2026-10-01: Built One tap (`#fix`): centered H2 with `PRO` tag, picture of the app button, 7-step line with
  ticks, sub, screenshot 3 in 3 crops with `7/7 STEPS DONE` readout, Advanced hint, bridge to `#safe`. Added
  `.pro`, `.tap-btn`, `.steps`/`.step`/`.tick`, `data-reveal="tick"`, fix crops in `images.py`. Checked at
  320–1440px (and 1280).
- 2026-10-01: Built Safe (`#safe`): left H2, 4 statements in a 7/5 2×2 with a 1px cross (stacked below 768px),
  closing line, bridge to `#proof`, 240px blend band to `clean`. Added `.safe-grid`, `.safe-num`, `.safe-title`,
  `.safe-line`. Checked at 320–1440px.
- 2026-10-01: Built Proof (`#proof`): H2/sub with the `proof` crop on the side, 5 before/after rows on a white
  chamfered panel with count-up, totals strip, date note, bridge to `#backup`. Added `.on-light`, `.proof-*`,
  `data-count-from` (script now ~60 lines), `proof` crop in `images.py`. Checked at 320–1440px.
- 2026-10-01: Built Backup (`#backup`): left H2 with `PRO` tag, 3 steps with big outlined numerals and a joining
  rule (vertical on phones, horizontal from 768px), 3-item hint line, bridge to `#compare`. Added `.backup-*`,
  `.on-light .pro`. Checked at 320–1440px.
- 2026-10-01: Added the hero sound on load (`assets/scan.mp3`, see Decisions).
- 2026-10-01: Built vs repair shop (`#compare`): left H2 and side note, 5 rows with our column on a white
  chamfered panel (stacked with a legend on phones), bridge to `#pricing`. Added `.compare-*`, `.mark`,
  `.on-light .tick`. Checked at 320–1440px.
- 2026-10-01: Built Pricing (`#pricing`): giant centered "Start for free.", sub, Free and Pro columns (Pro with
  green edge, notes and refund link), "Start free" button, price note, bridge to `#faq`. Added `.price-*`.
  Checked at 320–1440px.
- 2026-10-02: Built FAQ (`#faq`): H2, 12 `<details>` rows on white chamfered rows with +/− marker, `FAQPage`
  JSON-LD in `<head>`, blend band to `void`. Added `.faq-*`. Checked at 320–1440px (closed and open rows).
- 2026-10-02: FAQ follow-up: smooth open/close transition, questions in Plex Sans (Partho's request).
- 2026-10-02: Built Final call to action (`#start`): centered chamfered panel with HUD brackets and grid, H2,
  sub, "Scan my PC free", micro line, `READY TO SCAN` readout, scan line sweep on scroll. Added `.shot-panel`,
  `data-sweep` (reveal script now also observes it). Checked at 320–1440px and the sweep with Playwright.
- 2026-10-02: Built the footer: brand + line + "Get it free", Product / Legal / Help link columns, bottom line
  with a 1px rule. Added `.foot-link`. Checked at 320–1440px.
- 2026-10-02: Built the missing pages the footer links to: re-skinned privacy and terms (text unchanged) and
  built refund. Added robots.txt and sitemap.xml, `.legal-tabs` and `.legal`. Every relative link in `site/`
  resolves. Checked at 320–1440px.
