# ============================================
# Stage 1: Node — build frontend assets
# ============================================
FROM node:20-alpine AS node_builder

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install

COPY webpack.config.js postcss.config.js tailwind.config.js ./
COPY assets/ ./assets/
COPY templates/ ./templates/

RUN npm run build

# ============================================
# Stage 2: PHP-FPM (production-ready)
# ============================================
FROM php:8.2-fpm-alpine AS php

# Install system dependencies
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        zip \
        intl \
        opcache \
        mbstring \
        gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock symfony.lock ./

# Install PHP dependencies (include dev for fixtures)
RUN composer install --optimize-autoloader --no-scripts --no-interaction

# Copy the rest of the application
COPY . .

# Copy built assets from node stage
COPY --from=node_builder /app/public/build/ ./public/build/

# Run Composer scripts after full copy
RUN composer run-script post-install-cmd --no-interaction || true

# Create required directories
RUN mkdir -p var/cache var/log public/uploads \
    && chown -R www-data:www-data var public/uploads

# PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000

CMD ["php-fpm"]
