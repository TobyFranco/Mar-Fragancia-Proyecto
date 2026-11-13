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

Proyecto de la materia **Programación IV** · Entrega final: **27/11/2026**.

Para evaluar la entrega, comenzar por [Entregables del proyecto](#entregables-del-proyecto), seguir la [puesta en marcha con Docker](#puesta-en-marcha) y consultar la [auditoría de requisitos PI-28](docs/entrega/auditoria-pi28.md).

## Entregables del proyecto

Mapa de las secciones **2, 3, 5, 6 y 8** de la consigna. Avances de 2026: **A1 28/08 · A2 25/09 · A3 30/10 · A4 27/11**. La [auditoría PI-28](docs/entrega/auditoria-pi28.md) detalla cada requisito, la comprobación contra el código y sus límites. La correspondencia de tareas proviene del [registro PI-1 a PI-28](docs/metodologia/sprint-review-retrospectiva.md#estado-y-trazabilidad-de-jira); una tarea finalizada en ese registro no sustituye un acta ni acredita una aprobación del docente.

| Requisito del PDF / hito | Documento o evidencia para evaluar | Jira |
|---|---|---|
| §2: Scrum, tablero digital y Product Backlog priorizado con criterios · A1 | [Metodología y tablero](docs/metodologia/sprint-review-retrospectiva.md#1-resumen-del-proyecto-y-metodología); exportación por adjuntar | [PI-14](https://marfragancias.atlassian.net/browse/PI-14) |
| §2: Sprint Backlog, Planning de 2–3 semanas y reportes · A2 | [Matriz metodológica y registro de ceremonias](docs/metodologia/sprint-review-retrospectiva.md#4-registro-de-ceremonias); fuentes del equipo por adjuntar | [PI-15](https://marfragancias.atlassian.net/browse/PI-15), [PI-16](https://marfragancias.atlassian.net/browse/PI-16) |
| §2: Daily, Review, Retrospective y tablero actualizado · A3 | [Registro único de actas](docs/metodologia/sprint-review-retrospectiva.md#4-registro-de-ceremonias); pendiente de completar | [PI-15](https://marfragancias.atlassian.net/browse/PI-15), [PI-26](https://marfragancias.atlassian.net/browse/PI-26) |
| §3.A: caso PyME, objetivos, límites y entregables · A1 | [Alcance vigente](docs/alcance.md) · [PDF](Alcance_Proyecto_Mar_Fragancia.pdf) | [PI-1](https://marfragancias.atlassian.net/browse/PI-1) |
| §3.B: MER/DER conceptual y lógico · A1 | [Modelo y cardinalidades](docs/datos/der.md) · [SVG](docs/datos/der-logico.svg) | [PI-2](https://marfragancias.atlassian.net/browse/PI-2) |
| §3.B: tablas, campos, tipos, PK/FK, restricciones e índices · A1 | [Diccionario MySQL](docs/datos/diccionario-de-datos.md) | [PI-4](https://marfragancias.atlassian.net/browse/PI-4) |
| §3.B: justificación de 3FN | [Normalización y dos excepciones deliberadas](docs/datos/normalizacion.md); requiere revisión académica si se exige 3FN estricta | [PI-2](https://marfragancias.atlassian.net/browse/PI-2), [PI-4](https://marfragancias.atlassian.net/browse/PI-4) |
| §5: asignación formal de los cuatro roles | [Equipo](#equipo) y [responsabilidades](docs/alcance.md#roles-y-responsabilidades-actuales) | [PI-26](https://marfragancias.atlassian.net/browse/PI-26) |
| §6.1: Laravel y BD relacional; entorno Docker · A1 | [Puesta en marcha](#puesta-en-marcha), [stack base](compose.yaml) y [override local](compose.override.yaml) | [PI-3](https://marfragancias.atlassian.net/browse/PI-3), [PI-10](https://marfragancias.atlassian.net/browse/PI-10) |
| Implementación del alcance: usuarios/roles, autenticación y autorización · A2 | [Plan de pruebas Auth/Catálogo](docs/pruebas/plan-de-pruebas-auth-catalogo.md), [autenticación API](docs/api/README.md#autenticación) | [PI-5](https://marfragancias.atlassian.net/browse/PI-5), [PI-6](https://marfragancias.atlassian.net/browse/PI-6), [PI-7](https://marfragancias.atlassian.net/browse/PI-7) |
| §8 A2: CRUD principal y reglas de negocio | [Plan del panel](docs/pruebas/plan-de-pruebas-panel-admin.md), [tests administrativos](src/tests/Feature/Admin) | [PI-8](https://marfragancias.atlassian.net/browse/PI-8) |
| Entorno y datos de ejemplo | [Inicialización](setup.sh), [seeders](src/database/seeders), [usuarios de ejemplo](#puesta-en-marcha) | [PI-9](https://marfragancias.atlassian.net/browse/PI-9) |
| Seguimiento técnico: pipeline CI | [Hallazgo de auditoría](docs/entrega/auditoria-pi28.md#trazabilidad-complementaria); no hay pipeline versionado en el árbol actual | [PI-11](https://marfragancias.atlassian.net/browse/PI-11) |
| Diseño y QA de la solución | [Maquetas](docs/diseno/maquetas/diseno-final.html), [plan Auth/Catálogo](docs/pruebas/plan-de-pruebas-auth-catalogo.md) y [pruebas actuales](#pruebas) | [PI-12](https://marfragancias.atlassian.net/browse/PI-12), [PI-13](https://marfragancias.atlassian.net/browse/PI-13), [PI-25](https://marfragancias.atlassian.net/browse/PI-25) |
| §6.2: sesiones centralizadas · A2 | [Arquitectura compartida](#docker-y-haproxy), [test de sesiones](src/tests/Feature/Infra/SesionesEnBaseDeDatosTest.php), [ensayo del carrito](docs/demo/ensayo-2026-11-12/carrito.txt) | [PI-19](https://marfragancias.atlassian.net/browse/PI-19) |
| §6.4: despliegue del Stack en Portainer · A2 | [Guía Portainer](#portainer), [entrypoint](docker/php/entrypoint.sh), [tests](src/tests/Feature/Infraestructura/PortainerTest.php); constancia del despliegue por adjuntar en PI-26 | [PI-23](https://marfragancias.atlassian.net/browse/PI-23) |
| §6.3: HAProxy, algoritmo y health checks · A3 | [Arquitectura](#docker-y-haproxy), [configuración HAProxy](docker/haproxy/haproxy.cfg) | [PI-17](https://marfragancias.atlassian.net/browse/PI-17) |
| §8 A3: arquitecturas A y B activas | [Stack A/B](#docker-y-haproxy), [ensayo con ambos nodos UP](docs/demo/guion-demo-avance4.md#registro-del-ensayo-real) | [PI-18](https://marfragancias.atlassian.net/browse/PI-18) |
| §6.3: failover automático y demostración en vivo · A3 | [Demo reproducible](#demo-de-failover), [guion](docs/demo/guion-demo-avance4.md), [salida real](docs/demo/ensayo-2026-11-12/failover.txt) | [PI-20](https://marfragancias.atlassian.net/browse/PI-20) |
| §3.A: API REST como entregable | [Guía API](docs/api/README.md) · [OpenAPI 3.1](docs/api/openapi.yaml) | [PI-22](https://marfragancias.atlassian.net/browse/PI-22) |
| Implementación del alcance: área de cliente | [Funcionalidades](#funcionalidades), [rutas web](src/routes/web.php), [tests del área](src/tests/Feature/Tienda/AreaClienteTest.php) | [PI-24](https://marfragancias.atlassian.net/browse/PI-24) |
| §8 A4: cierre metodológico final | [Sprint Review y retrospectiva](docs/metodologia/sprint-review-retrospectiva.md); acuerdos y actas pendientes del equipo | [PI-26](https://marfragancias.atlassian.net/browse/PI-26) |
| §8 A4: benchmark RPS y latencia A/B | [Informe, método, gráficos y limitaciones](docs/benchmark/informe-benchmark.md) · [CSV](docs/benchmark/resultados.csv) | [PI-21](https://marfragancias.atlassian.net/browse/PI-21) |
| §6.3 y §8 A4: Stats 8404 con tráfico real | [Guion y registro](docs/demo/guion-demo-avance4.md#registro-del-ensayo-real), [cuatro snapshots y resumen](docs/demo/ensayo-2026-11-12/resumen.json) | [PI-27](https://marfragancias.atlassian.net/browse/PI-27) |
| §8 A4: Git y documentación técnica/metodológica | [Auditoría completa](docs/entrega/auditoria-pi28.md), [documentación de apoyo](#documentación), [validaciones](docs/entrega/validaciones-pi28.txt) | [PI-28](https://marfragancias.atlassian.net/browse/PI-28) |

### Pendientes del equipo para la entrega

El [registro de pendientes de PI-26](docs/metodologia/sprint-review-retrospectiva.md#pendientes-para-la-entrega) centraliza las actas, los backlogs, los reportes y la constancia de Portainer; completar allí sus evidencias. Pendientes adicionales detectados en esta auditoría:

- Obtener confirmación del docente para el equipo actual de cinco integrantes: la consigna §5 indica grupos de 3 a 4.
- Adjuntar constancia de presentación y aprobación de cada avance; §8 lo exige para habilitar la Defensa Final y Git no demuestra esa aprobación.
- PI-11: localizar el pipeline y su evidencia de ejecución, o aclarar su ausencia en esta entrega; no hay configuración de CI versionada en el árbol actual.
- Acordar con el docente la aceptación de las dos excepciones de 3FN documentadas; cualquier cambio de esquema requiere otra tarea.

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

Laravel 13 (lock: 13.39.0) · PHP 8.3+ (Docker: 8.4) · Blade · Tailwind CSS 4 · Alpine.js · Vite · PHPUnit · MySQL 8 (desarrollo) / SQLite (tests) · iconos Phosphor

## Puesta en marcha

Para la entrega se usa el stack Docker con HAProxy. Requisitos en el host: Docker Engine con Compose v2, Bash 4+ y curl (7.84+ para el generador de tráfico). No se necesita PHP ni Node en el host para inicializar la tienda con Docker.

### Primera inicialización local

Desde la raíz del repositorio:

```bash
./setup.sh
docker compose up -d --build
docker compose ps
curl --fail --silent --show-error --include http://localhost/up
```

[`setup.sh`](setup.sh) crea `src/.env` si falta, instala las dependencias de ambos lockfiles, genera una `APP_KEY` solo si no existe, aplica migraciones, carga datos de ejemplo en una base nueva, crea el enlace de storage, compila assets y prepara permisos. Después levanta los frontales. El `up -d --build` construye también las imágenes actuales de los nodos web. Esperar ambos nodos **UP/L7OK** en Stats antes de la demo.

### Stack local ya inicializado

```bash
docker compose up -d --build
docker compose ps
curl --fail --silent --show-error --include http://localhost/up
```

Compose combina automáticamente [`compose.yaml`](compose.yaml) con [`compose.override.yaml`](compose.override.yaml). El override monta `src/`, storage y configuración local; los dos nodos PHP leen la misma clave desde `src/.env`, y ejecutan `php-fpm` y `apache2-foreground` con el entrypoint estándar. Así se evita ejecutar la inicialización de producción sobre los montajes de desarrollo. El montaje de `docs/` permite generar el diccionario desde `app-fpm`.

Conservar `src/.env`, su `APP_KEY` y el volumen MySQL al actualizar. Los datos iniciales solo se cargan automáticamente si todavía no existe la tabla de migraciones. Para una base con migraciones pero sin datos de ejemplo, ejecutar una vez `docker compose exec -T app-fpm php artisan db:seed --force`. Si se agregan migraciones o dependencias, volver a ejecutar `./setup.sh`; `up --build` por sí solo no instala dependencias dentro del montaje local ni migra la base.

Datos opcionales en `src/.env`:

```dotenv
TIENDA_WHATSAPP=595981000000
TIENDA_ENVIO_ASUNCION=20000
```

### Alternativa sin Docker

Requiere PHP 8.3+, Composer, Node 22.13+ (compatible con los lockfiles actuales) y MySQL 8 o SQLite. Desde la raíz:

```bash
cd src
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Configurar `src/.env` con la conexión elegida antes de migrar. Para SQLite, crear `src/database/database.sqlite` y usar `DB_CONNECTION=sqlite`; para MySQL, configurar host, puerto, base y credenciales de una base existente.

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

La alternativa local queda en <http://localhost:8000> y sirve un solo proceso web; para presentar A/B y failover usar Docker. No ejecutar `artisan serve` en 8000 al mismo tiempo que el stack, que publica ese puerto.

**Usuarios de ejemplo** (creados por los seeders, solo para desarrollo):

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@marfragancia.com` | `password123` |
| Cliente | `mariapaz@example.com` | `password123` |

## Docker y HAProxy

La [puesta en marcha](#puesta-en-marcha) inicializa el entorno local. Opcionalmente, antes de levantarlo, definir `HAPROXY_STATS_PASSWORD` en la terminal o en el `.env` de Compose de la raíz.

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

Para que la comparación sea justa, las dos tienen **20 procesos PHP fijos** (`docker/php/procesos.conf` y `docker/apache/mpm_prefork.conf`). En desarrollo ambas comparten código, `src/.env`, `APP_KEY`, storage y MySQL; en Portainer usan el código de sus imágenes y la misma clave/BD mediante variables del Stack; sesiones y caché usan la base centralizada. Apache usa `/var/www/public`, rewrite y AllowOverride para las rutas Laravel. Los nodos web/PHP no publican puertos. El setup prepara storage y bootstrap/cache para www-data antes de iniciar los frontales. En Portainer lo hace el entrypoint de las imágenes; solo A aplica migraciones y seeders. Al detener cualquiera de los servidores web, el otro sigue atendiendo.

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
./docker/scripts/benchmark.sh
# Opcional: ajustar solicitudes por punto (predeterminado: 2000).
BENCHMARK_REQUESTS=2000 ./docker/scripts/benchmark.sh
```

La carga usa ApacheBench en `httpd:2.4.65-alpine`, dentro de la red Compose, directamente contra cada arquitectura. Mide cuatro endpoints con concurrencias 10/50/100, una corrida por HAProxy y una caída de nginx bajo carga. Guarda salida cruda, Stats y CSV en `docs/benchmark/`; una nueva ejecución reemplaza esos resultados. Prepara producción temporalmente y limpia config/rutas/vistas al terminar, sin cambiar `.env`, clave ni datos comerciales. Las sesiones y la caché centralizadas sí generan escrituras operativas normales. No ejecutar migraciones ni la suite de tests simultáneamente con el benchmark: la configuración compilada de producción es compartida.

Resultado: con los mismos 20 procesos PHP, Nginx + PHP-FPM es entre 5 y 8 % más rápido que Apache + mod_php en las páginas dinámicas. Resultados, corrida con configuración de fábrica, gráficos y limitaciones: [informe de benchmark](docs/benchmark/informe-benchmark.md). Para regenerar PNG: `python3 -I docs/benchmark/graficos.py`, con matplotlib disponible (puede usarse un venv temporal).

### Portainer

En un entorno Docker Standalone administrado por Portainer CE, elegí **Stacks → Add stack → Git Repository**, usá `https://github.com/TobyFranco/Mar-Fragancia-Proyecto`, seleccioná la referencia deseada y configurá **solo `compose.yaml`** como Compose path, sin archivos adicionales. `compose.override.yaml` es exclusivo del desarrollo local: no incluirlo en el Stack. Las imágenes se construyen desde el repositorio, sin preparar `src/.env`, vendor ni assets en el host. Esta guía no corresponde a Docker Swarm.

Configurá las variables del Stack: `APP_KEY` (obligatoria y persistente), `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_ROOT_PASSWORD` y `HAPROXY_STATS_PASSWORD`. Generá la clave una sola vez y guardala:

```bash
docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Los puertos pueden cambiarse con `HTTP_PORT` (80), `HTTP_ALT_PORT` (8000), `STATS_PORT` (8404) y `DB_PORT_HOST` (3306). Tras desplegar, verificá `/up` y los dos nodos disponibles en Stats (puerto 8404, usuario `admin`). La base nueva recibe los datos iniciales automáticamente; conservá los volúmenes y la clave al actualizar el Stack.

## Pruebas

```bash
cd src
php artisan test
```

Validación PI-28: **333 tests aprobados, 3411 aserciones**, con PHP 8.5.0 y PHPUnit 12.5.42. Los tests usan SQLite en memoria y no necesitan MySQL; requieren las dependencias de desarrollo (`composer install`, sin `--no-dev`). El plan de pruebas de Auth y Catálogo, con cada caso trazado a su test automático, está en [`docs/pruebas/plan-de-pruebas-auth-catalogo.md`](docs/pruebas/plan-de-pruebas-auth-catalogo.md).

## API REST

Con el stack levantado, la API responde en `http://localhost/api/v1` a través de HAProxy. Ejemplo:

```bash
curl http://localhost/api/v1/productos/coromandel
```

Guía con todos los endpoints, autenticación y ejemplos: [`docs/api/README.md`](docs/api/README.md). Especificación OpenAPI 3.1: [`docs/api/openapi.yaml`](docs/api/openapi.yaml).

## Documentación

Documentos de apoyo y antecedentes; el mapa de requisitos y tareas está en [Entregables del proyecto](#entregables-del-proyecto).

| Documento | Ubicación |
|---|---|
| Alcance vigente del proyecto | [Declaración de alcance](docs/alcance.md) · [PDF](Alcance_Proyecto_Mar_Fragancia.pdf) |
| Especificación de diseño | [`docs/superpowers/specs/2026-10-18-tienda-y-admin-design.md`](docs/superpowers/specs/2026-10-18-tienda-y-admin-design.md) |
| Maquetas aprobadas | [`docs/diseno/maquetas/diseno-final.html`](docs/diseno/maquetas/diseno-final.html) (abrir en el navegador) |
| Plan de implementación · Fase 1 | [`docs/superpowers/plans/2026-10-18-fase-1-tienda-publica.md`](docs/superpowers/plans/2026-10-18-fase-1-tienda-publica.md) |
| Plan de pruebas del panel | [Fase 2, reglas y límites](docs/pruebas/plan-de-pruebas-panel-admin.md) |
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
docker compose exec -T app-fpm php artisan docs:diccionario
```

El servicio `app-fpm` monta `./docs` en `/var/docs` para leer `docs/datos/descripciones.php` y escribir el Markdown del repositorio. Para incorporar cambios de imágenes o montajes, usar `docker compose up -d --build` y esperar ambos nodos UP en Stats. El comando no migra ni cambia datos; rechaza SQLite para no publicar tipos incorrectos. Al agregar una tabla o columna, completar sus descripciones, regenerar desde MySQL y correr los tests de documentación. Estos tests comparan nombres contra las migraciones SQLite, mientras que tipos, índices y acciones de FK del documento se extraen de MySQL.

## Equipo

| Rol | Integrante |
|---|---|
| Scrum Master / Project Manager | José Fernández |
| Backend & Architect Lead | Tobías Franco |
| DevOps & Cloud Engineer | Matías Amarilla · Jonathan Valdez |
| Frontend & QA Engineer | María Paz Gutiérrez |
