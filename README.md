[![Tests](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/actions/workflows/tests.yml/badge.svg)](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/actions/workflows/tests.yml)

<p align="center">
  <img src="docs/diseno/logo-readme.png" alt="Mar Fragancias" width="460">
</p>

<p align="center">
  <strong>Perfumes de lujo, en decant.</strong><br>
  Tienda web de decants de 5 ml y 10 ml de perfumes originales · Asunción, Paraguay
</p>

---

## Sobre el proyecto

**Mar Fragancias** vende decants de perfumes originales, sobre todo de casas de lujo, para que cada clienta pueda probar y usar fragancias caras pagando solo lo que va a usar. Hoy la venta se hace a mano por Instagram y WhatsApp. Este sistema centraliza el catálogo, el stock y los pedidos, y cierra cada compra por WhatsApp con el pedido ya armado.

Proyecto de la materia **Programación IV**.

## Funcionalidades

**Tienda pública** (Fase 1, terminada)

- Navegación por **marca**, con panel desplegable por casa (En portada, Mujer, Hombre, Para compartir).
- Catálogo con filtros por marca, género, familia de aroma y tamaño, y **búsqueda** sin tildes con sugerencias.
- Página de cada perfume con selección de **decant de 5 ml o 10 ml**, precio y stock.
- **Carrito** sin cuenta obligatoria, con recomendaciones de perfumes con **notas parecidas**.
- **Checkout** con datos personales y de entrega: el pedido queda guardado como *pendiente* y se abre **WhatsApp** con el mensaje listo para enviar.
- **Termómetro olfativo**: qué perfumes van con cada clima, con la temperatura real de Asunción.
- Ingreso y registro de clientas.

**Área de cliente**

- Mi cuenta con datos personales y accesos.
- Mis datos: edición de nombre, teléfono y dirección.
- Mis pedidos con enlace al detalle.

**Panel de administración** (Fase 2, terminada)

- Dashboard con pedidos por confirmar, ventas del mes, decants más vendidos y stock crítico.
- Productos con género, notas y precios por tamaño.
- Pedidos con ciclo de estados (pendiente → confirmado → preparando → enviado → entregado) y descuento automático de stock al confirmar.
- Inventario tipo kardex con cada entrada y salida.
- Marcas, categorías y tamaños.

**API REST v1**

- Catálogo público: marcas, familias de aroma, productos con filtros y ficha con precio y stock por decant.
- Login con token (Laravel Sanctum), consulta de pedidos propios con su historial de estados.
- Administración: pedidos con el mismo ciclo de estados del panel, stock crítico y movimientos de kardex.

## Tecnologías

Laravel 13 · PHP 8.3+ · Blade · Tailwind CSS 4 · Alpine.js · Vite · PHPUnit · MySQL 8 (desarrollo) / SQLite (tests) · iconos Phosphor

## Puesta en marcha

Requisitos: PHP 8.3 o superior, Composer, Node.js 20 o superior y MySQL 8 (o SQLite para probar rápido).

```bash
cd src
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configurá la base de datos en `src/.env` y, si querés, los datos de la tienda:

```dotenv
TIENDA_WHATSAPP=595981000000      # número de WhatsApp de la tienda, con código de país
TIENDA_ENVIO_ASUNCION=20000       # costo de envío en Asunción y Gran Asunción (₲)
```

Crear las tablas con datos de ejemplo y levantar la tienda:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

La tienda queda en <http://localhost:8000>. Para usar Docker con HAProxy, seguí la inicialización de la sección siguiente.

**Usuarios de ejemplo** (creados por los seeders, solo para desarrollo):

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@marfragancia.com` | `password123` |
| Cliente | `mariapaz@example.com` | `password123` |

## Docker y HAProxy

Requisitos en el host: Docker Engine con Docker Compose v2, Bash y curl. Desde la raíz del repositorio:

```bash
# Opcional: elegir una clave para Stats antes de levantar el stack.
export HAPROXY_STATS_PASSWORD='elegi-una-clave'
./setup.sh
```

El script construye y levanta MySQL y PHP-FPM; después inicializa Laravel y levanta nginx, Apache y HAProxy, instala las versiones de `composer.lock` y `package-lock.json`, configura Laravel y compila los assets. Migraciones, seeders y enlace de storage se ejecutan solo desde `app-fpm`. Conserva `.env`, la clave de aplicación y los volúmenes existentes; los datos de ejemplo se cargan automáticamente solo cuando todavía no existe la tabla de migraciones. Para una base que ya tiene migraciones pero quedó sin datos iniciales, ejecutar explícitamente `docker compose exec -T app-fpm php artisan db:seed --force`.

Después de la primera inicialización, para volver a levantar los servicios:

```bash
docker compose up -d
docker compose ps
```

| Servicio | Dirección / acceso |
|---|---|
| Tienda, a través de HAProxy | <http://localhost> o <http://localhost:8000> |
| HAProxy Stats | <http://localhost:8404/> · usuario `admin` |
| MySQL | `localhost:3306` desde el host; `db:3306` entre contenedores |

La contraseña de Stats sale de `HAPROXY_STATS_PASSWORD`; si no se define, es **`marfragancias-demo`**. Se puede exportar en la terminal, definir en un `.env` en la raíz para Compose o configurar como variable del Stack en Portainer. Ese archivo de Compose es distinto del `.env` de Laravel en `src/`. La configuración del frontal es HTTP; TLS requiere configuración adicional.

HAProxy reparte mediante `roundrobin` entre dos arquitecturas distintas:

| Arquitectura | Servicios | X-Servido-Por / X-Nodo |
|---|---|---|
| A: Nginx + PHP-FPM | `web-nginx` → `app-fpm` | `nginx-php-fpm` |
| B: Apache + mod_php | `apache-php` (PHP 8.4 integrado en Apache) | `apache-mod-php` |

Para que la comparación sea justa, las dos tienen **20 procesos PHP fijos** (`docker/php/procesos.conf` y `docker/apache/mpm_prefork.conf`). Ambas comparten código, `.env`, `APP_KEY`, storage y MySQL; sesiones y caché usan la base centralizada. Apache usa `/var/www/public`, rewrite y AllowOverride para las rutas Laravel. Los nodos web/PHP no publican puertos. El setup prepara storage y bootstrap/cache para www-data antes de iniciar los frontales. Al detener cualquiera de los servidores web, el otro sigue atendiendo.

### Tráfico para la demo del Avance 4

Con el stack levantado, generar tráfico moderado contra la tienda y observar ambos nodos en Stats:

```bash
./docker/scripts/trafico-demo.sh       # 120 segundos
./docker/scripts/trafico-demo.sh 360   # duración para la presentación; Ctrl+C para cortar
```

Requiere Bash 4+ y curl 7.84+. Alterna inicio, catálogo, ficha Coromandel y `/up`, hasta 2 solicitudes/s, y muestra conteos por nodo y código HTTP. Recorrido de 9 minutos, credenciales de Stats, continuidad del carrito y plan B: [guion de demo del Avance 4](docs/demo/guion-demo-avance4.md).

### Demo de failover

Esperá a que ambos nodos aparezcan disponibles en Stats y ejecutá:

```bash
./docker/scripts/demo-failover.sh
```

La demo consulta `/up` en bucle, muestra ambos nodos, detiene `web-nginx`, espera su baja por los health checks y exige respuestas HTTP 200 solo de B. Luego inicia A, espera su recuperación y comprueba que ambos vuelven a responder. Si falla después de detener A o se interrumpe con Ctrl+C, intenta volver a iniciarlo. El balanceador comprueba `/up` cada 2 segundos; baja un nodo tras 3 fallos y lo recupera tras 2 aciertos.

La demo comprueba disponibilidad, no continuidad de sesiones. Para esa verificación, iniciá sesión en el navegador, agregá un decant al carrito y ejecutá la demo: al recargar mientras solo B responde y cuando A vuelve, deben conservarse la cuenta y el carrito. No hay afinidad por cookie; esa continuidad depende de la base y la clave compartidas.

### Benchmark A vs B

Con el stack inicializado, ejecutar desde la raíz:

```bash
sg docker -c './docker/scripts/benchmark.sh'
# Opcional: ajustar solicitudes por punto (predeterminado: 2000).
sg docker -c 'BENCHMARK_REQUESTS=2000 ./docker/scripts/benchmark.sh'
```

La carga usa ApacheBench en `httpd:2.4.65-alpine`, dentro de la red Compose, directamente contra cada arquitectura. Mide cuatro endpoints con concurrencias 10/50/100, una corrida por HAProxy y una caída de nginx bajo carga. Guarda salida cruda, Stats y CSV en `docs/benchmark/`; una nueva ejecución reemplaza esos resultados. Prepara producción temporalmente y limpia config/rutas/vistas al terminar, sin cambiar `.env`, clave ni datos comerciales. Las sesiones y la caché centralizadas sí generan escrituras operativas normales. No ejecutar migraciones ni la suite de tests simultáneamente con el benchmark: la configuración compilada de producción es compartida.

Resultado: con los mismos 20 procesos PHP, Nginx + PHP-FPM es entre 5 y 8 % más rápido que Apache + mod_php en las páginas dinámicas. Resultados, corrida con configuración de fábrica, gráficos y limitaciones: [informe de benchmark](docs/benchmark/informe-benchmark.md). Para regenerar PNG: `python3 -I docs/benchmark/graficos.py`, con matplotlib disponible (puede usarse un venv temporal).

### Portainer

En Portainer CE elegí **Stacks → Add stack → Git Repository**, seleccioná la referencia deseada y usá `compose.yaml` como Compose path. Las imágenes se construyen desde el repositorio, sin preparar archivos en el host.

Configurá las variables del Stack: `APP_KEY` (obligatoria y persistente), `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_ROOT_PASSWORD` y `HAPROXY_STATS_PASSWORD`. Generá la clave una sola vez y guardala:

```bash
docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Los puertos pueden cambiarse con `HTTP_PORT` (80), `HTTP_ALT_PORT` (8000), `STATS_PORT` (8404) y `DB_PORT_HOST` (3306). Tras desplegar, verificá `/up` y los dos nodos disponibles en Stats (puerto 8404, usuario `admin`). La base nueva recibe los datos iniciales automáticamente; conservá los volúmenes y la clave al actualizar el Stack.

## Pruebas

Los tests corren automáticamente en cada PR dirigido a `main` y en cada push a `main` mediante [GitHub Actions](.github/workflows/tests.yml).

```bash
cd src
php artisan test --compact
```

Los tests usan SQLite en memoria y no necesitan una base de datos configurada. El plan de pruebas de Auth y Catálogo, con cada caso trazado a su test automático, está en [`docs/pruebas/plan-de-pruebas-auth-catalogo.md`](docs/pruebas/plan-de-pruebas-auth-catalogo.md).

## API REST

Con el stack levantado, la API responde en `http://localhost/api/v1` a través de HAProxy. Ejemplo:

```bash
curl http://localhost/api/v1/productos/coromandel
```

Guía con todos los endpoints, autenticación y ejemplos: [`docs/api/README.md`](docs/api/README.md). Especificación OpenAPI 3.1: [`docs/api/openapi.yaml`](docs/api/openapi.yaml).

## Documentación

| Documento | Ubicación |
|---|---|
| Alcance del proyecto | [`Alcance_Proyecto_Mar_Fragancia.pdf`](Alcance_Proyecto_Mar_Fragancia.pdf) |
| Especificación de diseño | [`docs/superpowers/specs/2026-10-18-tienda-y-admin-design.md`](docs/superpowers/specs/2026-10-18-tienda-y-admin-design.md) |
| Maquetas aprobadas | [`docs/diseno/maquetas/diseno-final.html`](docs/diseno/maquetas/diseno-final.html) (abrir en el navegador) |
| Plan de implementación · Fase 1 | [`docs/superpowers/plans/2026-10-18-fase-1-tienda-publica.md`](docs/superpowers/plans/2026-10-18-fase-1-tienda-publica.md) |
| Plan de pruebas (PI-13) | [`docs/pruebas/plan-de-pruebas-auth-catalogo.md`](docs/pruebas/plan-de-pruebas-auth-catalogo.md) |
| API REST v1 | [`docs/api/README.md`](docs/api/README.md) · [`openapi.yaml`](docs/api/openapi.yaml) |
| MER / DER conceptual y lógico | [`docs/datos/der.md`](docs/datos/der.md) · [SVG para presentación](docs/datos/der-logico.svg) |
| Diccionario del esquema MySQL | [`docs/datos/diccionario-de-datos.md`](docs/datos/diccionario-de-datos.md) |
| Evaluación de normalización | [`docs/datos/normalizacion.md`](docs/datos/normalizacion.md) |
| Benchmark A vs B | [`docs/benchmark/informe-benchmark.md`](docs/benchmark/informe-benchmark.md) |
| Cierre metodológico · Avance 4 (PI-26) | [Sprint Review y retrospectiva final](docs/metodologia/sprint-review-retrospectiva.md) |
| Demo en vivo · Avance 4 (PI-27) | [Guion, comandos y ensayo real](docs/demo/guion-demo-avance4.md) |

El diccionario se regenera desde el esquema MySQL real, con el stack y sus migraciones ya aplicados:

```bash
sg docker -c "docker compose exec -T app-fpm php artisan docs:diccionario"
```

El servicio `app-fpm` monta `./docs` en `/var/docs` para leer `docs/datos/descripciones.php` y escribir el Markdown del repositorio. Si el contenedor se creó antes de ese montaje, recrearlo con `docker compose up -d --no-deps app-fpm` y reiniciar `web-nginx` para resolver su upstream de nuevo. El comando no migra ni cambia datos; rechaza SQLite para no publicar tipos incorrectos. Al agregar una tabla o columna, completar sus descripciones, regenerar desde MySQL y correr los tests de documentación. Estos tests comparan nombres contra las migraciones SQLite, mientras que tipos, índices y acciones de FK del documento se extraen de MySQL.

## Equipo

| Rol | Integrante |
|---|---|
| Scrum Master / Project Manager | José Fernández |
| Backend & Architect Lead | Tobías Franco |
| DevOps & Cloud Engineer | Matías Amarilla · Jonathan Valdez|
| Frontend & QA Engineer | María Paz Gutiérrez |
