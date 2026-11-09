# Plan · Benchmarking A vs B (Avance 4)

**Rama:** `feature/benchmark`, creada desde `main` actualizado. **No hacer push**: Claude revisa y pushea.

## Objetivo

Cumplir el entregable del Avance 4 del PDF: "Pruebas de rendimiento (Benchmarking RPS/Latencia Arq A vs B)" y "Dashboard HAProxy Stats (Puerto 8404) con tráfico real".

Las dos arquitecturas a comparar son:

- **A:** `nginx-php-fpm`, con los servicios `web-nginx` y `app-fpm`.
- **B:** `apache-mod-php`, con el servicio `apache-php`.

El stack ya está levantado con Docker. Usá `sg docker -c "..."` porque el shell todavía no tiene el grupo `docker`.

## Diseño

### 1. Script `docker/scripts/benchmark.sh`, reproducible

- La carga se genera **dentro de la red de Compose**: los nodos no publican puertos, y así cada arquitectura se mide directo, sin HAProxy en el medio.
- Herramienta: `ab` (ApacheBench) o `hey`, en un contenedor de imagen con versión fija, lanzado con `docker compose run --rm` o `docker run --network <red del proyecto>`. Elegí uno, justificalo en el informe y no instales nada en el host.
- Endpoints, con la misma carga para A y para B:
  - `/up`: el costo mínimo de cada pila.
  - `/perfumes`: el catálogo, con consultas a la base y render de Blade.
  - `/perfumes/coromandel`: la ficha de un producto.
  - Un asset estático, por ejemplo `/build/assets/app-*.css` o una foto de `storage/productos`. Sirve para ver cómo sirve archivos cada servidor web.
- Antes de medir:
  - preparar el entorno de producción con `APP_ENV=production`, `APP_DEBUG=false` y `php artisan config:cache route:cache view:cache` desde `app-fpm`;
  - verificar que OPcache esté habilitado en las dos imágenes; si no lo está, habilitarlo en los dos Dockerfiles por igual, para que la comparación sea justa;
  - hacer un warm-up de cada endpoint.
- Niveles de concurrencia: 10, 50 y 100. Una cantidad fija de requests por corrida, de unos 2000 por punto, que se pueda ajustar por variable de entorno.
- Al terminar, restaurar el entorno: `config:clear` y valores de desarrollo. **No tocar la base de datos.**
- Salida cruda en `docs/benchmark/resultados/` y un CSV consolidado en `docs/benchmark/resultados.csv`. Cada fila lleva arquitectura, endpoint, concurrencia, RPS, latencia media, p50, p95, p99 y cantidad de errores.

### 2. Corrida a través de HAProxy

Una corrida contra `haproxy:80` sobre `/perfumes`, con concurrencia 50. Sirve para dos cosas:

- dejar tráfico real en HAProxy Stats y mostrar en el informe que la carga se reparte entre los dos nodos (sesiones y bytes por servidor, sacados de `http://haproxy:8404/;csv` con usuario `admin`);
- tener una variante con failover: durante la corrida se detiene `web-nginx` unos segundos y se cuentan los errores. La meta es 0 o casi 0. Restaurar `web-nginx` siempre, con un `trap`, como hace `demo-failover.sh`.

### 3. Informe `docs/benchmark/informe-benchmark.md`, en español

- Entorno: CPU, RAM, versiones de las imágenes, herramienta y parámetros.
- Metodología.
- Tablas A vs B por endpoint y concurrencia.
- Gráficos en PNG: RPS por concurrencia y p95 por concurrencia, para cada endpoint dinámico. Generalos con un script `docs/benchmark/graficos.py` (matplotlib, `python3 -I`); si matplotlib no está, con un venv en `/tmp`. Para el estilo, paleta salvia de la marca y sin emojis.
- Resultado de la corrida por HAProxy y del failover bajo carga.
- Conclusiones honestas, sacadas de los números. No inventes ni redondees a favor de nadie.
- Limitaciones: es una sola máquina, el generador de carga compite por CPU, etc.

### 4. Tests

En `ScriptsDockerTest` o en un test nuevo, con `docker` y `curl` simulados como en los tests actuales, comprobar que:

- el script mide las dos arquitecturas y todos los endpoints;
- escribe el CSV con las columnas esperadas;
- restaura `web-nginx` y la configuración aunque falle a mitad de camino.

### 5. README

En la sección de Docker, cómo correr el benchmark y el link al informe.

## Verificación

- Correr de verdad `./docker/scripts/benchmark.sh` y commitear los resultados reales.
- Al final, el stack tiene que quedar arriba y funcionando, con las dos arquitecturas en verde en Stats.
- `cd src && vendor/bin/pint --dirty --format agent && php artisan test --compact` en verde.
- Commits chicos en español, sin atribución a IA. **No pushear.**
- Al terminar, resumí los números principales y cualquier cosa que no hayas podido medir.
