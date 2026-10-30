#!/bin/bash
set -e

echo "🛡️ Iniciando Despliegue Ultra-Verificado (Fix Timeout)..."

# 1. Limpieza profunda
echo "🧹 Limpiando contenedores y volúmenes previos..."
docker compose down -v

# 2. Levantar infraestructura
echo "📦 Levantando contenedores (App, Webserver, DB)..."
docker compose up -d --build

# 3. Verificación basada en Healthcheck de Docker
echo "⏳ Esperando a que MySQL reporte estado SALUDABLE (Healthy)..."
MAX_RETRIES=20
COUNT=0
until [ "$(docker inspect -f '{{.State.Health.Status}}' mar_fragancia_db)" == "healthy" ]; do
    COUNT=$((COUNT+1))
    if [ $COUNT -ge $MAX_RETRIES ]; then
        echo "❌ Error: La base de datos nunca alcanzó el estado 'healthy'."
        docker compose logs db
        exit 1
    fi
    echo "   Esperando... ($COUNT/$MAX_RETRIES)"
    sleep 3
done
echo "✅ Base de datos saludable y lista."

# Definir el nombre del servicio
SERVICE="app"

# 4. Configuración de Laravel
echo "⚙️ Instalando dependencias de PHP con Timeout extendido..."

# FIX: Aumentamos el timeout de Composer a 2000 segundos para evitar el error de extracción
docker compose exec $SERVICE sh -c "composer config process-timeout 2000"
docker compose exec $SERVICE composer update --no-interaction --optimize-autoloader

echo "📝 Configurando entorno (.env)..."
docker compose exec $SERVICE sh -c "cp .env.example .env"
docker compose exec $SERVICE sh -c "sed -i 's/DB_HOST=127.0.0.1/DB_HOST=db/' .env"
docker compose exec $SERVICE sh -c "sed -i 's/DB_DATABASE=laravel/DB_DATABASE=mar_fragancia/' .env"
docker compose exec $SERVICE sh -c "sed -i 's/DB_USERNAME=root/DB_USERNAME=laravel/' .env"
docker compose exec $SERVICE sh -c "sed -i 's/DB_PASSWORD=/DB_PASSWORD=password/' .env"

echo "🔑 Generando clave de aplicación..."
docker compose exec $SERVICE php artisan key:generate

# 5. Base de Datos y Datos
echo "🗄️ Ejecutando migraciones y seeders..."
docker compose exec $SERVICE php artisan migrate:fresh --force
docker compose exec $SERVICE php artisan db:seed --force

# 6. Frontend y Assets
echo "🎨 Compilando assets de Vite..."
docker compose exec $SERVICE npm install
docker compose exec $SERVICE npm run build

echo "--------------------------------------------------"
echo "✨ DESPLIEGUE COMPLETADO EXITOSAMENTE"
echo "🌐 Accede a la tienda en: http://localhost"
echo "🌐 O también en: http://localhost:8000"
echo "--------------------------------------------------"
