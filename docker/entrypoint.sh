#!/bin/sh
set -e

echo "==> Starting Logistics System Application..."

# 1. Fallback for APP_KEY if not configured in host environment
if [ -z "$APP_KEY" ]; then
    echo "==> APP_KEY not provided, generating/setting application key..."
    export APP_KEY="base64:7BsmF+t9D4gq0Zk7oA7+2t8rP1K0f/K4n9Jm9+rX2Ys="
fi

# 2. Database setup
export DB_CONNECTION="${DB_CONNECTION:-sqlite}"

if [ "$DB_CONNECTION" = "sqlite" ]; then
    # Force SQLite path to always use container path, ignoring any local host machine paths
    export DB_DATABASE="/var/www/html/database/database.sqlite"
    mkdir -p /var/www/html/database
    touch "$DB_DATABASE"
fi

export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export CACHE_STORE="${CACHE_STORE:-file}"

# 3. Run database migrations & seeders
echo "==> Running database migrations..."
php artisan migrate --force --no-interaction || true

echo "==> Running database seeders..."
php artisan db:seed --force --no-interaction || true

# 4. Cache config and routes
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# 5. Ensure full read/write permissions for web server (www-data)
echo "==> Setting directory permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database /var/www/html/public/uploads
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database /var/www/html/public/uploads
if [ -f "$DB_DATABASE" ]; then
    chmod 666 "$DB_DATABASE"
fi

# 6. Configure Apache PORT (Render passes $PORT, default 8000)
PORT="${PORT:-8000}"
echo "==> Binding Apache to port ${PORT}..."
sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

echo "==> Logistics System ready! Launching Apache..."
exec apache2-foreground
