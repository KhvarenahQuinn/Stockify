#!/bin/sh

# Hentikan eksekusi jika ada perintah yang gagal
set -e

echo "==> Running database migrations..."
php artisan migrate --force

echo "==> Optimizing configuration and routes caching..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting Nginx & PHP-FPM via Supervisord..."
exec /usr/bin/supervisord -c /var/www/html/docker/supervisord.conf