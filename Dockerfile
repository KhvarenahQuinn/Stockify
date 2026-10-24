FROM php:8.3-fpm-alpine

# 1. Install system dependencies, PHP extensions, serta Node.js & npm
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    nodejs \
    npm && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo pdo_mysql gd zip intl mbstring bcmath opcache

# 2. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 3. Copy seluruh berkas proyek
COPY . .

# 4. Install dependensi Composer & NPM, lalu jalankan Build Frontend
RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build

# 5. Set hak akses direktori storage & cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 6. Beri hak akses eksekusi pada skrip entrypoint
RUN chmod +x /var/www/html/docker/entrypoint.sh

# Expose port 80
EXPOSE 80

# Jalankan entrypoint script saat container booting
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]