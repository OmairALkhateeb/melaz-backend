#!/usr/bin/env bash
# =============================================================================
# Melaz Motors — deploy script for the Hostinger VPS.
# Run from the project root on the SERVER (not your laptop):
#   cd /var/www/melaz_motors && ./deploy.sh
#
# It pulls the latest code, refreshes dependencies, runs migrations, rebuilds
# caches, and reloads PHP-FPM. Safe to run repeatedly. First-time setup steps
# (key:generate, storage:link, seeding) are in docs/deployment-hostinger.md.
# =============================================================================
set -euo pipefail

PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"   # override if you use 8.2/8.4
BRANCH="${BRANCH:-main}"

echo "==> Enabling maintenance mode"
php artisan down --render="errors::503" || true

cleanup() {
  echo "==> Bringing site back up"
  php artisan up || true
}
trap cleanup EXIT

echo "==> Pulling latest code ($BRANCH)"
git pull --ff-only origin "$BRANCH"

echo "==> Installing PHP dependencies (production)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Rebuilding caches"
php artisan optimize:clear
php artisan optimize

echo "==> Reloading PHP-FPM (so OPcache picks up new code)"
sudo systemctl reload "$PHP_FPM_SERVICE" || echo "   (skipped — run manually if OPcache caches stale code)"

echo "==> Done."
