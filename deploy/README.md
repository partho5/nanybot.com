# Deploying nanybot.com

## nginx
`nginx.conf` is the site config; `security-headers.conf` is included by it
(copy it to `/etc/nginx/snippets/nanybot-security-headers.conf`).
`nginx -t && systemctl reload nginx` after changes.

The static page cache is `public/page-cache` (written by
`App\Http\Middleware\CachePublicPage` through `App\Services\Blog\PageCache`).
Disable with `BLOG_PAGE_CACHE=false`; clear with `php artisan blog:cache-clear`.
The directory must be writable by the PHP-FPM user:

    mkdir -p public/page-cache   # git-ignored, so absent on a fresh clone
    chown -R www-data:www-data storage bootstrap/cache public/page-cache

## Scheduler (cron)
Publishes scheduled articles every minute (`articles:publish-scheduled`).

    * * * * * cd /var/www/nanybot.com && php artisan schedule:run >> /dev/null 2>&1

## Queue worker (supervisor)
The webhook's featured-image import (`ImportFeaturedImage`) is queued
(`QUEUE_CONNECTION=database`), so a worker must always be running.
`/etc/supervisor/conf.d/nanybot-worker.conf`:

    [program:nanybot-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /var/www/nanybot.com/artisan queue:work --sleep=3 --tries=3 --max-time=3600
    autostart=true
    autorestart=true
    stopasgroup=true
    killasgroup=true
    user=www-data
    numprocs=1
    redirect_stderr=true
    stdout_logfile=/var/www/nanybot.com/storage/logs/worker.log
    stopwaitsecs=3600

Then `supervisorctl reread && supervisorctl update && supervisorctl start nanybot-worker:*`.
After each deploy run `php artisan queue:restart`.

## Release steps

    composer install --no-dev --optimize-autoloader
    npm ci && npm run build
    php artisan migrate --force
    php artisan config:cache && php artisan route:cache && php artisan view:cache
    php artisan blog:cache-clear
    php artisan queue:restart
