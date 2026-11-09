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

## Ejecución y decisiones

- [x] Rama feature/benchmark; plan en el primer commit f4d85bc. Sin push.
- [x] Script reproducible con ApacheBench en httpd:2.4.65-alpine, perfil Compose, Python estándar para orquestación/CSV; no instalación del generador en el host.
- [x] 24 puntos directos reales (4 endpoints × 3 concurrencias × 2 arquitecturas), 2000 solicitudes cada uno; 30 de warm-up por endpoint/nodo. CSV y salida cruda completos.
- [x] Producción temporal, debug false y caches config/rutas/vistas. OPcache confirmado activo en los dos SAPI web; ya estaba incluido por igual en las imágenes, sin cambiar Dockerfiles.
- [x] HAProxy normal c50: 2000 solicitudes, 78.32 RPS, p95 1163 ms, 0 errores; Stats 1000 sesiones por servidor.
- [x] Failover c50 con carga activa confirmada: 954 solicitudes en ensayo de 15 segundos, 63.55 RPS, p95 3174 ms, p99 5079 ms y 0 errores. Antes: ambos UP y 1000/1000 sesiones; carga iniciada: 1038/1038; final: ambos UP y 1539/1543. Reintentos explican que sesiones y solicitudes completadas no sean lo mismo.
- [x] Informe español, 6 PNG salvia y script matplotlib (`python -I`) generado con venv temporal en /tmp. Sin paquetes nuevos del proyecto ni del host.
- [x] TDD de scripts: RED por script ausente, GREEN; regresiones RED→GREEN para caída antes de carga y nodo previamente DOWN. 3 tests/26 assertions nuevos; Pint y suite final284/2625 verdes.
- [x] Restauración comprobada: ambos nodos UP, tienda HTTP200, APP_ENV local y caches de producción retiradas; sin cambios de .env/APP_KEY ni datos comerciales. GET con sesiones/cache database crea escrituras operativas propias de la carga; no se hicieron migraciones ni seeders.
- [x] README enlaza informe y explica ejecución y resultados reemplazables.

**Decisiones y revisión:**
- Se mantiene la configuración original (FPM5 hijos frente a Apache150 workers), documentada como limitación; no se ajustó una arquitectura para mejorar sus números. Se eligió ab por sus percentiles/contadores y versión fija dentro de Docker.
- Failover por duración15s, separado de la comparación directa con cantidad fija, para garantizar tiempo de caída y recuperación. El script espera tráfico servido por Nginx y ambos nodos UP antes de provocar la caída, y exige ambos UP al restaurar.
- La revisión detectó que Docker podía cambiar la IP de Nginx tras stop/start y HAProxy retenía la dirección inicial. Se agregó resolución DNS Docker en haproxy.cfg, validada con el parser y con recuperación real. Dos ensayos iniciales de failover se descartaron; crudos y métricas descartados permanecen identificados en resultados/. Tras corregir DNS se repitieron ambos escenarios HAProxy, sin repetir los 24 puntos directos que no usan HAProxy.
- La primera verificación web de OPcache se hizo manualmente durante la corrida, además de comprobar ini antes; el script final hace esa verificación automáticamente antes de medir con una probe temporal que elimina. No se atribuyen resultados a optimizaciones no aplicadas.
- Matplotlib faltaba y Python no tenía ensurepip: venv sin pip y bootstrap oficial de pip, todo exclusivamente en /tmp. No es dependencia de runtime Laravel.
- Menor diferido: el test del benchmark presupone el CSS compilado del checkout, como la suite de vistas existente; no verifica un clon sin assets. README requiere stack inicializado.
- Límites: única máquina y una corrida por punto, orden fijo, sin confianza estadística; generador compite por CPU, límites de workers distintos y muestras estáticas muy cortas. No valida continuidad de sesión/carrito, TLS, carga de producción ni Portainer.
