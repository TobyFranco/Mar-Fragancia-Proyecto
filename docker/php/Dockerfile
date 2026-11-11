FROM node:22-bookworm-slim AS assets
WORKDIR /build
COPY src/package*.json ./
RUN npm ci --no-audit --no-fund
COPY src/ ./
RUN npm run build

FROM php:8.4-fpm
RUN apt-get update && apt-get install -y \
    git curl zip unzip libjpeg62-turbo-dev libfreetype6-dev libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/procesos.conf /usr/local/etc/php-fpm.d/zz-procesos.conf
WORKDIR /var/www
COPY src/ ./
COPY --from=assets /build/public/build ./public/build
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache
COPY docker/php/entrypoint.sh /usr/local/bin/mar-entrypoint
ENTRYPOINT ["mar-entrypoint"]
CMD ["php-fpm"]
