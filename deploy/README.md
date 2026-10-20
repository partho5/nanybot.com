# Deploying nanybot.com

Production runs on the shared Debian VPS, laid out like the other Laravel sites there
(one system user, FPM pool, PostgreSQL role and systemd queue worker per site).

| Piece | Value |
|---|---|
| App root | `/var/www/nanybot.com` (user `nanybot`) |
| PHP-FPM pool | `/run/php/php8.4-fpm-nanybot.sock` |
| Database | PostgreSQL `nanybot` (credentials in `/root/credentials-nanybot.md`) |
| Cache / queue / session | Redis, prefix `nanybot_` |
| Queue worker | systemd `nanybot-queue` |
| Scheduler | cron of user `nanybot` (`schedule:run` every minute; publishes scheduled articles) |
| TLS | Let's Encrypt cert `nanybot.com` (renewed by the shared certbot timer) |

## First-time setup
`provision-vps.sh` does everything above, idempotently where it can. The previous static
site is renamed to `/var/www/nanybot.com.static-bak-<timestamp>` (never deleted) and the old
vhost is kept as `nanybot.com.bak-<timestamp>`; a failed smoke test rolls both back.

    sudo ./deploy/provision-vps.sh /path/to/extra.env   # extra.env = Cloudinary keys

## Releases
    sudo deploy-nanybot.sh        # = deploy/deploy.sh: pull, composer, npm build, migrate,
                                  #   caches, blog:cache-clear, reload FPM, restart queue

## nginx
`nginx.conf` is the vhost, `security-headers.conf` is installed as
`/etc/nginx/snippets/nanybot-security-headers.conf`. `nginx -t && systemctl reload nginx` after edits.

The static page cache is `public/page-cache` (written by `App\Http\Middleware\CachePublicPage`
through `App\Services\Blog\PageCache`); nginx serves it before PHP is touched. Disable with
`BLOG_PAGE_CACHE=false`; clear with `php artisan blog:cache-clear`. The directory must be
writable by the FPM user (`nanybot`).

`/ai-laptop-solver/` is a plain static site in `public/ai-laptop-solver/` served directly by nginx.

## First admin
Registration is disabled. `php artisan blog:create-admin you@example.com --name="Your Name"`
(prints a generated password if `--password` is omitted).
