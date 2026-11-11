FROM node:22-bookworm-slim AS assets
WORKDIR /build
COPY src/package*.json ./
RUN npm ci --no-audit --no-fund
COPY src/ ./
RUN npm run build

FROM php:8.4-apache
RUN apt-get update && apt-get install -y \
    git curl zip unzip libjpeg62-turbo-dev libfreetype6-dev libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
WORKDIR /var/www
COPY src/ ./
COPY --from=assets /build/public/build ./public/build
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache
COPY docker/php/entrypoint.sh /usr/local/bin/mar-entrypoint
ENTRYPOINT ["mar-entrypoint"]
CMD ["apache2-foreground"]
