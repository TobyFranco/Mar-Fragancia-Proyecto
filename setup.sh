#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

if [[ ! -f src/.env ]]; then
    cp src/.env.example src/.env
fi

echo "Levantando MySQL y los dos servicios PHP..."
docker compose up -d --build --remove-orphans db app-a app-b

echo "Instalando las dependencias desde los archivos lock..."
docker compose exec -T app-a composer install --no-interaction --prefer-dist --optimize-autoloader
docker compose exec -T app-a php artisan config:clear

# Los dos nodos comparten esta clave; no cambiarla en cada despliegue.
if ! grep -qE '^APP_KEY=.+$' src/.env; then
    docker compose exec -T app-a php artisan key:generate
fi

bd_nueva=false
if ! docker compose exec -T app-a php artisan migrate:status >/dev/null 2>&1; then
    bd_nueva=true
fi

echo "Aplicando migraciones desde app-a..."
docker compose exec -T app-a php artisan migrate --force
if [[ "$bd_nueva" == true ]]; then
    echo "Cargando datos de ejemplo en la base nueva..."
    docker compose exec -T app-a php artisan db:seed --force
fi

docker compose exec -T app-a php artisan storage:link --relative --force
docker compose exec -T app-a npm ci --no-audit --no-fund
docker compose exec -T app-a npm run build
docker compose exec -T app-a sh -c 'mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache'

echo "Levantando nginx y HAProxy..."
docker compose up -d --remove-orphans
echo "Tienda: http://localhost o http://localhost:8000"
echo "HAProxy Stats: http://localhost:8404 (usuario admin)"
echo "Demo de failover: ./docker/scripts/demo-failover.sh"
