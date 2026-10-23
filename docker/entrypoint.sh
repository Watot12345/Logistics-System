#!/bin/bash
set -e

# Default to 10000 if PORT is not set (Render injects $PORT)
PORT="${PORT:-10000}"

echo "Configuring Apache to listen on port ${PORT}..."

# Update ports.conf and virtual host config to match the runtime port
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Run database auto-initialization if requested
if [ "$AUTO_INIT_DB" = "true" ] || [ "$AUTO_INIT_DB" = "1" ]; then
    echo "Running database initialization..."
    php /var/www/html/init_db.php || true
fi

# Execute the main Apache process
exec apache2-foreground
