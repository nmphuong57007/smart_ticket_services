# Sử dụng PHP 8.2 với Apache
FROM php:8.2-apache

# Cài đặt các dependencies của hệ thống
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    jpegoptim optipng pngquant gifsicle \
    vim \
    nano \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Cài đặt PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Thiết lập working directory
WORKDIR /var/www/html

# Copy toàn bộ dự án vào container
COPY . .

# Copy file cấu hình Apache
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Thiết lập quyền cho storage và bootstrap/cache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Cài đặt dependencies
RUN composer install --optimize-autoloader --no-dev

# Copy script khởi tạo
COPY docker/scripts/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose port 80 cho Apache
EXPOSE 80

# Khởi tạo ứng dụng
CMD ["/usr/local/bin/start.sh"]