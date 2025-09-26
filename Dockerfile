# Stage 1: Build frontend assets
FROM node:22 AS frontend
WORKDIR /app

COPY package*.json vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm install && npm run build


# Stage 2: PHP CLI container
FROM php:8.3-cli AS backend

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libzip-dev zip \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo pdo_mysql mbstring gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy built frontend assets from Node stage
RUN mkdir -p public/build
COPY --from=frontend /app/public/build ./public/build

# Copy composer files and install PHP deps
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader

# Copy full app (⚠️ don't copy .env!)
COPY . .

# Git safe directory fix
RUN git config --global --add safe.directory /var/www/html

# Run artisan discover
RUN php artisan package:discover --ansi

# Ensure storage dirs exist and permissions
RUN mkdir -p storage/app/chunks storage/app/uploads storage/app/uploads/variants \
    && chown -R www-data:www-data storage bootstrap/cache

# Expose a port (Railway injects $PORT at runtime)
EXPOSE 8080

# Run migrations + cache + start Laravel server on Railway's $PORT
CMD php artisan migrate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
