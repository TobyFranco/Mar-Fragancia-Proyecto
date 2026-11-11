#!/bin/sh
set -eu

case "${1:-}" in
    php-fpm|apache2-foreground) ;;
    *) exec docker-php-entrypoint "$@" ;;
esac

if [ -z "${APP_KEY:-}" ]; then
    echo 'APP_KEY es obligatoria; configurala en las variables del Stack.' >&2
    exit 1
fi
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
php artisan config:clear
if [ "${APP_NODE:-}" = nginx-php-fpm ]; then
    rm -f /tmp/mar-ready
    intentos=0
    until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }'; do
        intentos=$((intentos + 1))
        if [ "$intentos" -ge 60 ]; then echo 'No se pudo conectar a MySQL.' >&2; exit 1; fi
        sleep 2
    done
    estado=0
    php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit($app->make("migrator")->repositoryExists() ? 0 : 10);' || estado=$?
    case "$estado" in 0|10) ;; *) echo 'No se pudo comprobar la base de datos.' >&2; exit "$estado" ;; esac
    php artisan migrate --force
    if [ "$estado" = 10 ]; then php artisan db:seed --force; fi
fi
php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
if [ "${APP_NODE:-}" = nginx-php-fpm ]; then touch /tmp/mar-ready; fi
exec docker-php-entrypoint "$@"
