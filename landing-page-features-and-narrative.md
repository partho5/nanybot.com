# AI Laptop Solver landing page: build brief (single source of truth)

> **Superseded on 1 October 2026 by `landing-page-plan.md`** for tone, story, copy, layout and look. Only Part A's
> deploy rules (A2, A3, A8) and the legal-text rule still apply. Kept for reference.

Written 1 October 2026 for the AI agent that builds the landing page. Everything you need is in this file. Read all of
it before you write any code. Part A is how to build it. Part B is what the page says.

Sources this file was built from: `README.md` (build rules and design brief), `NANYBOT-LANDING-GUIDE.md` (how the
site is deployed), `FEATURES.md` (feature inventory from the code), the current `site/` pages and the four
screenshots. Where they disagree, this file wins. Do not read `FEATURES.md` to add features: anything not in Part B
is either not built, not reachable by users, or not worth telling a visitor.

**Internal file. Never upload it.** Only the contents of `site/` go to the server.

---

# PART A: TECHNICAL DIRECTION

## A1. What you are building

A small static website for **AI Laptop Solver**, a Windows app sold through Microsoft Store by **Protim Soft**.
English only, for a global audience. Three pages:

| URL | File | What to do |
|---|---|---|
| `https://nanybot.com/ai-laptop-solver/` | `site/index.html` | Rewrite completely from Part B |
| `https://nanybot.com/ai-laptop-solver/privacy/` | `site/privacy/index.html` | Re-skin only. Text stays word for word |
| `https://nanybot.com/ai-laptop-solver/terms/` | `site/terms/index.html` | Re-skin only. Text stays word for word |

Privacy and terms text are legally approved (privacy dated 29 September 2026, terms 1 October 2026). Move their
existing text into the new layout without changing a word, heading or date. The deploy check greps for
`Last updated 29 September 2026` on the privacy page, so that exact string must survive.

Store link (the only call to action): `https://apps.microsoft.com/detail/9PJG3G7PKJJR`

## A2. Hard rules (from the deploy setup; breaking one fails deployment)

1. **Static files only.** The server has no Node, no PHP and no build step. You build on Partho's PC and only the
   finished files in `site/` are uploaded.
2. **Tailwind is compiled locally** into one minified file, `site/assets/site.css`. Never use the Tailwind CDN script.
   Tailwind's source files live outside `site/` (see A3) and are never uploaded.
3. **Zero third-party requests.** No external fonts, scripts, analytics, CDNs, embeds, icon fonts or tracking pixels.
   Fonts are self-hosted in `site/assets/fonts/`. Outbound `<a href>` links are fine (the Store link, `mailto:`).
4. **JavaScript:** only a few lines of inline JS for the mobile menu. Everything else works without JS (the FAQ uses
   `<details>`/`<summary>`).
5. **All links relative** (`assets/site.css`, `privacy/`, `../assets/site.css` from subpages, `./` for home), so the
   folder name does not matter. Each page is `<folder>/index.html`. Exceptions: `canonical` and Open Graph URLs, which
   must be absolute (see A9).
6. **Keep the placeholder `SUPPORT_EMAIL`** literally, in all three pages (footer `mailto:SUPPORT_EMAIL` and visible
   text, plus wherever privacy/terms already have it). The deploy agent replaces it with `sed`. Do not invent an
   address.
7. **Nothing else in `site/`**: no `.md`, `package.json`, config, source CSS, `.git` or `node_modules`.

## A3. Folder layout and build

```
E:\Development\ai-laptop-solver-site\
  tailwind\                 build tooling, NEVER uploaded
    package.json            devDependencies: tailwindcss, @tailwindcss/cli (v4), @fontsource/ibm-plex-sans, @fontsource/ibm-plex-mono
    input.css               Tailwind entry: @import, @source, @theme tokens, @font-face, small component layer
    build.ps1               (optional) npm ci + compile + copy fonts
  site\                     the ONLY folder that is uploaded
    index.html
    privacy\index.html
    terms\index.html
    assets\
      site.css              compiled + minified Tailwind output
      fonts\*.woff2         self-hosted, latin subset only
      icon.png              existing app icon (keep)
      screens\1-home-speed-score.png ... 4-startup-programs.png   (keep, see A7)
```

Tailwind v4, CSS-first config. `input.css` starts with:

```css
@import "tailwindcss" source(none);
@source "../site/**/*.html";
```

Build command (run it yourself, from `tailwind\`):

```
npm install
npx @tailwindcss/cli -i input.css -o ../site/assets/site.css --minify
```

Copy the needed `.woff2` files (latin subset) from `node_modules/@fontsource/...` into `site/assets/fonts/` and write
the `@font-face` rules yourself in `input.css` with relative URLs (`url("fonts/ibm-plex-sans-latin-400-normal.woff2")`
resolves from `site/assets/site.css`). Use `font-display: swap`. Preload only the two most used weights on the
landing page.

## A4. Visual direction

The brief from Partho, in short: **the page must not look AI-designed.** What makes a page look AI-made is the
combination of purple/blue gradients, rounded glass cards, glow, a giant centered headline, floating mockups, lots of
whitespace and generic copy. Do the opposite: strong typography, restrained colour, sharp geometry, real
screenshots, dense useful information, visible product details, few decorations.

Think "spec sheet from a careful engineering company", not "SaaS template".

### Colour (light page, one accent, plus the app's own dark for the product bands)

| Token | Value | Use |
|---|---|---|
| `paper` | `#F6F5F1` | page background (warm off-white, not pure white) |
| `surface` | `#FFFFFF` | tables, FAQ rows |
| `ink` | `#111418` | text, headings |
| `muted` | `#5B6470` | secondary text, captions |
| `rule` | `#D9D6CE` | 1px borders and section dividers |
| `accent` | `#0B7A4B` | links, small highlights, "after" values in tables (passes AA on paper and white) |
| `accent-fill` | `#19C37D` | primary button background only (the app's own green), with `#04170E` text |
| `night` | `#0B0F14` | the app's background colour: full-width bands behind screenshots, so the dark screenshots sit naturally |
| `night-ink` | `#E6E9EE` / `#9AA4B2` | text on `night` bands |
| `warn` | `#B45309` | status word "Needs improvement" when you echo the app's statuses |
| `alert` | `#B42318` | status word "Needs attention" |

No gradients anywhere. No purple, no blue accents. Light theme only (do not add a dark mode; the `night` bands give
contrast).

### Typography

- **IBM Plex Sans** (400, 500, 600, 700) for everything; **IBM Plex Mono** (400, 600) for numbers, measurements,
  table figures and the proof strip. Monospaced figures are what make the page feel like measured data.
- H1: left-aligned, `clamp(2.25rem, 4.6vw, 3.75rem)`, weight 600, line-height 1.05, letter-spacing -0.02em, max ~18
  words. Not centered. Not gradient. Not all caps.
- H2: `clamp(1.75rem, 3vw, 2.5rem)`, weight 600. H3: 1.25rem, weight 600.
- Body: 17px / 1.6 desktop, 16px mobile. Line length 60 to 75 characters.
- Sentence case everywhere. **No tiny uppercase labels above headings.** No eyebrow text.

### Geometry and surfaces

- Border radius: **0** on sections, images, tables. **2px** max on buttons and inputs. Never pills.
- **No box-shadow** anywhere, except a visible focus outline. No blur, no glass, no glow, no translucency.
- Structure comes from 1px `rule` lines and full-width bands, not from cards. Do not wrap sections in cards. Do not
  build grids of "icon + heading + two lines" cards.
- Screenshots: flat, straight, full resolution, 1px `rule` (or `#1F2937` on night bands) border. Never tilted, never
  floating, never in a fake laptop frame, no reflections.
- Icons: avoid them. Where one is unavoidable (menu toggle, checkmark in the plan table), use a simple inline SVG,
  1.5px stroke, `currentColor`. No emoji.
- No illustrations, no blobs, no orbs, no 3D shapes, no particles, no animated anything. Hover states only (colour or
  underline change). Respect `prefers-reduced-motion` by having no motion.

### Layout

- Max content width 1200px, 12-column grid, 24px gutter, 16px side padding on mobile. No horizontal scroll at 320px.
- **Asymmetric** splits that change from section to section (5/7, 7/5, 4/8, full-width table). Not every section
  centered, not every section the same shape.
- Compact vertical rhythm: section padding about 72px desktop, 48px mobile. Information density is a goal: tables,
  numbered lists and specific figures are welcome.
- Sections separated by a full-width 1px rule or a change to a `night` band.

### Components you will need

- **Primary button**: `accent-fill` background, dark ink text, 2px radius, 600 weight, "Get it from Microsoft Store".
  Secondary: text link with an arrow character (→) or a 1px outlined rectangle. Do not use Microsoft's official
  badge image unless Partho supplies it (it would need to be self-hosted and follow Microsoft's badge rules).
- **Data table**: white surface, 1px rules, mono figures right-aligned, header row in `muted` 500 weight (sentence
  case, not uppercase). Used for: the nine readings, the before/after, Free vs Pro.
- **Numbered list** with large mono numerals in the gutter: used for the 7-step plan and the safety rules.
- **Proof strip**: one row of 4 figures in mono, each "before → after" with a short label, wrapping to 2×2 on mobile.
- **FAQ**: `<details>` rows separated by 1px rules, `+`/`−` marker, no cards.
- **Nav**: see A6.

## A5. Avoid list (check every section against it)

Purple/blue gradient; gradient text; huge rounded cards; border-radius on everything; glassmorphism (translucent
cards, blur, glowing borders); dark navy with neon purple; huge centered hero headline; abstract AI blobs; floating
3D shapes or orbs; heavy shadows or glow; every section inside a card; pill buttons and badges; big empty whitespace
with little information; perfectly symmetrical layouts everywhere; illustrations of people around laptops; tilted
floating dashboard mockups; animated gradients or particles; "Trusted by 10,000+ users"; logo clouds; repeated
icon + heading + two-line feature cards; tiny uppercase labels above headings; "Built for modern teams" / "The future
of..." copy; Inter-style generic type with very soft UI; anything that looks too smooth and frictionless.

## A6. Navigation and responsiveness

- Sticky top bar on `paper` with a 1px bottom rule (opaque, no blur). Left: `assets/icon.png` (28px, no rounding
  added) + "AI Laptop Solver" in 600 weight. Right on desktop: links **How it works · Safety · Backup · Pricing ·
  FAQ**, then the primary button "Get it free" (same Store URL).
- Below 768px: links collapse behind a 44×44px menu button (`aria-expanded`, `aria-controls`). The panel opens below
  the bar, full width, links as 48px-tall rows separated by rules, Store button at the bottom. Inline script (≤15
  lines): toggle on click, close on link click and on Escape. No library.
- Subpages (privacy, terms) use the same header with links pointing back to `../#section` and the same footer.
- Test at 320, 375, 768, 1024, 1280 and 1920px widths. Tables: on narrow screens either stack rows (label above
  value) or allow horizontal scroll *inside* the table wrapper only, never the page.

## A7. Screenshots

Real, from a real PC, 1920×1040 PNG, in `site/assets/screens/`. Signed-in email and taskbar already removed. Always
set `width="1920" height="1040"`; `loading="lazy"` on all but the hero one; `fetchpriority="high"` on the hero one.
You may additionally produce `.webp` versions locally and serve them via `<picture>` with the PNG as fallback.

| File | What it shows | Use it in | Alt text |
|---|---|---|---|
| `1-home-speed-score.png` | Dashboard: Speed Score 63/100 "Needs improvement" with trend line, Main Problems Detected list, System specs card, first row of measured cards | Hero | "AI Laptop Solver dashboard: a Speed Score of 63 out of 100, a list of the main problems found on this PC, its system specs, and measured cards for junk files, Recycle Bin, drive space and security" |
| `2-what-was-measured.png` | The nine measured cards, each with status, figure and "Technical reason" | "What it measures" | "Nine measured cards: junk files, Recycle Bin, drive space, security check, startup impact, background processes, memory in use, boot time and CPU headroom, each with a status and the technical reason" |
| `3-speed-up-result.png` | "Speed up my computer: 7/7 steps done", one result tile per step, and "What this software has done for your PC" totals | "Fix it in one tap" | "Result of Speed up my computer: 7 of 7 steps done, with a result for each step and the total space recovered so far" |
| `4-startup-programs.png` | Startup list with AI reasons, Keep / Turn off, "Turn back on", and the Before and After table | "Startup programs" and/or "Proof" | "Startup programs with the AI's reason for each, Keep and Turn off choices, and a before and after table for this PC" |

Known content in these images you must not echo in copy (see B5): screenshot 1 contains the sentence "I can
uninstall them if you want" and an "Ask the AI" button. Neither may be promised on the page. Screenshot 4 has a big
green callout and a red arrow drawn on it. These are open items for Partho (B8); use the images as they are until
replaced.

## A8. Accessibility

`<html lang="en">`; a "Skip to content" link; one H1 per page and no skipped heading levels; text contrast WCAG AA
(4.5:1 body, 3:1 large); visible focus outline (2px `ink` outline, 2px offset) on every interactive element; tap
targets ≥44px; tables use `<th scope>`; the menu button has an accessible name; the arrow characters in "before →
after" have readable text equivalents (for example a visually hidden "to").

## A9. Head, meta and SEO (landing page)

```html
<title>AI Laptop Solver: find out why your PC is slow and fix it safely</title>
<meta name="description" content="AI Laptop Solver measures your Windows PC, explains what is slowing it down in plain words, fixes the safe things in one tap, lets you undo them, and shows the before and after.">
<link rel="canonical" href="https://nanybot.com/ai-laptop-solver/">
<meta property="og:type" content="website">
<meta property="og:title" content="AI Laptop Solver">
<meta property="og:description" content="Find out why your Windows PC is slow. Fix it safely. See the proof.">
<meta property="og:url" content="https://nanybot.com/ai-laptop-solver/">
<meta property="og:image" content="https://nanybot.com/ai-laptop-solver/assets/screens/1-home-speed-score.png">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#F6F5F1">
<link rel="icon" href="assets/icon.png">
```

Optional inline JSON-LD `SoftwareApplication` (name, operatingSystem "Windows 10, Windows 11", applicationCategory
"UtilitiesApplication", offers with price 0, url to the Store). **No `aggregateRating`, no review counts.**
Privacy and terms pages get their own `<title>` and canonical.

## A10. Acceptance checklist (run these yourself before handing back)

- [ ] `site/` contains only: 3 `index.html` files, `assets/site.css`, `assets/icon.png`, `assets/fonts/*.woff2`,
      `assets/screens/*` (and optional `.webp`). Nothing else.
- [ ] `grep -rEn "https?://" site | grep -v nanybot.com` shows only `<a href>` links (Store) and no `src=`,
      stylesheet, font or script from another host.
- [ ] `SUPPORT_EMAIL` still present in all three pages (deploy replaces it).
- [ ] Privacy and terms text unchanged word for word (diff the extracted text before and after); privacy still
      contains `Last updated 29 September 2026`.
- [ ] Every `assets/...` reference resolves to a file that exists.
- [ ] Compiled CSS is minified and contains only used classes; no Tailwind CDN script.
- [ ] Open the page in a browser (serve `site/` with any local static server) and take screenshots at 375px and
      1440px of all three pages. Check: no horizontal scroll, menu opens and closes, FAQ works without JS.
- [ ] No Bangla text, no `border-radius` above 4px, no `box-shadow` except focus, no gradient, no animation.
- [ ] Every claim on the page appears in B4 and none appears in B5.

---

# PART B: NARRATIVE, FEATURES AND COPY

## B1. Who the visitor is, and the one idea

**Visitor:** a Windows 10 or 11 user anywhere in the world whose PC or laptop has become slow, who is not technical,
who has been burned by "PC cleaner" apps that invent scary problems, and who is quietly afraid of breaking
something. A second, smaller group is technical people who will scroll to see whether the claims hold up.

**The one idea:** *Every other cleaner guesses and scares. This one measures, explains in plain words, fixes only
what is safe, lets you undo it, and proves the result.*

**The arc of the page** (each section answers the question the previous one raises):

1. "Why is my PC slow?" → it tells you, with real readings.
2. "Can I trust those numbers?" → every figure is a reading from your PC; nothing is estimated; if your PC is fine,
   it says so.
3. "Can it fix it without me knowing anything?" → one tap, seven steps, a report for each.
4. "Will it break something?" → the safety rules, and undo that survives a restart.
5. "Did it actually help?" → a before and after that cannot be rewritten.
6. "And my files, if I ever reset Windows?" → verified backup, restore, programs and Wi-Fi back.
7. "What does it cost?" → free to start, Pro for the one-tap work.
8. "What do you do with my data?" → most work stays on the PC; plain policy.

## B2. Voice and wording rules

- Plain, calm, specific. Short sentences. Second person ("your PC"). Numbers instead of adjectives.
- Say what it does, then the small technical detail that proves it (the "how we know" line). The detail is what
  makes technical readers trust it and non-technical readers feel safe.
- Honest limits stated openly are a selling point here (it does not run its own antivirus, it does not read drive
  health, results differ by PC). Do not hide them.
- British spelling, to match the privacy policy and terms ("recognise", "optimise" only where it is not a UI string).
- Use the app's own words for its buttons, in the same case: "Speed up my computer", "Run the recommended fixes",
  "Email me the full report", "Test disk speed", "Clear junk files", "Empty Recycle Bin", "Turn back on".
- Say "PC" (covers laptops and desktops). The product name is always "AI Laptop Solver".
- **Banned words and phrases:** transform, revolutionise, supercharge, unleash, seamless, effortless, cutting-edge,
  next-gen, blazing fast, lightning, magic, AI-powered (as a label), "the future of", "built for modern", "trusted
  by", "join thousands", "10x", "boost your productivity", "say goodbye to", any percentage speed promise, any
  invented statistic, any testimonial, any user count, any rating.

## B3. Page outline with copy

Copy below is ready to use. Lines marked *(alt)* are alternatives; pick one. Keep the facts exactly as written.

### 0. Header
Nav as A6.

### 1. Hero (5/7 split: text left, screenshot 1 right, bleeding to the viewport edge on wide screens)

**H1:** Find out why your PC is slow. Fix it safely. See the proof.
*(alt)* Your PC is slow. Here is exactly why, and what changed after the fix.

**Lead:** AI Laptop Solver measures your Windows PC, tells you in plain words what is slowing it down, fixes the safe
things in one tap, and shows you the before and after. Most changes can be undone. If your PC is fine, it says so.

**Buttons:** [Get it from Microsoft Store] · How it works →

**Small line under buttons:** Free to start. Windows 10 (version 2004 or later, 64-bit) and Windows 11. Installed and
updated through Microsoft Store.

**Proof strip** (directly under the hero, full width, mono figures):

| Free space on C: | Junk files | Programs starting with Windows | Startup time |
|---|---|---|---|
| 50 GB → 53 GB | 273 MB → 5 MB | 26 → 24 | 24.6 s → 22.2 s |

Caption under the strip: "One real PC, measured on 4 September and again on 28 September 2026, the PC in the
screenshots on this page. Your results will be different, and the app shows you yours."

### 2. "What it measures" (night band; screenshot 2 wide, table beside or below)

**H2:** Real readings from your PC. Nothing is estimated.

**Intro:** On launch the app measures your PC and fills the dashboard as each reading arrives. Each reading comes with
a status, the figure, and a short "technical reason" that explains it in normal words. A reading it cannot take says
"No reading" and why, instead of a made-up number.

**Table: the nine readings**

| Reading | What it tells you | How it is measured |
|---|---|---|
| Junk files | How much safe-to-delete clutter you have | Temp files, shader and thumbnail caches, update leftovers: things Windows recreates on demand |
| Recycle Bin | How much space your deleted files still hold | Read from Windows; the app always asks before emptying |
| Drive space | Free space on your Windows drive | Windows needs headroom here for updates and memory |
| Security check | Protected, Worth a look, Threat found or Not protected | Windows Security and Microsoft Defender's own status |
| Startup impact | How many programs launch with Windows | Registry, Startup folders, logon tasks and auto-start services |
| Background processes | How many programs are running right now | Counted at scan time |
| Memory in use | How full your RAM is | Past about 88%, Windows starts swapping to disk, which is what stalling feels like |
| Boot time | How long the last startup took | Windows' own Diagnostics-Performance log, not a stopwatch |
| CPU headroom | How much processor time was free | A one-second snapshot, labelled as a snapshot |

**Below the table, three short blocks (asymmetric, not cards):**

- **Speed Score out of 100.** The average of five real readings: boot time, startup programs, memory pressure, free
  space on the Windows drive and background load. It moves only when something real changes, keeps a trend line,
  and if one reading is missing it says "average of 4 of 5" instead of guessing.
- **Problems in plain sentences.** "26 softwares slow down your PC every time it starts." "82% of drive C: is full."
  Worst first. If nothing is wrong: "No problems found. This PC is in good shape."
- **Your PC's specs, including the boring ones that matter.** Windows edition and whether it is activated, RAM size,
  type and speed (for example DDR4 3200), storage type (NVMe SSD, SSD or HDD), processor, graphics, and how old your
  Windows install is ("3 years 3 months"). Plus **Test disk speed**: a one-minute real read and write test in MB/s,
  run with caching off so Windows cannot flatter the result.

**Smaller line:** It also points out crowded Downloads, Documents and Desktop folders, and lists installed programs
you have not opened for 30 days or more, with how long ("1 year 2 months"). A program with no record of when it last
ran is never called unused.

### 3. "Fix it in one tap" (7/5 split: numbered list left, screenshot 3 right)

**H2:** One tap runs the whole routine. You see what every step did.

**Intro:** Press **Speed up my computer**. The app runs seven steps in a safe order, shows "Step 3 of 7" as it goes,
has a Stop button, and ends with a result for every step, including what it skipped and why.

**Numbered list (number, name, one line, and whether it can be undone):**

1. **Responsiveness tweaks.** Three small Windows settings so menus open faster and the disk does less needless
   writing. Your original values are saved first. *Can be undone.*
2. **High Performance power plan.** On a laptop, only while plugged in. Undo puts back whatever plan you had,
   including a maker's own plan. *Can be undone.*
3. **Close background programs.** Leftovers that do not need to run. Never anything with a window open, never
   system programs. The AI only narrows a list the safety rules already allowed; if the AI cannot answer, nothing is
   closed. *Programs can simply be started again.*
4. **Startup programs.** Programs the AI judged "not needed at startup" are turned off, each with one-click "Turn
   back on". *Can be undone.*
5. **Drive optimisation.** A re-trim on SSDs, a tidy-up pass on hard disks, nothing on drives of unknown type. Waits
   for mains power on a laptop and runs gently while you work. *Nothing to undo.*
6. **Junk files.** Only files Windows makes again on its own. Browser caches, crash dumps and the Windows Update
   download cache are cleared only if you agree. *Permanent, and only regenerable files.*
7. **Recycle Bin.** Shows what it holds and asks "Delete permanently?" first. *Permanent, and it asks.*

**Under the list:** At the end it measures again and files the "after" reading. Prefer to do one thing at a time? The
**Advanced** panel lists every fix with Check, Run and Undo, and labels the ones that cannot be undone.

**Screenshot 3 caption (true to what the image shows):** "7 of 7 steps done. Note the Background apps tile: the AI
could not be reached on this run, so the app closed nothing and said so. That is the rule: when in doubt, leave it
alone."

### 4. "Startup programs" (4/8 split: text left, screenshot 4 right)

**H2:** It finds more of what starts with Windows than Task Manager does, and tells you what each one is.

**Body:**
- Looks in the registry Run entries, both Startup folders, scheduled tasks that start at logon, and auto-start
  services. Task Manager shows only part of that.
- Each program gets who made it and a one-line reason from the AI: "Dropbox startup entry isn't essential for most
  users; you can start Dropbox manually if needed."
- For each one: **Keep**, **Start later** (where Windows supports a delayed start) or **Turn off**, then "Apply
  changes". Anything turned off can be turned back on from the same list.
- Programs Windows needs to run are never shown as switches, so you cannot break Windows by accident here.
- A change counts as done only after Windows confirms it. Turning off never deletes the entry.

### 5. "Proof" (full-width table, paper background)

**H2:** A before and after that cannot be rewritten.

**Intro:** The first time the app measures your PC, it saves that reading and never overwrites it. After every
clean-up it compares. These are the real figures from the PC in the screenshots:

| | Before (4 Sep 2026, 5:42 PM) | After (28 Sep 2026, 8:00 PM) |
|---|---|---|
| Free space on C: | 50 GB | 53 GB |
| Junk files | 273 MB | 5 MB |
| Recycle Bin | 93 MB | Emptied |
| Programs starting with Windows | 26 | 24 |
| Startup time | 24.6 s | 22.2 s |

Footnote row, as the app writes it: "24 startup softwares are still allowed to start, nothing is turned off unless
it is safe to."

**Below the table:** It also keeps a running total in plain words, "What this software has done for your PC": space
recovered, programs that no longer start with Windows, seconds off startup, Speed Score points gained, total junk
cleared. Every number comes from your own PC's history.

### 6. "Safety" (night band or paper; two-column numbered list)

**H2:** The rules it follows on every change

1. **Undo survives a restart.** How to put a setting back is saved to disk before anything changes. If it cannot
   save that, it changes nothing.
2. **Done means confirmed.** A change is reported as done only after Windows reads back the new state.
3. **Windows' own parts are off limits.** Startup items and processes Windows needs are never offered for switching
   off or closing.
4. **Clean-up touches only temp and cache folders.** Everything else is refused. Your documents, photos and roaming
   profile are never in reach.
5. **The AI narrows, it never widens.** The AI can only choose from what the safety rules already allowed. If it
   fails or times out, the answer is "do nothing".
6. **Unknown is never "safe".** A program or setting it cannot identify is left alone, and it tells you so.
7. **It asks before anything permanent.** Recycle Bin, browser caches, the Windows Update cache, crash dumps.
8. **No invented problems.** Every warning is a measurement from your PC. It will not scare you into paying.

**Short line under the list:** Windows asks for administrator permission when the app starts, because clearing
system junk and changing startup services need it. The app runs no antivirus scanner of its own: the security check
is Microsoft Defender's own answer, and you can run Defender's quick scan from the card. If another antivirus
protects your PC, the app names it and points you to it.

### 7. "Backup, restore, reinstall" (asymmetric three-step timeline, text only; no screenshot exists)

**H2:** Resetting Windows? Your files, your programs and your Wi-Fi come back.

**Intro:** Reinstalling or resetting Windows wipes the drive. Back up first, in one click, to a drive you choose.

**Step 1: Back up**
- Copies Documents, Downloads, Desktop, Pictures, Music and Videos to the drive you pick. Before starting it shows
  how many GB, which drives are big enough, and warns you if the drive is the same physical disk as Windows ("if
  that disk fails, this backup could be lost too").
- **Every file is checked.** Each copy is re-read and its SHA-256 fingerprint compared with the original before it
  counts as saved.
- **Pause and resume.** Pause any time and continue later, even after a restart; the app tells you how many files
  are already saved safely.
- **Nothing dropped silently.** Files that could not be copied are listed with the reason (locked, cloud-only
  OneDrive file, path too long and so on).
- Optional: save program installers so you can reinstall without downloading, with the real size shown first; save
  your Wi-Fi networks and passwords; and let the AI point out files that look important (it sees only the top-level
  file and folder names, never paths or contents, and only if you tick the box).
- Originals are never changed or deleted. Backups are never uploaded.

**Step 2: Reset Windows** (one line: "Reinstall or reset Windows as usual, then install AI Laptop Solver from
Microsoft Store.")

**Step 3: Restore**
- "Have a backup from before? Bring your files and softwares back in one click." It finds the backup on any
  connected drive, shows a summary, and for every file that already exists asks: keep the one here, replace with the
  backup, or skip.
- Wi-Fi networks reconnect first.
- **Your programs come back from safe sources only:** the installer saved in your backup, or the official package by
  its exact ID. Each one is marked "Official installer ready", "From the official source" or "No trusted installer".
  A program with no safe source is left for you, and it says so. It never installs from a random download link.
- It recognises your PC after the reset by a one-way fingerprint of its hardware, so it knows which programs were
  yours.

### 8. "Free and Pro" (table)

**H2:** Free to start. Pro for the one-tap work.

**Lead:** Download free from Microsoft Store. Try Pro free inside the app, no card needed. *(Trial length and credits:
see B6 before publishing.)*

| | Free | Pro |
|---|---|---|
| Scan, Speed Score, all nine readings, problems list, system specs | Yes | Yes |
| Security check and Defender quick scan | Yes | Yes |
| Clear junk files, Empty Recycle Bin, responsiveness tweaks | Yes | Yes |
| Startup programs: turn on and off yourself | Yes | Yes |
| Test disk speed; emailed PDF report | Yes | Yes |
| Before and after, lifetime totals, undo | Yes | Yes |
| AI startup review | One free try | Yes, uses AI credits |
| Speed up my computer (the full 7-step run) | | Yes |
| Weekly automatic junk clean-up | | Yes |
| Close background programs, High Performance plan, drive optimisation | | Yes |
| Backup, restore and reinstall programs | | Yes |
| AI checks for background programs and important files | | Yes, uses AI credits |

**Under the table (plans):**
- **Pro Annual**: one year. Does not renew by itself.
- **Pro Lifetime**: one payment, no renewal.
- One account on up to 3 PCs. Paid inside the app through PayPal; we never see your card or PayPal login. No serial
  keys: your licence is managed automatically.
- **AI credits** pay for AI features by actual use. Your balance and the cost are shown before you use one. Pro
  members get 20% off credit packs.
- Prices are shown in the app before you pay. *(Whether to print the dollar figures here: see B6.)*

**Free emailed report, highlighted as a one-line band above or below the table:** "Not ready to install anything
heavy? Type your email in the app and get a full PDF report of your PC: plain English next to the technical detail.
Free, no sign-up."

### 9. "Your data" (narrow column, paper)

**H2:** Most of the work never leaves your PC.

Scanning, cleaning, backups and every change the app makes happen on your PC. Your backups go to your own drive and
are never uploaded. The app sends scan results and crash reports to our server, and sends text to an AI service only
when you use an AI feature. Your PC is identified by a one-way fingerprint of its hardware; the raw serial numbers
never leave it. Every request is signed and sent over HTTPS. No advertising, no third-party analytics, and we do not
sell data. Sign-in, when you want it, is a 6-digit code to your email: no password to leak.
→ Read exactly what is sent, why and for how long in the Privacy Policy (`privacy/`).

### 10. FAQ (`<details>` list)

**Is it free?**
Yes, to download and to scan, see every reading, clean junk, empty the Recycle Bin, manage startup programs and get
the emailed report. The one-tap Speed up, weekly clean-up, backup and restore, and the AI checks are part of Pro,
which you can try free in the app.

**Why does it ask for administrator permission?**
Clearing system junk, changing startup services and optimising drives need it. Windows asks once when the app starts.

**Will it delete my files?**
No. Clean-up can only touch temporary and cache folders, and only files Windows recreates on its own. Anything
permanent (Recycle Bin, browser caches, crash dumps, the Windows Update cache) needs your yes first.

**Can I undo what it did?**
Most changes, yes, even after closing the app or restarting: settings, the power plan and startup programs. Deleted
junk and an emptied Recycle Bin cannot come back, and the app tells you that before it does them.

**Does it replace my antivirus?**
No. It reads Microsoft Defender's own status and can start Defender's quick scan for you. It does not run a scanner of
its own.

**What does the AI do, and what does it see?**
It reviews your startup programs and background programs and says which are safe to turn off or close, with a reason,
and it can point out important-looking files in a backup. It only ever picks from what the safety rules already
allow. It sees program names, publishers and descriptions, or top-level file and folder names for the backup check:
never file contents. We keep only usage counts, not the text.

**What are AI credits?**
AI features cost us per use, so they are paid for by credits. The app shows your balance and the cost before you use
one. Pro includes a discount on credit packs.

**Will it make my PC faster?**
That depends on what is slowing it down. It measures first, fixes what is safe, then measures again and shows you the
before and after, so you see the real difference on your own PC rather than a promise.

**Which Windows versions does it support?**
Windows 10 version 2004 or later (64-bit) and Windows 11. No separate .NET install needed.

**How many PCs can I use it on?**
One account works on up to 3 PCs. You can sign a PC out from the app at any time.

**Does Pro renew automatically?**
No. Pro Annual lasts one year and does not renew by itself. Pro Lifetime is a single payment.

**How is it updated?**
Through Microsoft Store, like any Store app.

### 11. Closing call to action (night band, left-aligned, short)

**H2:** Find out what is slowing your PC down.
**Line:** It takes a minute to scan, and the scan is free.
**Button:** Get it from Microsoft Store

### 12. Footer
"© 2026 Protim Soft. AI Laptop Solver." · Privacy Policy · Terms of Use · `SUPPORT_EMAIL` (as `mailto:SUPPORT_EMAIL`).

## B4. Approved claims (the only features the page may describe)

Each is true in the shipped app. Benefit first, proof second.

| # | Feature | Benefit to say | Proof detail you may add |
|---|---|---|---|
| 1 | Speed Score | One honest number out of 100 with a trend | Average of 5 real readings; says "4 of 5" if one is missing |
| 2 | Main problems | What is wrong, in sentences, worst first | Says "No problems found" when healthy |
| 3 | Nine measured readings | See exactly what is slow | Boot time from Windows' own log; CPU labelled a snapshot; "No reading" instead of guesses |
| 4 | System specs | Know your PC, including Windows install age | RAM type and speed, NVMe/SSD/HDD, activation status |
| 5 | Test disk speed | Real MB/s, not cached numbers | Uncached read and write test, about one minute |
| 6 | Unused programs | See what you never open | 30+ days, with duration; skipped if no evidence. **Report only** |
| 7 | Folder clutter | Spot crowded Downloads, Documents, Desktop | Read-only, deletes nothing |
| 8 | Speed up my computer (Pro) | Whole routine in one tap | 7 steps, progress, Stop, per-step result, skipped steps explained |
| 9 | Startup programs | Faster startup without breaking anything | More sources than Task Manager; protected items hidden; read-back confirmation; turn back on |
| 10 | AI startup review | Know which programs you do not need at startup, and why | One free try per PC (verify, B6); auto turn-off during Speed up with undo |
| 11 | Close background programs (Pro, AI) | Frees memory without touching open windows | AI only narrows; failure means nothing closed |
| 12 | Power plan (Pro) | Full performance when plugged in | Undo restores your exact previous plan |
| 13 | Drive optimisation (Pro) | Drives kept in shape | Trim on SSD, defrag on HDD, waits for power |
| 14 | Clear junk files | Space back, nothing you own lost | Only regenerable files; consent for costly ones |
| 15 | Empty Recycle Bin | Space back, with a warning first | Always asks; confirmed by reading back |
| 16 | Security check + quick scan | "Do I have a virus?" answered by Windows itself | Defender status; names a third-party antivirus |
| 17 | Advanced panel | One fix at a time, with Check / Run / Undo | Labels what cannot be undone |
| 18 | Before and after | Proof of what changed | Baseline written once, never overwritten |
| 19 | Lifetime totals | See everything it has done | From your PC's own history |
| 20 | Undo that survives restart | Safe to try | No change without a saved way back |
| 21 | Backup (Pro) | Files safe before a reset | SHA-256 check per file, pause/resume, honest skip list, same-disk warning |
| 22 | Restore (Pro) | Files back in one click | Finds backup on any drive; keep / replace / skip per file |
| 23 | Reinstall programs (Pro) | Programs back after a reset | Saved installer or official package by exact ID; never random links |
| 24 | Wi-Fi restore (Pro) | Networks reconnect after a reset | Saved only to your own backup drive |
| 25 | Emailed PDF report | Full report free, no sign-up | Plain English next to technical detail |
| 26 | Weekly clean-up (Pro) | Junk cleared every week without thinking | Runs in the background |
| 27 | Email-code sign-in | No password | 6-digit code, up to 3 PCs, sign out any PC |
| 28 | Privacy | Most work stays on the PC | Hardware fingerprint is one-way; signed HTTPS requests; no ads or analytics |
| 29 | Install and updates | Easy and trusted | Microsoft Store; no .NET install needed |

## B5. Claims you must NOT make

These are not built, not reachable, or would mislead. Do not hint at them in copy, captions, alt text or FAQ.

- **Uninstalling unused programs.** The list is report only. (Screenshot 1 contains "I can uninstall them if you
  want"; do not repeat or caption it.)
- **A chat assistant / "Ask the AI anything" / AI technician.** The "Ask the AI" button in the screenshots is not a
  free-form assistant. Do not mention chat at all.
- **Remote support, screen sharing, a technician fixing your PC.** Not available to users.
- **Paid repair quotes or invoices.** Not for the public page.
- **Cloud backup, Google Drive, OneDrive backup.** Backups go only to a drive you choose.
- **Antivirus, malware removal, "virus cleaner".** It reads Defender and can start Defender's scan; nothing more.
- **Drive health, SMART, "predicts disk failure".** Deliberately not read.
- **Windows Update control or driver updates.** Not built.
- **Registry cleaning / "registry errors".** It changes three named settings only.
- **Any speed percentage, "X times faster", "guaranteed".** The terms say no particular gain is promised.
- **A "curated" installer library** for reinstalling programs. Say "official source" instead.
- **Notifications / popups** as a feature. Not a reason to install, and reads like adware.
- **User counts, ratings, reviews, testimonials, "as seen in", logos.** None exist.
- **"Works offline"** or anything about having no internet. Do not raise it.
- **"No admin rights needed".** It needs them; say so plainly (section 6).
- **Bangla or any language other than English.** The page is English only.

## B6. Commercial facts: confirm before publishing

These values are live admin settings on the backend and can change without an app release. The defaults are below.
**Ask Partho before publishing any figure in this list.** Until he confirms, use the wording in the right-hand
column.

| Fact | Default in the backend | Use until confirmed |
|---|---|---|
| Trial length and credits | 30 days, 200 AI credits, no card | "Try Pro free inside the app, no card needed." |
| Pro Annual price | $19 | "Prices are shown in the app before you pay." |
| Pro Lifetime price | $79 | same |
| Credit packs | 500 for $5, 1,200 for $10, 3,000 for $20; Pro 20% off | "Pro members get 20% off credit packs." |
| PCs per account | 3 | "up to 3 PCs" (shown in app) |
| AI startup review free uses | 1 per PC | "One free try" |
| Free/Pro split per feature | as in the B3 §8 table | as in the table; Partho checks `/admin/features` on production matches it |

## B7. Section-to-asset map (quick reference)

| Section | Background | Layout | Asset |
|---|---|---|---|
| 1 Hero + proof strip | paper | 5/7 | screenshot 1 |
| 2 What it measures | night | wide image + table | screenshot 2 |
| 3 Fix it in one tap | paper | 7/5 | screenshot 3 |
| 4 Startup programs | paper | 4/8 | screenshot 4 |
| 5 Proof | paper (white table) | full width table | none |
| 6 Safety rules | night | 2-column numbered list | none |
| 7 Backup / restore / reinstall | paper | 3-step timeline, asymmetric | none |
| 8 Free and Pro | paper (white table) | table + notes | none |
| 9 Your data | paper | narrow column | none |
| 10 FAQ | paper | narrow column | none |
| 11 Closing CTA | night | left-aligned | none |

## B8. Open items for Partho (do not block the build on these; build with the defaults above)

1. **Screenshot 1** shows "I can uninstall them if you want" and the "Ask the AI" button, which promise things the
   app does not do. Re-capture after the app wording is fixed (FEATURES.md section 17, items 1 and 2).
2. **Screenshot 4** has a large green callout and a red arrow drawn on it, which clashes with the restrained page.
   Provide a clean capture if possible.
3. **Prices and trial figures** on the page: yes or no (B6).
4. **Free/Pro split on production**: open `/admin/features` once and confirm it matches the B3 §8 table.
5. **Support email** for the `SUPPORT_EMAIL` placeholder (the deploy agent asks for it).
