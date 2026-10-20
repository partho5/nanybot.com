# Blog UI upgrade — modern, premium, simple

## Project goal (source of truth)
nanybot.com sells workflow software to small-business owners (Laptop Care, Nany Social).
The blog exists to (1) rank in search, (2) build trust, (3) send readers to the products.
The admin exists so a few authors can write and publish with zero friction.

## Product decisions (CTO / PM)
- **Palette (REVISED by owner, overrides any warm/rust colour below):** the landing page colours are placeholders — do NOT use them.
  Blog + admin are neutral: background white `#ffffff`, soft off-white surfaces `#f7f7f5`, ink `#111111`, muted `#5f6368`,
  hairline rules `#e6e6e3`. Accent red `#b83119` (dark `#e0583d`) for borders, rules, underlines, small markers, focus —
  owner OK'd red accents. No blue. Text links = ink with red underline. Dark mode: bg `#0f1115`, soft `#171a21`,
  text `#e8eaee`, muted `#9aa2b1`, rules `#262a33`. Anything below mentioning paper `#efebe2` or warm dark `#15140f` / `#24221b`
  means the neutral equivalent above.
- **Layout language kept:** Schibsted Grotesk + JetBrains Mono, square corners, 1px rules,
  mono uppercase labels. Softened for long reading (hairline rules, not heavy black boxes everywhere).
- **Same header/footer as the landing page** on blog pages, so readers never feel they left the site
  and products are always one click away.
- **Fonts:** same Google Fonts link the landing uses (shared browser cache, consistent). No self-hosting.
- **Keep the blog fast:** CSS stays inlined (`App\Support\InlineAssets`, 60KB budget). JS only where it earns its place,
  as one small inline vanilla script, deferred, no libraries.
- **Least cognitive load:** visible actions over hidden ones (no hover-only controls, no click-to-reveal search on desktop).
- **Explicitly NOT building:** reading-progress bar (noise), newsletter (no backend for it), comments, animations beyond
  subtle hover, carousel, infinite scroll, a full WYSIWYG "live" split-screen preview (a one-click unsaved preview is enough).

## Global rules for every task
- Read only what you need. Match surrounding code style (Blade + Tailwind v4 in `resources/css/blog.css`;
  React/TS + shadcn in `resources/js`).
- Accessibility: keep skip link, landmarks, `aria-current`, focus-visible (rust outline), contrast ≥ 4.5:1, works without JS.
- Mobile first: 16px side gutter on mobile (media may still bleed edge-to-edge), no horizontal scroll at 360px.
- Dark mode via `prefers-color-scheme`: neutral dark per the revised palette above.
- Do not change URLs, SEO meta, JSON-LD, sitemap, feed, page-cache behaviour, or DB schema unless the task says so.
- When done: `php artisan test` green, `npm run build` succeeds, `npm run types:check` and `npm run lint:check` clean for touched TS,
  `vendor/bin/pint --dirty` for touched PHP. Clear the page cache if the project has a command for it.
- Update/add Feature tests where behaviour changes (new routes, new rendered elements that matter).
- Do NOT commit. Do NOT touch tasks other than your own.

---

## T1 — Public foundation: tokens, type, layout chrome
Files: `resources/css/blog.css`, `resources/views/components/public/layout.blade.php`, `pagination.blade.php`,
`resources/views/blog/search.blade.php`, `resources/views/errors/404.blade.php`.
1. Replace blog theme tokens with the brand palette above (light + warm dark). Keep token names used by views
   (`paper, ink, muted, line, soft, brand, brand-ink`) so other views keep working; map `brand` → rust, `line` → ink at ~15%,
   `soft` → slightly darker paper (`#e7e2d6` light / `#1d1c16` dark).
2. Fonts: add the landing's Google Fonts preconnect + stylesheet link; `--font-sans` Schibsted Grotesk, `--font-mono` JetBrains Mono.
3. Gutter: `--gutter` 16px mobile, 24px ≥768px. Keep `.bleed` working.
4. Header: reuse the landing header pattern (logo-64.png + "nanybot", nav: Software, Blog (aria-current on blog pages), Contact),
   plus a search: visible compact input ≥640px; on mobile a search icon **link** to the search page (no JS).
   Height 56px, hairline bottom rule. Not sticky (keeps reading area clean).
5. Footer: same 3-column structure as landing footer (brand line, Software incl. Blog + RSS, Company legal links), plus copyright row.
   Keep Sitemap + llms.txt links but in the small copyright row.
6. Spacing: page top/bottom padding more generous (`py-12 md:py-20` for page intros). Body line-height stays 1.6–1.75.
7. `.chip` restyle: square, mono, uppercase, 11–12px, hairline border; active = ink background + paper text.
8. Pagination + search page + 404 restyled to match (mono labels, square buttons, clear current page).
9. Selection colour, focus outline (rust, 2px offset) like landing.

## T2 — Blog listing + article cards
Files: `resources/views/blog/index.blade.php`, `components/public/article-card.blade.php` (+ new component for covers).
1. Page intro: mono eyebrow (`BLOG` / category / tag / author), large title `clamp(2.5rem,6vw,4.5rem)` extrabold tight tracking,
   one-line description muted. Bottom hairline rule.
2. Category filter: horizontal row of `.chip` links ("All" first), scrolls horizontally on mobile without visible scrollbar,
   never wraps into multiple lines on mobile.
3. Featured (first item on page 1 only): split layout ≥768px — image 7/12 left, text 5/12 right, vertically centred;
   title `text-3xl md:text-4xl`. On page ≥2 no featured item, all grid.
4. Grid: 1 / 2 / 3 columns; cards separated by generous gap; each card has a hairline top rule above the meta.
5. Card: image `aspect-[16/10]`, square corners, on hover image scale 1.03 (`transition 300ms`, respects `prefers-reduced-motion`)
   and title underline (rust, offset 4px). Whole card clickable via the title link stretched (`after:absolute inset-0`) — keep
   category + author links clickable above it (`relative z-10`).
   Meta in mono uppercase 11px: `CATEGORY · 6 MIN READ`; below title: excerpt (2-line clamp), then `Author · Mon D, YYYY` small.
6. **Generated cover when no featured image**: same aspect box, `soft` background, 1px rule border, mono category label top-left,
   article title set large in extrabold (line-clamp 3) bottom-left, a small rust square accent. Pure HTML/CSS, no images.
   Use it in cards, featured slot and related articles.
7. Empty state: centred, title "No articles yet", one sentence, link back to all articles.

## T3 — Article page + author card + author page
Files: `resources/views/blog/show.blade.php`, `components/public/author-card.blade.php`, `blog.css`,
`resources/css/app.css` (editor block palette), possibly `BlogController` for author article count.
1. Header: breadcrumb in mono uppercase small; title `clamp(2.25rem,5vw,3.5rem)` extrabold, tight leading; excerpt as standfirst
   (`text-xl md:text-2xl`, muted, max ~60ch).
2. Byline row: 40px round avatar (or initial fallback: ink bg, paper text) + author name (link) on line 1,
   mono small `MON D, YYYY · UPDATED … · 6 MIN READ` on line 2. Hairline rule under the header block.
3. Featured image: square corners, wider than text column on desktop (max 960px, centred), caption mono small.
4. TOC (≥3 items): at ≥1200px a **sticky right rail** next to the 720px column ("ON THIS PAGE", hairline left border,
   active item rust left bar, like `.toc` on legal pages). Below 1200px keep the collapsible `<details>` styled as a square box.
   Active-section highlight via tiny IntersectionObserver script; works (no highlight) without JS.
5. Article body: keep reading metrics (18px, 1.75), h2 bigger (1.75rem) with a hairline rule above, links ink with rust underline,
   blockquote: rust left bar, larger italic-free text. Heading anchors: `#` link shown on hover/focus beside h2/h3
   (add via server-side if body HTML already has ids; else tiny script).
6. Code blocks: language label (if present on `code` class) top-right mono, and a **Copy** button (visible, not hover-only)
   that shows "Copied" for 1.5s. Same tiny inline script; hidden when JS unavailable.
7. Callout + section blocks: re-palette all variants to the brand while **keeping variant names/data attributes**
   (no content migration): neutral (soft bg, ink rule), rust (light rust tint, rust rule), ink (ink bg, paper text), outline (1px ink border).
   Map old variants: purple→neutral, amber→rust, teal/green→neutral-success-ish (use a muted green only if needed for meaning; prefer neutral),
   hero-dark/hero-light→ink, accent→soft, bordered→outline. Apply the same palette in the editor CSS (`app.css`) so authors see what readers see.
   Square corners. Dark mode correct.
8. Tags: chips row. Share: row of square icon buttons (X, LinkedIn, Facebook, Email — inline SVG) + **Copy link** button with
   "Copied" feedback; label "SHARE" mono.
9. **Product CTA** after the article body and share row: ink block with paper text — mono eyebrow "NANYBOT",
   headline "Repeated tasks? Automate them.", one line, two links: "See the software →" (`/#software`) and "Tell us what you repeat" (contact).
   Config-driven text in `config/blog.php` so it can be changed without touching views.
10. Order after body: tags → share → author card → CTA → prev/next → related (3, using T2 cards).
11. Prev/next: two square boxes with hairline border, mono label, title; hover border ink.
12. Author card (article variant): no grey filled box. Top hairline rule, mono "WRITTEN BY", 64px avatar, name (link),
    headline, bio clamped to 3 lines, social links as small inline-SVG icon links with aria-labels, and
    "All articles by {name} →" (show count if cheap: `N articles`).
13. Author page (`full` variant): profile header instead of card — 112px avatar, name as the page H1 (avoid duplicate H1 with
    the index intro), headline, full bio, socials, article count; then hairline rule and the article grid. Fallback avatar = initial on ink.

## T4 — Admin branding + article list + empty states
Files: `resources/css/app.css` (shadcn theme vars), `app-logo*.tsx`, `pages/admin/articles/index.tsx`,
`app/Http/Controllers/Admin/ArticleController.php` (counts + thumbnail URL), other admin list pages only for empty states.
1. Theme: shadcn `--primary` = ink (light) / paper (dark), `--ring` and accents rust; font Schibsted Grotesk for UI
   (load same Google Fonts in `app.blade.php`). Radius small (0.375rem) — admin stays a tool, not brutalist.
2. Logo: use `/logo-64.png` + "nanybot" wordmark (replace starter logo icon/text).
3. Article list:
   - Status **tabs with counts** above the list: `All 24 · Published 18 · Scheduled 2 · Drafts 4` (replaces status select;
     counts respect the other active filters except status). Keep search + category (+ author for admins) filters.
   - Rows: 64×40 thumbnail (featured image via Cloudinary transform, or a small soft placeholder with category initial),
     title (link to edit), status dot + label, `Category · Author`, relative "Updated 3h ago" with absolute date in `title` attr.
   - Row actions **always visible** but subdued: Edit, Preview (new tab), View live (if published), Delete (confirm). On mobile icon-only with aria-labels.
4. Empty states (articles, categories, tags, users, api keys): icon, one line, primary action.

## T5 — Admin editor layout (writing canvas + sidebar)
Files: `resources/js/components/article-form.tsx`, `pages/admin/articles/create.tsx`, `edit.tsx`, and smaller extracted components if it helps.
1. Layout ≥1024px: two columns — main canvas (flex-1, max ~760px) + right sidebar (320px, sticky top). <1024px: single column,
   sidebar sections stacked **after** the editor.
2. Main canvas: Title as a large borderless textarea (auto-grow, `text-3xl md:text-4xl font-bold`, placeholder "Article title");
   below it the slug as small inline text `nanybot.com/blog/<slug>` with an "Edit" link that turns it into an input (keep the
   existing warning text when editing an existing slug). Then the rich text editor, min-height tall, no extra label.
3. Sidebar sections (cards with small headings, this order): **Publish** (status, schedule date when scheduled,
   primary submit button, Preview button, View live link, "Last saved …" for edit), **Featured image** (+alt/caption),
   **Organize** (category, tags), **Excerpt** (with counter), **SEO** (collapsed by default; meta title/description with counters).
   Keep every existing field and validation; nothing removed.
4. Sticky bottom save bar on mobile and when the sidebar's submit is scrolled away? Keep simple: on <1024px a sticky bottom bar
   with the primary button + "Unsaved changes" dot. On desktop the sticky sidebar already keeps the button visible.
5. Unsaved-changes indicator (`form.isDirty`) next to the primary button; `beforeunload` + Inertia navigation guard confirm
   when leaving with unsaved changes (not after successful submit).
6. Keyboard: Ctrl/⌘+S submits the form with current status (preventDefault on browser save).

## T6 — Article preview (unsaved preview, preview banner, search/social previews)
Files: `routes/web.php`, `BlogController` (or a new small controller), `resources/views/blog/show.blade.php`,
`article-form.tsx` (Publish + SEO sections from T5), tests.
1. **Unsaved preview**: the Preview button in the editor works for new and existing articles without saving.
   It POSTs the current form data (CSRF) to a new auth-protected route (e.g. `POST admin/articles/preview`) targeting a new tab;
   server builds an unsaved `Article` model in memory (same body → HTML/TOC/read-time pipeline as on save, same sanitisation),
   renders `blog.show` with `preview=true`. Never persists, never cached, `noindex`, no page-cache write. Authorization same as editing.
   Keep the existing GET preview route working (used by the list "Preview" action).
2. **Preview banner** on preview renders: sticky top, ink bg/paper text, mono status pill (`DRAFT` / `SCHEDULED · date` /
   `UNSAVED PREVIEW`), short text "Only you can see this.", and a "← Back to editor" link (edit URL; for a never-saved article, `window.close()` fallback text "Close preview").
3. **Search result preview** in the SEO section: Google-style snippet (title in link blue-ish, URL `nanybot.com › blog › slug`,
   description), live from form fields with fallbacks exactly as the server uses (meta title → title, meta description → excerpt).
   Show soft warnings when title > 60 chars or description > 160 chars / < 70 chars.
4. **Social card preview** below it: featured image (16:9 or placeholder), domain, title, description — live.
5. Feature tests: unsaved preview requires auth, renders the posted title/body, does not create a row, sends noindex.
