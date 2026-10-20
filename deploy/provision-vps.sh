#!/bin/bash
# One-time provisioning of nanybot.com on the shared VPS (same layout as jovoc.com):
# own system user, own PHP-FPM pool, own PostgreSQL role+db, systemd queue worker,
# cron scheduler, nginx vhost on the existing Let's Encrypt certificate.
#
#   sudo ./provision-vps.sh [path/to/extra.env]
#
# extra.env (optional): KEY=VALUE lines merged into the production .env
# (used for the Cloudinary keys). Nothing in the old /var/www/nanybot.com is
# deleted: it is renamed to /var/www/nanybot.com.static-bak-<timestamp>, and the
# old vhost is kept as <vhost>.bak-<timestamp>. Any failure after the swap rolls back.
set -euo pipefail

SLUG=nanybot
DOMAIN=nanybot.com
ROOT=/var/www/$DOMAIN
NEXT=/var/www/$DOMAIN.next
PHP=8.4
SOCK=/run/php/php$PHP-fpm-$SLUG.sock
REPO=https://github.com/partho5/nanybot.com.git
BRANCH=main
VHOST=/etc/nginx/sites-available/$DOMAIN
STAMP=$(date +%F-%H%M)
CRED=/root/credentials-$SLUG.md
EXTRA="${1:-}"
[ -n "$EXTRA" ] && EXTRA="$(readlink -f "$EXTRA")"
cd /

[ "$(id -u)" -eq 0 ] || { echo "run with sudo" >&2; exit 1; }
as() { sudo -u $SLUG env HOME="$HOME_DIR" "$@"; }
HOME_DIR=$NEXT

echo "==> 1/9 system user"
id $SLUG >/dev/null 2>&1 || adduser --system --group --shell /bin/bash --home $ROOT --no-create-home $SLUG

echo "==> 2/9 database"
if [ ! -f "$CRED" ]; then
  DBPW="$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32 || true)"
  su - postgres -c "psql -v ON_ERROR_STOP=1" <<SQL
CREATE ROLE $SLUG WITH LOGIN PASSWORD '$DBPW';
CREATE DATABASE $SLUG OWNER $SLUG ENCODING 'UTF8';
GRANT ALL PRIVILEGES ON DATABASE $SLUG TO $SLUG;
SQL
  umask 077
  printf '# %s\nProvisioned %s\n\nDB_DATABASE=%s\nDB_USERNAME=%s\nDB_PASSWORD=%s\n' "$DOMAIN" "$(date -u +'%F %T UTC')" $SLUG $SLUG "$DBPW" > "$CRED"
  umask 022
fi
DBPW="$(grep '^DB_PASSWORD=' "$CRED" | cut -d= -f2-)"

echo "==> 3/9 PHP-FPM pool"
cat > /etc/php/$PHP/fpm/pool.d/$SLUG.conf <<EOP
[$SLUG]
user = $SLUG
group = $SLUG
listen = $SOCK
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 5
pm.start_servers = 1
pm.min_spare_servers = 1
pm.max_spare_servers = 2
pm.max_requests = 500

php_admin_value[error_log] = /var/log/php$PHP-fpm-$SLUG.log
php_admin_flag[log_errors] = on
EOP
php-fpm$PHP -t
systemctl reload php$PHP-fpm

echo "==> 4/9 code ($REPO@$BRANCH -> $NEXT)"
[ -e $NEXT ] && { echo "$NEXT already exists; remove it (it is only a staging dir) and re-run" >&2; exit 1; }
mkdir $NEXT && chown $SLUG:$SLUG $NEXT
as git clone -q -b $BRANCH $REPO $NEXT

echo "==> 5/9 .env"
cd $NEXT
as cp .env.example .env
setenv() { # KEY VALUE: replace the line, or append it
  local k=$1 v=$2
  if grep -q "^$k=" .env; then
    sed -i "s|^$k=.*|$k=$(printf '%s' "$v" | sed 's/[&|\\]/\\&/g')|" .env
  else
    printf '%s=%s\n' "$k" "$v" >> .env
  fi
}
setenv APP_ENV production
setenv APP_DEBUG false
setenv APP_URL https://$DOMAIN
setenv LOG_STACK daily
setenv LOG_LEVEL error
setenv DB_USERNAME $SLUG
setenv DB_DATABASE $SLUG
setenv DB_PASSWORD "$DBPW"
setenv SESSION_DRIVER redis
setenv SESSION_SECURE_COOKIE true
setenv QUEUE_CONNECTION redis
setenv CACHE_STORE redis
setenv REDIS_PREFIX ${SLUG}_
setenv CACHE_PREFIX ${SLUG}_
setenv MAIL_FROM_ADDRESS no-reply@$DOMAIN
setenv BLOG_PAGE_CACHE true
if [ -n "$EXTRA" ]; then
  while IFS='=' read -r k v; do [ -n "$k" ] && setenv "$k" "$v"; done < "$EXTRA"
fi
chown $SLUG:$SLUG .env; chmod 600 .env

echo "==> 6/9 composer, key, migrate, assets, admin"
as composer install --no-dev --optimize-autoloader --no-interaction
as php artisan key:generate --force
as php artisan migrate --force
as npm ci --no-audit --no-fund
as npm run build
mkdir -p public/page-cache storage/framework/{cache,sessions,views} storage/logs
chown -R $SLUG:$SLUG $NEXT
chmod -R 775 storage bootstrap/cache
if ! grep -q '^ADMIN_EMAIL=' "$CRED"; then
  out="$(as php artisan blog:create-admin partho8181bd@gmail.com --name='NanyBot Admin')"
  echo "$out"
  printf '\nADMIN_EMAIL=partho8181bd@gmail.com\nADMIN_PASSWORD=%s\n' "$(echo "$out" | sed -n 's/.*Generated password: //p')" >> "$CRED"
fi

echo "==> 7/9 nginx files"
install -m 644 deploy/security-headers.conf /etc/nginx/snippets/nanybot-security-headers.conf
cp -a $VHOST $VHOST.bak-$STAMP
install -m 644 deploy/nginx.conf $VHOST
ln -sfn $VHOST /etc/nginx/sites-enabled/$DOMAIN
rollback() {
  echo "!! failure, rolling back" >&2
  cp -a $VHOST.bak-$STAMP $VHOST
  if [ -d "$ROOT.static-bak-$STAMP" ]; then
    [ -d $ROOT ] && mv $ROOT $NEXT
    mv "$ROOT.static-bak-$STAMP" $ROOT
  fi
  nginx -t && systemctl reload nginx
  exit 1
}
nginx -t || { cp -a $VHOST.bak-$STAMP $VHOST; nginx -t; exit 1; }

echo "==> 8/9 swap in the new app (old dir kept as $ROOT.static-bak-$STAMP)"
trap rollback ERR
mv $ROOT $ROOT.static-bak-$STAMP
mv $NEXT $ROOT
HOME_DIR=$ROOT
cd $ROOT
chmod 755 $ROOT
as php artisan config:cache
as php artisan route:cache
as php artisan view:cache
as php artisan event:cache
nginx -t
systemctl reload nginx
sleep 2
code=$(curl -s -o /dev/null -w '%{http_code}' --resolve $DOMAIN:443:127.0.0.1 https://$DOMAIN/blog)
[ "$code" = 200 ] || { echo "smoke test /blog returned $code" >&2; false; }
trap - ERR

echo "==> 9/9 queue worker, scheduler, deploy script"
cat > /etc/systemd/system/$SLUG-queue.service <<EOP
[Unit]
Description=$DOMAIN queue worker
After=network.target redis-server.service postgresql.service

[Service]
User=$SLUG
Group=$SLUG
Restart=always
RestartSec=5
WorkingDirectory=$ROOT
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
EOP
systemctl daemon-reload
systemctl enable --now $SLUG-queue
echo "* * * * * cd $ROOT && /usr/bin/php artisan schedule:run >> /dev/null 2>&1" | crontab -u $SLUG -
install -m 755 deploy/deploy.sh /usr/local/bin/deploy-$SLUG.sh
chmod 600 "$CRED"
echo "Done. Credentials: $CRED"
