# Render-friendly Dockerfile based on the official php:8.2-fpm image
FROM php:8.2-fpm

ENV COMPOSER_ALLOW_SUPERUSER=1 APP_ENV=production

# Install system packages and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip zip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev libpq-dev \
    nginx supervisor procps ca-certificates curl && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY composer.* ./
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction --no-progress || true; fi

# Copy application source
COPY . .

# Permissions
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache /var/log/laravel \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/log/laravel || true \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/log/laravel || true

# Copy configs and entrypoint
COPY nginx.conf /etc/nginx/nginx.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD curl -f http://localhost/health || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
