# Stage 1: Build frontend assets
FROM node:22 AS frontend
WORKDIR /app

COPY package*.json vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm install && npm run build

# Stage 2: PHP-FPM + Nginx container
FROM php:8.3-fpm AS backend

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libzip-dev zip \
    nginx supervisor \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo pdo_mysql mbstring gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy built frontend assets
RUN mkdir -p public/build
COPY --from=frontend /app/public/build ./public/build

# Copy composer files and install deps
COPY composer.json ./
RUN composer install --no-dev --no-scripts --optimize-autoloader

# Copy full app
COPY . .


# Git safe directory fix
RUN git config --global --add safe.directory /var/www/html

# Run artisan discovery
RUN php artisan package:discover --ansi

# Ensure storage dirs exist
RUN mkdir -p storage/app/chunks storage/app/uploads storage/app/uploads/variants \
    && chown -R www-data:www-data storage bootstrap/cache

# ------------------------
# Nginx & Supervisord setup
# ------------------------

# Nginx config
RUN rm -f /etc/nginx/conf.d/* && \
    rm -f /etc/nginx/sites-enabled/* && \
    cp /etc/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

# Supervisord config
COPY ./supervisord.conf /etc/supervisord.conf

# Expose port 8080 (Railway keeps injecting this)
EXPOSE 8080

# Start supervisord (manages php-fpm + nginx)
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
