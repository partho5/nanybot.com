#!/bin/bash
# Deploy the latest main to production. Run it directly from the repo:
#   sudo /var/www/nanybot.com/deploy/deploy.sh
set -euo pipefail

SITE=nanybot
ROOT=/var/www/nanybot.com
BRANCH=main

[ "$(id -u)" -eq 0 ] || { echo "run with sudo"; exit 1; }
cd "$ROOT"

echo "==> pulling $BRANCH"
sudo -u $SITE git pull --ff-only origin "$BRANCH"

echo "==> composer"
sudo -u $SITE composer install --no-dev --optimize-autoloader --no-interaction

echo "==> npm build"
# Wayfinder reads artisan's route list; a stale route cache hides new routes and breaks the build.
sudo -u $SITE php artisan route:clear
sudo -u $SITE npm ci --no-audit --no-fund
sudo -u $SITE npm run build

echo "==> migrations"
sudo -u $SITE php artisan migrate --force

echo "==> rebuilding caches"
sudo -u $SITE php artisan config:cache
sudo -u $SITE php artisan route:cache
sudo -u $SITE php artisan view:cache
sudo -u $SITE php artisan event:cache

echo "==> clearing the static page cache"
sudo -u $SITE php artisan blog:cache-clear

echo "==> permissions"
chown -R $SITE:$SITE "$ROOT"
chmod -R 775 "$ROOT/storage" "$ROOT/bootstrap/cache"
chmod 600 "$ROOT/.env"

# OPcache runs with validate_timestamps=0: FPM must be reloaded to see new code.
echo "==> reloading php-fpm (clears OPcache)"
systemctl reload php8.4-fpm

echo "==> restarting queue worker"
systemctl restart ${SITE}-queue

echo "==> done"
