FROM php:8.3-fpm

ARG NODE_MAJOR=20

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl unzip git \
    libonig-dev libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libicu-dev gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_${NODE_MAJOR}.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    bcmath \
    intl \
    gd \
    exif \
    pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create cache directories
RUN mkdir -p /var/www/.composer /var/www/.npm \
    && chown -R www-data:www-data /var/www

# Set user
USER www-data

EXPOSE 9000

CMD ["php-fpm"]