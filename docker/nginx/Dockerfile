FROM php:8.2-fpm

ARG NODE_MAJOR=22

WORKDIR /var/www/html

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

RUN mkdir -p /var/www/.npm && chown -R www-data:www-data /var/www/.npm

COPY --chown=www-data:www-data . .

RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

CMD ["php-fpm"]