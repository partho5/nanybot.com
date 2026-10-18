# NanyBot blog — remaining tasks

## Context (decided)
- Laravel 12 + React starter kit (commit d6145cb). Postgres (`nanybot`, `nanybot_test`). Cloudinary for images.
- Public pages (landing + blog) are **Blade with zero JS**. Inertia/React is used **only for the admin**.
- Speed: a static page cache in `public/page-cache` (served by nginx), CSS inlined in `<head>`, system fonts, HTML processed once at save.
- Every user is an author. Roles: `admin`, `author`.
- The webhook accepts HTML in the same format as the TipTap editor and authenticates with a Bearer API key (stored as SHA-256).
- Mobile gutter is 6px, set only on `.wrap`. Media can bleed to the edges using `--gutter`.
- Reference for copying: `/home/haku/projects/Laravel/skill-evidence-laravel` (articles module).
- All writes go through `App\Services\Blog\ArticleWriter` (processing and cache flush).

## Built (untested)
Migrations, enums, models, `CloudinaryService`, `ContentProcessor`, `ArticleWriter`, `PageCache` + `CachePublicPage`, observers, commands (`articles:publish-scheduled`, `blog:cache-clear`, `blog:create-admin`), `config/blog.php`, `Seo`/`Schema`/`InlineAssets`, `BlogController`, `LandingController`, `resources/css/blog.css`, copied editor components.

## Tasks

### Wiring
- [x] `bootstrap/app.php`:
  - add a `public` middleware group (SubstituteBindings + CachePublicPage; no session, CSRF or Inertia);
  - load `routes/blog.php` through `then:`;
  - add the API routes;
  - add aliases `admin` and `api.key`.
- [x] Register observers: `ArticleObserver` on Article, `FlushPageCacheObserver` on Category, Tag and User.
- [x] Turn off `Features::registration()` in `config/fortify.php`.
- [x] Delete `public/robots.txt`. Remove the `/` Inertia route from `web.php`.
- [x] Add `resources/css/blog.css` to the Vite inputs.
- [x] Fix `CreateAdmin`: set username only when creating the user.

### Routes
- [x] `routes/blog.php`:
  - `/` (landing), `robots.txt`, `sitemap.xml`, `llms.txt`;
  - `/blog`, `/blog/page/{n}`, `/blog/feed.xml`, `/blog/search`;
  - `/blog/category/{slug}[/page/{n}]`, `/blog/tag/{slug}[/page/{n}]`, `/blog/author/{key}[/page/{n}]`;
  - `/blog/{slug}.md`, `/blog/{slug}` (keep this one last).
- [x] `web.php`: the `/admin/*` group (auth) and article preview `/admin/articles/{article}/preview` → `BlogController@preview`.
- [x] `routes/api.php`: `POST/PUT/DELETE /api/v1/articles[/{external_id}]`, behind `api.key` and a throttle.

### Public backend
- [x] `SitemapController`: include `image:image` entries. Copy the structure from the reference.
- [x] `FeedController` (RSS 2.0), `RobotsController` (disallow `/admin`, `/api`, search), `LlmsTxtController` (copy from the reference).
- [x] `ArticlePolicy`: copy from the reference, with `author` role = own articles and admin = all.

### Blade views
- [x] `components/public/layout`:
  - head: SEO, canonical, OG/Twitter, robots, JSON-LD, LCP preload, RSS and Markdown alternates, inlined CSS;
  - header, footer, skip link.
- [x] `landing.blade.php` (placeholder plus the latest 3 posts).
- [x] `blog/index` (also used for category, tag and author pages): category chips, cards, path-based pagination, author card on the author page.
- [x] `blog/show`:
  - breadcrumb, h1, excerpt, meta row with `<time>`;
  - featured `<figure>` (eager, `fetchpriority=high`) with caption;
  - TOC using `<details>` when there are 3+ headings;
  - body, tags, share links, author card, related posts, previous/next;
  - preview banner.
- [x] `blog/search` (noindex), `errors/404`, ~~`sitemap.blade.php`, `feed.blade.php`~~ (done).
- [x] Components: article card (eager for the first card, lazy for the rest), author card, pagination.

### Admin (Inertia React)
- [x] Controllers and requests: Articles (copy the reference store/update and route them through `ArticleWriter`), Categories, Tags, Users (admins manage everyone, authors edit their own profile), API keys (create shows the key once, revoke).
- [x] Pages: `admin/articles/{index,create,edit}` (copy from the reference and add meta_title, meta_description, image caption, author select for admins).
- [x] Pages: `admin/categories`, `admin/tags`, `admin/users`, `admin/api-keys`.
- [x] Fix reference Button variants (`utility`, `compact`, `danger`) to use the starter kit's variants.
- [x] Editor image: add a caption field (`data-caption`) and store width/height from the Cloudinary upload response.
- [x] Sidebar nav: Articles, Categories, Tags, Authors, API Keys, View blog.

### Webhook
- [x] `AuthenticateApiKey` middleware: Bearer token → `ApiKey::findActive`, touch `last_used_at`.
- [x] `WebhookArticleRequest`:
  - title, slug?, excerpt, body (HTML), featured_image_url/alt/caption;
  - category (slug or name, created if missing), tags[], author (username or email; defaults to the key's user);
  - status, publish_at (ISO 8601), external_id.
- [x] Controller: upsert on (api_key_id, external_id). Return 201 or 200 with id, url and status. Also PUT and DELETE.
- [x] Queued job: copy a non-Cloudinary featured image into Cloudinary.

### Assets and deploy
- [x] `public/logo.png` (512×512) and `favicon.svg`.
- [x] `deploy/nginx.conf`: `try_files /page-cache$uri /page-cache$uri.html /page-cache/index.html …`, the md MIME type, long cache on `/build`. Also document the cron entry for `schedule:run`.
- [x] Update `.env.example` with `BLOG_PAGE_CACHE` and `BLOG_SAME_AS`.

### Verify
- [x] Factories (Category, Tag, Article, ApiKey; User gains role/username/headline/bio and admin()/author() states) and a seeder (`BlogSeeder`: admin plus 2 authors, 4 categories, 8 tags, 16 sample posts).
- [x] `migrate:fresh --seed`, `npm run build`, `types:check`, `lint`, `pint` (all green).
- [x] Feature tests: webhook (auth, upsert, schedule), visibility and scheduling, SEO and JSON-LD tags, page cache write and flush, ContentProcessor output. These use the `nanybot_test` Postgres database. (161 tests in tests/Feature, 162 in total; PHPUnit uses `nanybot_test`; page-cache tests write to a temp dir via `blog.page_cache_path`.)
- [x] Check page weight: 0 JS on public pages, CSS under 14 KB gzipped (inlined CSS 22.6 KB raw / 5.4 KB gzipped; pages 34-56 KB raw, 8-10 KB gzipped; only res.cloudinary.com resources).
- [ ] `git init` and commit.
