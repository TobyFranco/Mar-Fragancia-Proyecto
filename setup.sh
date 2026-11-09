#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

if [[ ! -f src/.env ]]; then
    cp src/.env.example src/.env
fi

echo "Levantando MySQL y PHP-FPM..."
docker compose up -d --build --remove-orphans db app-fpm

echo "Instalando las dependencias desde los archivos lock..."
docker compose exec -T app-fpm composer install --no-interaction --prefer-dist --optimize-autoloader
docker compose exec -T app-fpm php artisan config:clear

# Los dos nodos comparten esta clave; no cambiarla en cada despliegue.
if ! grep -qE '^APP_KEY=.+$' src/.env; then
    docker compose exec -T app-fpm php artisan key:generate
fi

# El código 10 identifica una base sin tabla de migraciones; otro error aborta.
estado_bd=0
docker compose exec -T app-fpm php -r '
try {
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    exit($app->make("migrator")->repositoryExists() ? 0 : 10);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
}
' || estado_bd=$?
case "$estado_bd" in
    0|10) ;;
    *) echo "No se pudo comprobar la base de datos; se cancela la inicialización." >&2; exit "$estado_bd" ;;
esac

echo "Aplicando migraciones desde app-fpm..."
docker compose exec -T app-fpm php artisan migrate --force
if [[ "$estado_bd" == 10 ]]; then
    echo "Cargando datos de ejemplo en la base nueva..."
    docker compose exec -T app-fpm php artisan db:seed --force
fi

docker compose exec -T app-fpm php artisan storage:link --force
docker compose exec -T app-fpm npm ci --no-audit --no-fund
docker compose exec -T app-fpm npm run build
docker compose exec -T app-fpm sh -c 'mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache'

echo "Levantando nginx, Apache y HAProxy..."
docker compose up -d --remove-orphans
echo "Tienda: http://localhost o http://localhost:8000"
echo "HAProxy Stats: http://localhost:8404 (usuario admin)"
echo "Demo de failover: ./docker/scripts/demo-failover.sh"
