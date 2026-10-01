# AI Laptop Solver: landing page plan

Written 1 October 2026. Plan only, no code yet. This file **replaces** `landing-page-features-and-narrative.md`
for tone, story, copy, layout and look. From the old file, keep only Part A's deploy rules (A2, A3, A8) and the
legal-text rule (privacy and terms text stays word for word).

The page shows the product **as it will be**, not only today's v1. The app is software-only (no hardware
repair); say that once, small, as a side note.

Internal file. Never upload.

---

## 1. The mental model

**Who we sell to:** a person who would otherwise **take their laptop to a repair shop**. Their Windows PC got slow,
shows errors or is hard to use. They are not technical. They may not be a native English speaker. They may live in
the USA, South Asia or the Middle East. They do not want to learn anything. They want it fixed, safely, today.

**What they feel:** annoyed, a little scared ("will I break it?"), tired of fake "PC cleaner" apps, and unhappy
about leaving their laptop (and their private files) with a stranger for days.

**The one promise:** *Let AI run one deep scan. It finds the real reason. Then it fixes it in 1 tap, safely.*

**How the page works:** each section raises one question, and the next section answers it. Every section ends
with a small **bridge line** (cyan, mono, with an arrow) that asks the next question out loud. That is the page's
signature device.

```
1 Hero          "PC slow? Errors?"          ->  What does the scan check?
2 Deep scan     "Every problem found"       ->  26 programs start with my PC. Which do I need?
3 AI review     "Needed or not?"            ->  So much to fix. Do I do it all by hand?
4 One tap       "Fix everything in 1 tap"   ->  Will it delete my files?
5 Safe          "Undo any time"             ->  Does it really work?
6 Proof         "Real before and after"     ->  What if I have to reset Windows?
7 Backup        "Reset. Lose nothing."      ->  So why pay a repair shop?
8 vs shop       "Why pay a repair shop?"    ->  How much does it cost?
9 Pricing       "Start for free."           ->  I still have a question.
10 FAQ          short answers               ->  (ready)
11 Final CTA    "Scan my PC free"
   Footer
```

**The story in colour:** the page starts **dark** (a slow, troubled PC; warning colours amber and red) and becomes
**bright and clean** at the Proof section (the "after"). Warning colours appear only in sections 1 and 2. After
that, only green ("fixed") and cyan ("AI, signal"). The page goes dark one last time for the final call to action
and the footer, which mirrors the hero.

---

## 2. Words: rules for this audience

Readers skim. Many read English as a second language. Some had little schooling. Write so a 12-year-old in Dhaka,
Riyadh or Ohio understands every word on the first read.

- **Headings: 3 to 7 words.** Small text under them: **1 line, at most 2.** No paragraphs on the page (except the
  FAQ and legal pages).
- **Common words only** (around CEFR A2 to B1). One idea per line. Active voice. "You" and "your PC".
- **Numbers as digits:** "1 tap", "7 steps", "9 checks", "30 days". Numbers stop the eye.
- **No idioms, slang or cultural jokes.** Never "forever", "hassle", "game changer", "under the hood", "in a
  snap", "no-brainer", "boost", "supercharge", "blazing fast", "magic".
- **US spelling** on the landing page. Legal pages keep their approved text.
- Say **"PC"** in copy. Use **"laptop"** in the title tag, meta and a few headings for search ("slow laptop").
- Words that hit: **Found. Fixed. Done. Free. Safe. Undo. Back. 1 tap. Real.**

| Do not say | Say |
|---|---|
| takes forever to start | takes too long to start |
| boot time | start time |
| optimize / optimization | make faster / tune |
| tweaks | small settings |
| headroom, allowlist, regenerate | (do not use) |
| clutter | extra files |
| hassle | hard to use |
| leverage, seamless, effortless | (do not use) |

---

## 3. Look and feel

**Direction:** premium, quiet sci-fi. Think "calm control screen", not "gaming RGB". Lots of space, few things on
screen, and everything sharp and readable. Every section has its own feel, but they all come from one system, so
nothing changes suddenly.

### 3.1 Colour tokens

| Token | Hex | Use |
|---|---|---|
| `void` | `#05080B` | hero, final call to action, footer |
| `night` | `#0A1016` | sections 2 to 5 |
| `panel` | `#111A23` | screenshot frames, verdict rows |
| `line-dark` | `#1E2A36` | 1px lines on dark |
| `text-hi` | `#F2F5F7` | main text on dark |
| `text-lo` | `#B4C0CC` | small text on dark (about 10:1 on night; not a grey wash) |
| `scan` | `#1ED68A` | primary button background, "fixed", "after" figures on dark |
| `scan-ink` | `#03140C` | text on the green button |
| `signal` | `#4FD8F0` | thin HUD lines, bridge lines, focus ring, AI accents. Use sparingly |
| `warn` | `#FFB020` | "needs improvement" (sections 1 and 2 only) |
| `alert` | `#FF5A5F` | "needs attention" (sections 1 and 2 only) |
| `clean` | `#F5F8F7` | background of sections 6 to 10 |
| `white` | `#FFFFFF` | tables, FAQ rows, pricing columns on light |
| `ink` | `#0A1016` | text on light |
| `ink-2` | `#3A4652` | small text on light (about 9:1) |
| `scan-deep` | `#087A4A` | green text on light ("after" figures, ticks, links) |
| `line-light` | `#D7DEE3` | 1px lines on light |

- No purple, no blue-to-purple gradients, no neon rainbow.
- **The only gradients:** two tall blend bands (about 240px) where the page changes from dark to light (between 5
  and 6) and back from light to dark (between 10 and 11), plus one faint cyan glow (under 10% opacity) behind the
  hero screenshot.

### 3.2 Type (self-hosted, latin subset)

| Role | Font | Weights | Size |
|---|---|---|---|
| H1, H2, big numbers | **Chakra Petch** (squared, light sci-fi, still very readable) | 600, 700 | H1 `clamp(2.5rem, 6vw, 5rem)`; H2 `clamp(2rem, 4.2vw, 3.5rem)` |
| Body, buttons | **IBM Plex Sans** | 400, 600 | 18px desktop, 17px mobile, line-height 1.6 |
| Labels, readouts, bridge lines | **IBM Plex Mono** | 500 | 14 to 15px, never under 14px |

Pricing headline "Start for free" is the biggest text on the page: `clamp(3rem, 9vw, 7.5rem)`.

### 3.3 Shapes and effects

- **Chamfered corners** (one 45° cut corner, through `clip-path`) on buttons, screenshot frames and the Pro column.
  This is the sci-fi signature. No rounded cards, no pills, no glass, no blur.
- **HUD corner brackets** (thin L-shaped lines in `signal`) at the corners of each screenshot frame.
- **Faint grid** (48px, 4% opacity) only in the hero and the final call to action.
- **One effect per section, at most.** If a section has a glow, it has no grid. If it has motion, nothing else
  moves.

### 3.4 Motion (subtle, once, and switched off by `prefers-reduced-motion`)

| Where | What |
|---|---|
| Hero | A thin cyan scan line sweeps once down the screenshot on load (1.2 s), then stops |
| Scan section | The 4 "found" lines appear one after another (150 ms apart) when scrolled into view |
| One tap section | The 7 steps tick green one by one when scrolled into view |
| Proof section | The "after" numbers count up once |
| Everywhere | Smooth scroll for in-page links (`scroll-behavior: smooth`, `scroll-margin-top` for the sticky bar) |

The page must read and work fully without JavaScript. Allow one small inline script (menu, reveal, count-up)
under about 80 lines, no libraries.

### 3.5 Space and layout

- Section padding: **128 to 160px** desktop, **88 to 96px** mobile. Generous, calm.
- Max width 1200px; 12-column grid; 16px side gutter on mobile; no sideways scroll from 320px up.
- **No two neighbouring sections share a layout** (see the table in section 5).
- Test at 320, 375, 768, 1024, 1440 and 1920px.

---

## 4. Navigation and page map

**Sticky top bar** (`void`, 1px `line-dark` bottom line, solid, no blur): app icon + "AI Laptop Solver" on the
left. On the right: **Scan · AI · Safe · Proof · Pricing · FAQ**, then a small green button **"Get it free"**.
Below 768px, a 48×48 menu button opens a full-width panel with 56px-tall rows and the button at the bottom.

| URL | Page | Status |
|---|---|---|
| `/ai-laptop-solver/` | Landing page (one page, smooth scroll) | rewrite from this plan |
| `/ai-laptop-solver/privacy/` | Privacy Policy | re-skin only, text unchanged |
| `/ai-laptop-solver/terms/` | Terms of Use | re-skin only, text unchanged |
| `/ai-laptop-solver/refund/` | Refund Policy | **new**, draft in `refund-policy-draft.md` |

**Linking rules (everything links to everything):**
- Every page has the same top bar and footer.
- Legal pages: links in the top bar point to `../#scan` and so on. At the top of each legal page, a row of 3 tabs
  **Privacy · Terms · Refund** (current one marked) so readers can move between them.
- The landing page links to the refund page from Pricing ("30-day refund") and from the FAQ.
- Legal pages are **light** (`clean` background, `ink` text, 70-character lines) because long text reads better
  on light.

---

## 5. Section by section

Layout map first, then the copy for each section. Copy is final unless marked *(alt)*.

| # | Section | Background | Layout | Asset |
|---|---|---|---|---|
| 1 | Hero | void + grid | 5/7 split: text left, screenshot right, bleeding off the right edge | screenshot 1 |
| 2 | Deep scan | night | 7/5 split, reversed: screenshot left, "found" list right | screenshot 2 |
| 3 | AI review | night | narrow centered column of 3 "verdict rows" | none (rows built in HTML) |
| 4 | One tap | night | centered stack: big button image, 7-step line, wide screenshot | screenshot 3 |
| 5 | Safe | night → blend to clean | 4 huge statements in an uneven 2×2 (first one wider), divided by lines | none |
| 6 | Proof | clean | full-width before/after rows with giant numbers, screenshot crop on the side | screenshot 4 (cropped) |
| 7 | Backup | clean | 3-step horizontal line with big numerals | new screenshots needed |
| 8 | vs repair shop | clean | 2-column comparison rows | none |
| 9 | Pricing | clean | giant headline, then Free / Pro columns | none |
| 10 | FAQ | clean | narrow column, `<details>` rows | none |
| 11 | Final call to action | blend to void + grid | centered, short | none |
| — | Footer | void | 4 wide columns, spacious | icon |

---

### 1. Hero (`#top`)

The pain first, then the answer. Nothing else.

- **H1** (2 lines, line 1 in `text-hi`, line 2 in `scan`):
  **PC slow? Errors? Hard to use?**
  **Let AI run one deep scan.**
- **Under it (20px):** It finds the real reason for every problem. In simple words.
- **Small line:** Then it fixes them in 1 tap. Undo any time.
- **Buttons:** [**Scan my PC free**] (Microsoft Store link) · *See what it finds ↓* (to `#scan`)
- **Micro line (mono, 14px):** Free to start · Windows 10 and 11 · From Microsoft Store
- **Visual:** screenshot 1 in a chamfered frame with HUD brackets, the scan line sweeping once. One small mono
  readout pinned to the frame's bottom-left corner: `SCAN COMPLETE · 6 PROBLEMS FOUND` (amber dot).
- **Mobile:** text first, then a **cropped** screenshot (Speed Score and problem list only; a full 1920px
  dashboard is unreadable on a phone). Use `<picture>` with a mobile crop.
- **Bridge:** What does the scan check? ↓

### 2. Deep scan (`#scan`)

- **H2:** One deep scan. Every problem found.
- **Sub:** It checks 9 parts of your PC. Real numbers from your PC. No guessing.
- **Right column, label (mono):** `FOUND ON A REAL PC`
  - [red] 26 programs slow down your PC every time it starts
  - [red] 198 programs running in the background
  - [amber] 82% of drive C: is full
  - [amber] 1.2 GB of junk files
  *([red] and [amber] are small CSS squares in alert and warn colour, not emoji)*
- **Then a big number:** **63 / 100** with the label **Speed Score**. Small line: One number for your PC's
  health. Watch it go up.
- **The 9 checks** as one wrapping row of mono tags: Junk files · Recycle Bin · Disk space · Virus protection ·
  Startup programs · Background programs · Memory (RAM) · Start time · CPU
- **Hint line:** Also shows your PC's details: RAM, SSD or HDD, Windows age, and real disk speed.
- **Left column:** screenshot 2.
- **Bridge:** 26 programs start with your PC. Which ones do you really need? ↓

### 3. AI review (`#ai`)

The "AI" in the product name earns its place here.

- **H2:** AI checks every program. Needed or not?
- **Sub:** For each program, it tells you who made it, what it does, and if your PC needs it.
- **3 verdict rows** (built in HTML, styled like the app, `panel` background, chamfered):

  | Program | AI verdict | Reason (1 line) | Action |
  |---|---|---|---|
  | Dropbox · Dropbox Inc. | `Not needed at start` (cyan) | You can open Dropbox when you need it. | Keep · **Turn off** |
  | Webcam software · Logitech | `Not needed at start` (cyan) | Only needed if you use the webcam app at start. | Keep · **Turn off** |
  | Windows Security · Microsoft | `Protected` (green, lock) | Windows needs this. The AI never touches it. | (locked) |

- **Hint line:** Finds more startup programs than Task Manager. Also checks programs running in the background.
- **Bridge:** That is a lot to fix. Do you have to do it all by hand? ↓

### 4. One tap (`#fix`)

- **H2:** Fix everything in 1 tap.
- **Big button image** (not clickable, `aria-hidden`), styled like the app's: **⚡ Speed up my computer**
- **7-step line** (horizontal on desktop, vertical on mobile; each step ticks green on scroll):
  1 Faster menus · 2 Full power when plugged in · 3 Close extra programs · 4 Stop extra startup programs ·
  5 Tune your drives · 6 Clean junk files · 7 Empty Recycle Bin
- **Sub:** 7 steps. You see each one. Stop any time.
- **Screenshot 3** wide below, caption: `7/7 STEPS DONE`
- **Hint line:** Like control? Run each fix one by one in Advanced. Small `PRO` tag next to the H2.
- **Bridge:** Will it delete my files? ↓

### 5. Safe (`#safe`)

- **H2:** Safe by design. Undo any time.
- **4 huge statements** (Chakra Petch, about 2.25rem), each with one small line under it:
  1. **Your files: never touched.** It cleans only temp and cache files. Windows makes them again by itself.
  2. **Undo: even after restart.** It saves your old settings first. If it cannot save, it changes nothing.
  3. **Windows parts: locked.** Programs Windows needs are never shown for turning off.
  4. **Delete for good? It asks first.** Recycle Bin, browser cache: only with your yes.
- **One line under all 4:** Not sure about something? Then it does nothing. The AI can only pick from safe choices.
- **Bridge:** Does it really work? See real numbers. ↓
- The bottom of this section blends into `clean` (this is where the page "becomes clean").

### 6. Proof (`#proof`): the page turns bright

- **H2:** Real PC. Real before and after.
- **5 big rows** (label left; before in `ink-2`; arrow; after in `scan-deep`, Chakra Petch, very large, count-up):

  | | Before | After |
  |---|---|---|
  | Free space on C: | 50 GB | **53 GB** |
  | Junk files | 273 MB | **5 MB** |
  | Recycle Bin | 93 MB | **Empty** |
  | Programs at start | 26 | **24** |
  | Start time | 24.6 s | **22.2 s** |

  The arrows need hidden text ("to") for screen readers.
- **Sub:** The first scan is saved and never changed. Every fix is compared with it. You see your own numbers.
- **Small (ink-2):** One real PC, 4 to 28 September 2026. Every PC is different.
- **Totals strip** (mono): 3.2 GB junk cleared · 248 MB emptied from Recycle Bin · 2 programs stopped at start
- **Side:** screenshot 4 **cropped to the Before and After table only** (this also removes the drawn callout and arrow).
- **Bridge:** What if you have to reset Windows? ↓

### 7. Backup (`#backup`)

- **H2:** Reset Windows. Lose nothing.
- **3 steps** (big numerals, a thin line joining them):
  1. **Back up.** Files, programs and Wi-Fi passwords. To your own drive, in 1 click.
  2. **Reset Windows.** A fresh, clean start.
  3. **Get it all back.** 1 click. Files back. Programs back. Wi-Fi back.
- **Hint line:** Every file is checked after copying. Pause and continue later. Nothing goes to the cloud.
- `PRO` tag next to the H2.
- **Visual:** needs new screenshots (see section 8). Until then, text only.
- **Bridge:** So why pay a repair shop? ↓

### 8. vs repair shop (`#compare`)

This is the section for our niche.

- **H2:** Why pay a repair shop?
- **Rows** (left column `ink-2`, right column `ink` with a green tick):

  | Repair shop | AI Laptop Solver |
  |---|---|
  | Leave your laptop for days | Fix it at home, today |
  | A stranger can see your files | Your files stay with you |
  | Pay again every visit | Start free |
  | "It's fixed." But what did they do? | A clear report for every step |
  | Slow again next month | Weekly auto clean (Pro) |

- **Side note (small, ink-2):** Broken screen or bad battery? That needs a shop. AI Laptop Solver fixes software
  problems.
- **Bridge:** How much does it cost? ↓

### 9. Pricing (`#pricing`)

- **Giant headline:** **Start for free.**
- **Sub:** Scan, find every problem, clean junk. Free.
- **Two columns:**

  **Free: find and clean**
  - Deep scan and Speed Score
  - All 9 checks and problems in simple words
  - Clean junk files, empty Recycle Bin
  - Virus protection check
  - Turn startup programs on or off yourself
  - Before and after
  - Full PDF report to your email
  - 1 free AI check of your startup programs

  **Pro: more comfort, all automatic** (chamfered frame, `scan` border, the visual focus)
  - Fix everything in 1 tap
  - AI checks every program
  - Weekly auto clean
  - Closes extra background programs
  - Full power mode and drive tune-up
  - Backup, restore, programs back after reset
  - 1 account on up to 3 PCs

- **Under Pro (mono, small):** Try Pro free in the app. No card needed. · Pay yearly or once for life. No
  auto-renew. · **30-day refund** (link to `refund/`)
- **No prices on the page.** Prices are shown in the app before you pay.
- **Button:** [**Start free**]
- **Bridge:** Still have a question? ↓

### 10. FAQ (`#faq`)

`<details>` rows, 1 to 2 lines per answer.

| Question | Answer |
|---|---|
| Is it really free? | Yes. Scan, all checks, junk clean and the PDF report are free. Pro adds 1-tap fix, AI and backup. |
| Is it safe to install? | Yes. It comes from Microsoft Store, and Microsoft checks it. |
| Will it delete my files? | No. It cleans only temp and cache files. For anything permanent, it asks you first. |
| Can I undo changes? | Yes. Settings and startup programs can be undone, even after a restart. |
| Do I need to know computers? | No. It explains everything in simple words. 1 tap does the work. |
| Does it fix hardware? | No. It fixes software problems. A broken screen or battery needs a repair shop. |
| Is it an antivirus? | No. It checks Windows Security for you and can start a virus scan. |
| What does the AI see? | Program names and makers. Never your files or what is inside them. |
| Why does it ask for admin permission? | Windows asks this so the app can clean system junk and change startup programs. |
| Which Windows? | Windows 10 (version 2004 or newer, 64-bit) and Windows 11. |
| How many PCs? | 1 account works on up to 3 PCs. |
| Can I get my money back? | Yes, within 30 days of your first payment. [Refund Policy](refund/) |

### 11. Final call to action (`#start`)

Mirrors the hero: `void`, grid, the scan line sweeps once more.

- **H2:** Find out why your PC is slow. Today.
- **Sub:** 1 deep scan. Free.
- **Button:** [**Scan my PC free**]
- **Micro line:** Windows 10 and 11 · From Microsoft Store

### Footer

`void`, spacious (about 96px top padding, 48px between groups), 4 columns on desktop, 2 on tablet, 1 on mobile.

| Column 1 | Column 2: Product | Column 3: Legal | Column 4: Help |
|---|---|---|---|
| Icon + **AI Laptop Solver**, one line: "Find and fix what slows your PC." + Store button | Deep scan · AI review · 1-tap fix · Safety · Backup · Pricing · FAQ | Privacy Policy · Terms of Use · Refund Policy | `SUPPORT_EMAIL` (mailto) · Get it on Microsoft Store |

Bottom line (1px rule above): © 2026 Protim Soft. All rights reserved. · Made for Windows 10 and 11.

---

## 6. SEO structure

**Head (landing page):**

```html
<title>Slow PC or Laptop? Find and Fix the Cause | AI Laptop Solver</title>
<meta name="description" content="PC slow or showing errors? Let AI run one deep scan. It finds the real reason, fixes it in 1 tap, and you can undo any time. Free for Windows 10 and 11.">
<link rel="canonical" href="https://nanybot.com/ai-laptop-solver/">
<meta name="robots" content="index, follow">
<!-- Open Graph + Twitter: title, description, url, og:image = a new 1200x630 image (see section 8) -->
<meta name="theme-color" content="#05080B">
```

**Headings:** one H1 (the hero). One H2 per section. H3 only inside sections (FAQ questions, pricing column
titles, backup steps). No skipped levels.

**Search phrases to cover naturally** (no stuffing):

| Main | Supporting |
|---|---|
| slow laptop fix · why is my PC slow · PC running slow Windows 11 | laptop takes too long to start · which startup programs to disable · clean junk files Windows · free up disk space · backup before reset Windows · PC repair software · AI PC scan |

Each phrase belongs to the section that answers it (scan → "why is my PC slow"; AI → "startup programs";
backup → "reset Windows").

**Markup:**
- `<header>`, `<nav>`, `<main>`, `<section id aria-labelledby>`, `<footer>`. Section ids: `scan`, `ai`, `fix`,
  `safe`, `proof`, `backup`, `compare`, `pricing`, `faq`, `start`.
- JSON-LD: `SoftwareApplication` (name, operatingSystem "Windows 10, Windows 11", applicationCategory
  "UtilitiesApplication", offers price 0, url to the Store), `Organization` (Protim Soft), `FAQPage` (the FAQ
  above, same text). No ratings and no review counts.
- Legal pages: their own `<title>`, description and canonical, plus `BreadcrumbList`.
- Images: descriptive `alt`, `width`/`height`, WebP with PNG fallback, `loading="lazy"` except the hero
  (`fetchpriority="high"`).
- Add `robots.txt` and `sitemap.xml` (4 URLs) to `site/`. Tell the deploy guide they are allowed.
- Speed: static HTML, fonts preloaded (2 files), no third-party requests. Target LCP under 2 s.

---

## 7. Technical rules kept from the old brief

Static files only. Tailwind v4 compiled locally into `site/assets/site.css` (never the CDN). Self-hosted fonts
(`@fontsource/chakra-petch`, `@fontsource/ibm-plex-sans`, `@fontsource/ibm-plex-mono`). No third-party
requests. Relative links (absolute only for canonical and Open Graph). Keep the `SUPPORT_EMAIL` placeholder.
Privacy keeps `Last updated 29 September 2026`. Nothing in `site/` except the pages, assets, `robots.txt` and
`sitemap.xml`. Accessibility: `lang="en"`, skip link, AA contrast or better, visible focus (2px `signal` on dark,
2px `ink` on light), 48px tap targets, `<th scope>` in tables.

**Changed from the old brief:** colour (premium dark-to-light instead of muted paper), spacing (generous instead
of compact), subtle motion is allowed (and off with reduced motion), chamfered corners instead of square, copy
style (short and punchy instead of long and technical).

---

## 8. Assets to make before the build

| Asset | For | Note |
|---|---|---|
| Mobile crop of screenshot 1 (Speed Score + problems) | Hero on phones | about 1080×1350 |
| Crop of screenshot 4 (Before and After table only) | Proof | removes the callout and red arrow |
| Clean capture of the AI startup list | optional for section 3 | no drawn callout |
| Backup screen, Restore screen, "Software you can get back" screen | Section 7 | do not exist yet |
| PDF report page | optional for Pricing (Free column) | |
| Open Graph image 1200×630 | link previews | dark, H1 line 2 + dashboard crop |
| Favicon set (32, 180, 192, 512) | all pages | from `icon.png` |
