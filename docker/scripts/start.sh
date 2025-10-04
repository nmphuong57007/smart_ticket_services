#!/bin/bash

echo "Starting Laravel application setup..."

# Tạo file .env nếu chưa tồn tại
if [ ! -f .env ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
fi

# Generate application key
php artisan key:generate --force

# Cache configuration
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (chỉ khi có database)
if [ ! -z "$DB_HOST" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Thiết lập quyền
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start Apache
echo "Starting Apache..."
exec apache2-foreground