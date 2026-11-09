# Plan · HAProxy y dos nodos web (Avance 3)

**Rama:** `feature/haproxy` (crear desde `main` actualizado). **No hacer push**: Claude revisa y pushea.

## Objetivo

Cumplir el requisito 6.3 y el Avance 3 del PDF de la materia:

- HAProxy como balanceador frontal que reparte el tráfico entre **dos nodos web** (Arquitectura A y Arquitectura B).
- Algoritmo `roundrobin`.
- **HAProxy Stats en el puerto 8404**.
- **Health checks** y **failover automático**: si un nodo cae, todo el tráfico va al otro sin que la clienta pierda la sesión.
- Sesiones centralizadas en la base de datos (MySQL compartido).

Actualización solicitada: A es Nginx + PHP-FPM (`web-nginx` y `app-fpm`); B es Apache + mod_php (`apache-php`), con imagen propia PHP 8.4 Apache. Son implementaciones distintas con MySQL, sesiones y storage compartidos.

## Estado actual

- `compose.yaml` (raíz) tiene `app` (php-fpm, `docker/php/Dockerfile`), `webserver` (nginx con puertos 80 y 8000) y `db` (MySQL 8 con healthcheck).
- `docker/nginx/conf.d/default.conf` hace `fastcgi_pass app:9000`.
- `setup.sh` levanta todo, instala dependencias, migra y siembra usando el servicio `app`.
- `src/Dockerfile` es una plantilla de Node que no se usa: **borrarla**.
- Laravel ya expone `/up` (`src/bootstrap/app.php`). `SESSION_DRIVER=database` y `CACHE_STORE=database` en `.env.example`.
- En esta máquina **no hay Docker**, así que el stack no se puede levantar. Validá lo que puedas a mano y dejalo explicado.

## Tareas

### 1. Laravel detrás de un proxy (con TDD)

En `src/bootstrap/app.php`, confiar en el proxy: `$middleware->trustProxies(at: '*');`. HAProxy y nginx están en la red interna de Docker.

Test nuevo en `src/tests/Feature/Infraestructura/BalanceoTest.php`. Escribirlo primero y verlo fallar:

- `GET /up` responde 200.
- Si la request trae `X-Forwarded-Proto: https` y `X-Forwarded-Host`, `url('/')` usa https y ese host.

### 2. Dos nodos en `compose.yaml`

- Reemplazar `app` + `webserver` por `app-fpm` + `web-nginx` y `apache-php` + `apache-php`.
  - Los dos `app-*` usan el mismo build (`docker/php/Dockerfile`), el mismo volumen `./src:/var/www` (y por lo tanto el mismo `.env` y `APP_KEY`) y el mismo MySQL.
  - En el environment de los dos: `SESSION_DRIVER: database`, `CACHE_STORE: database`, `APP_NODE: nginx-php-fpm` / `apache-mod-php`.
- Los `web-*` **no publican puertos**: solo HAProxy da la cara.
- nginx: usar un único template `docker/nginx/templates/default.conf.template` con `fastcgi_pass ${PHP_UPSTREAM}:9000;`. La imagen nginx:alpine aplica envsubst solo a las variables definidas.
  - Pasar `PHP_UPSTREAM=app-fpm` o `apache-php` a cada nginx.
  - Agregar el header `add_header X-Nodo $hostname always;` o equivalente para ver qué nodo respondió.
  - Borrar `docker/nginx/conf.d/default.conf` si deja de usarse.
- Servicio `haproxy`:
  - `image: haproxy:2.9-alpine` (versión fija).
  - Montar `docker/haproxy/haproxy.cfg:/usr/local/etc/haproxy/haproxy.cfg:ro`.
  - Puertos `80:80`, `8000:80` y `8404:8404`.
  - `depends_on` los dos `web-*`.

### 3. `docker/haproxy/haproxy.cfg`

- `frontend` http en `:80`.
- `backend` con `balance roundrobin`, `option httpchk GET /up` y `http-check expect status 200`.
- `server nginx-php-fpm web-nginx:80 check inter 2s fall 3 rise 2` y lo mismo para `apache-mod-php`.
- `option forwardfor`, `http-request set-header X-Forwarded-Proto http` y el header de respuesta `X-Servido-Por %s`, para ver el nodo en la demo.
- `listen stats` en `:8404` con `stats enable`, `stats uri /`, `stats refresh 5s` y `stats auth admin:<clave>`. La clave sale de una variable de entorno con default documentado.
- Timeouts razonables y comentarios breves en español.

### 4. Scripts

- `setup.sh`: adaptarlo a `app-fpm`. Migraciones, seeders y `storage:link` se corren **una sola vez** desde `app-fpm`.
- `docker/scripts/demo-failover.sh`:
  1. hace curl en bucle a `http://localhost/up` mostrando `X-Servido-Por`, para ver que alterna A/B;
  2. ejecuta `docker compose stop web-nginx` y muestra que sigue respondiendo solo B, sin errores;
  3. ejecuta `docker compose start web-nginx` y muestra que A vuelve.
  - Sin emojis en los scripts nuevos.

### 5. Limpieza y documentación

- Borrar `src/Dockerfile` (la plantilla de Node).
- README, en la sección de Docker: cómo levantar el stack, las URLs (tienda en :80/:8000, Stats en :8404 con su usuario), cómo correr la demo de failover y la nota sobre Portainer.
  - La nota de Portainer: el `compose.yaml` se puede desplegar como Stack desde el repositorio Git.
  - Explicar que A y B son por ahora idénticos hasta que el profesor asigne las arquitecturas.

## Verificación

- `cd src && vendor/bin/pint --dirty --format agent && php artisan test --compact`: todo en verde, incluido el test nuevo.
- Revisar `compose.yaml` y `haproxy.cfg` a mano: nombres de servicio coherentes y ningún puerto publicado fuera de HAProxy y MySQL.
- Commits chicos en español, en la rama `feature/haproxy`, **sin atribución a IA**. No pushear.
- Al terminar, resumí qué cambió y qué no pudiste verificar por no tener Docker.

## Registro de ejecución

- [x] Tarea 1: proxy y health check. RED: la URL ignoraba HTTPS y host reenviados. GREEN: 2 tests nuevos; suite 273 tests / 2559 assertions. Pint correcto.
- [x] Tarea 2: dos nodos web y MySQL compartido. YAML parseado, dependencias/montajes revisados y template renderizado para ambos upstreams conservando las variables de nginx. Solo HAProxy/MySQL publican puertos.
- [x] Tarea 3: HAProxy y Stats. Parser nativo HAProxy 2.9.15 acepta la configuración con contraseña de entorno y aliases locales para los nombres Docker. No equivale a levantar el stack.
- [x] Tarea 4: inicialización y demo de failover. RED → GREEN: 5 tests de scripts con dobles de docker/curl/sleep. Comprueban éxito, error HTTP, cabecera ausente, recuperación y setup inicial/repetido. Suite 278 tests / 2589 assertions.
- [x] Tarea 5: limpieza y documentación. Dockerfile Node, Compose debug que lo referenciaba y nginx viejo sin consumidores retirados. README incluye inicialización, URLs, credenciales de Stats, demo, prueba manual de sesión y condiciones para Portainer/Git.
- [x] Verificación final: Pint, `php artisan test --compact` (281 tests / 2599 assertions), `bash -n` y `git diff --check` correctos. Revisión independiente cerrada con regresiones para sus tres hallazgos.

**Decisiones técnicas:** `setup.sh` conserva `.env`, APP_KEY y volúmenes; usa `composer install`, `npm ci` y `migrate --force`. Si no existe tabla de migraciones, carga seeders una sola vez desde `app-fpm`; no vuelve a sembrar una BD existente. Crea el enlace de storage relativo para que funcione dentro y fuera del contenedor. La demo comprueba HTTP 200 y ambos nodos, espera los health checks y tiene recuperación de `web-nginx` ante errores/interrupciones. Los tests con dobles no prueban Docker ni continuidad real de sesiones.

**Portainer:** el Stack Git requiere preparar el entorno y resolver los bind mounts relativos en el host Docker. La función automática de volúmenes relativos es de Business Edition; se documenta esa condición sin afirmar un despliegue real no ejecutado.

**Correcciones de la revisión final (RED → GREEN):**
- Se configura el destino relativo del enlace en `config/filesystems.php` y se usa `storage:link --force`: `--relative` requería `symfony/filesystem`, que no está instalado. El test ejecuta ahora la operación real de Laravel sobre un checkout temporal y comprueba que el enlace resuelve al storage compartido. No se agregó ninguna dependencia.
- La consulta inicial de la base distingue repositorio de migraciones existente (0), ausencia confirmada (10) y errores (otros códigos). Un fallo de conexión/ejecución aborta sin migrar ni sembrar. Se comprobaron los tres estados con Laravel real sobre SQLite aislada y se agregó regresión del script; MySQL sigue pendiente.
- La demo exige el estado HTTP 200 además de `X-Servido-Por`; una redirección 302 ahora falla. Se trata como corrección del contrato de la demo, aunque la revisión la calificó menor.

**Verificación nativa adicional:** HAProxy 2.9.15 compilado temporalmente fuera de los archivos versionados. Parser sobre el cfg real válido; prueba de ejecución con backends HTTP de prueba y puertos/direcciones locales adaptados: roundrobin A/B, Stats 401 sin clave / 200 con la clave del entorno, caída de A deja respuestas 200 de B y recuperación reincorpora A. No sustituye Docker.

**No verificado:** construcción/arranque del stack Docker, permisos y comunicación reales nginx/PHP/MySQL, continuidad de sesión/carrito entre nodos y despliegue Portainer. A/B permanecen idénticas por decisión explícita del plan; TLS y rendimiento bajo carga quedan fuera de este avance. No se hicieron push ni cambios a migraciones existentes.

## Actualización: arquitecturas distintas

Se reemplaza el segundo nginx/PHP-FPM por Apache con mod_php. Servicios y cabeceras usan nombres de arquitectura; inicialización exclusiva desde app-fpm. Pruebas de scripts: RED 6 fallos antes de renombrar producción, GREEN 8 tests. Validación Docker y demo se registran al cerrar este bloque. Las notas anteriores de validación sin Docker corresponden a la ejecución original.

### Validación Docker de las arquitecturas distintas

- `docker compose down --remove-orphans` sin `-v`; `./setup.sh` terminó correctamente con `sg docker -c`. Base existente conservada, migraciones sin cambios pendientes, sin volver a sembrar.
- Apache construido desde `php:8.4-apache`; módulos php, rewrite y headers confirmados. Ambos procesos www-data pueden escribir storage y bootstrap/cache.
- GET `/` real: seis respuestas HTTP200 alternadas apache-mod-php / nginx-php-fpm, con X-Nodo coincidente.
- Demo real: alternancia inicial, seis HTTP200 exclusivos de Apache con nginx detenido, recuperación y alternancia de ambos. También se detuvo Apache: GET `/` siguió HTTP200 desde nginx; Apache restaurado al final.
- Pint y suite local: 281 tests / 2599 assertions verdes. Se prepararon ACL locales para el usuario del host y www-data tras detectar errores de permisos al compartir los directorios.
- No push. Pendiente fuera de este bloque: Portainer, continuidad real de sesión/carrito y benchmarks. Las secciones previas describen el plan histórico; esta actualización reemplaza su segundo nodo idéntico por Apache y sus nombres por los de arquitectura.
