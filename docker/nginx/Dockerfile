FROM php:8.2-fpm

ARG NODE_MAJOR=22

# 1. Set Working Directory
WORKDIR /var/www/html

# 2. Install Dependencies & Node.js
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl unzip libpq-dev libonig-dev libzip-dev libpng-dev libjpeg-dev \
    libfreetype6-dev libwebp-dev gnupg libicu-dev \
    && curl -fsSL https://deb.nodesource.com/setup_${NODE_MAJOR}.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo_mysql mbstring zip bcmath gd exif pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. FIX PERMISSION CACHE (Penting untuk Composer & NPM)
# Membuat folder cache agar user www-data tidak 'denied' saat menjalankan composer/npm
RUN mkdir -p /var/www/.composer /var/www/.npm && \
    chown -R www-data:www-data /var/www /var/www/.composer /var/www/.npm

# 4. Copy project dengan kepemilikan www-data
COPY --chown=www-data:www-data . .

# 5. Setup Permissions (Digabung agar layer lebih ringan)
# 775: Owner & Group (www-data) bisa Read, Write, Execute
RUN chmod -R 775 storage bootstrap/cache database public

# 6. Setup SQLite (Memastikan file ada dan bisa ditulis)
RUN touch database/database.sqlite && \
    chown www-data:www-data database/database.sqlite && \
    chmod 664 database/database.sqlite

# 7. Jalankan sebagai www-data
USER www-data

EXPOSE 9000

CMD ["php-fpm"]