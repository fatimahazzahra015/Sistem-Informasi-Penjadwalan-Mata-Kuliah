#!/bin/sh
set -e

# Ensure SQLite file permissions are correct if exists
if [ -f /var/www/html/database/database.sqlite ]; then
    chown www-data:www-data /var/www/html/database/database.sqlite || true
    chmod 664 /var/www/html/database/database.sqlite || true
fi

# Run migrations & seeders
echo "Running database migrations and seeders..."
php artisan migrate --force --graceful || true
php artisan db:seed --force || true

# Create storage link if not exists
php artisan storage:link || true

# Cache production config, routes, views
echo "Caching configuration..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
