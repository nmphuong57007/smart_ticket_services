# Dockerfile for Laravel application (single-container with nginx + php-fpm + supervisor)
FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive \
    LANG=C.UTF-8 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production

ARG USER=www-data

# Install base packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl gnupg lsb-release apt-transport-https software-properties-common \
    git unzip zip build-essential procps gnupg2 ca-certificates \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev libpq-dev \
    nginx supervisor procps bc curl && rm -rf /var/lib/apt/lists/*

# Install PHP 8.2 from Sury (deb.sury.org)
RUN curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/php-archive-keyring.gpg \
  && echo "deb [signed-by=/usr/share/keyrings/php-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list \
  && apt-get update \
  && apt-get install -y --no-install-recommends php8.2-fpm php8.2-cli php8.2-mbstring php8.2-xml php8.2-curl php8.2-mysql php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl php8.2-opcache \
  && rm -rf /var/lib/apt/lists/*


# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Copy composer files and install dependencies (cache layer)
# Use a wildcard so it matches composer.json (and composer.lock if present)
COPY composer.* ./
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction --no-progress || true; fi

# Copy application source
COPY . .

# Ensure storage and cache directories have correct permissions
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache /var/log/laravel \
    && chown -R ${USER}:${USER} /var/www/html/storage /var/www/html/bootstrap/cache /var/log/laravel || true \
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
