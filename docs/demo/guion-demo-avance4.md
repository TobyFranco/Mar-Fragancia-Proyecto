# PI-27 · Guion de demo en vivo del Avance 4

Entrega: **27/11/2026** · Duración prevista: **9 minutos**.

Objetivo: mostrar tráfico HTTP real, reparto entre las arquitecturas A y B, continuidad del carrito y caída/recuperación de un nodo en HAProxy Stats. El cierre usa el [benchmark existente](../benchmark/informe-benchmark.md); durante la presentación se genera tráfico moderado, sin repetir sus mediciones.

## Preparación antes de presentar

Desde la raíz del repositorio, con Docker Compose y el stack levantado. El generador requiere Bash 4+ y curl 7.84+ (`--rate` y lectura de cabeceras con `--write-out`); el ensayo usó curl 8.5.0.

```bash
docker compose ps
curl --fail --silent --show-error --include http://localhost/up
```

Comprobar que están levantados `db`, `app-fpm`, `web-nginx`, `apache-php` y `haproxy`, y que `/up` devuelve HTTP 200 con `X-Servido-Por`. Abrir también `/`, `/perfumes` y `/perfumes/coromandel`, ficha usada por el script.

Abrir en el navegador:

- Tienda: <http://localhost>.
- HAProxy Stats: <http://localhost:8404/>. Usuario **`admin`** y contraseña **`HAPROXY_STATS_PASSWORD`** del stack; el valor predeterminado de [Compose](../../compose.yaml) es `marfragancias-demo`. Usar la clave configurada si se cambió.
- Carrito: <http://localhost/carrito>, en la misma ventana y con las mismas cookies que la tienda.

Esperar a que las filas `nginx-php-fpm` (A) y `apache-mod-php` (B), dentro de `nodos_web`, estén **UP**. La configuración está en [haproxy.cfg](../../docker/haproxy/haproxy.cfg): `roundrobin`, health check `GET /up` cada 2 segundos, baja tras 3 fallos y recuperación tras 2 aciertos. Stats se refresca cada 5 segundos; se puede recargar manualmente.

Tener abiertas dos terminales: una para tráfico y otra para detener/recuperar el nodo. Matías Amarilla prepara Docker y Jonathan Valdez opera Stats y el failover. María Paz Gutiérrez deja un decant disponible en el carrito y anota nombre, tamaño y cantidad; no confirmar ni enviar un pedido. Si se muestra una cuenta iniciada, conservar la misma sesión del navegador.

## Presentación: quién dice y hace qué

| Tiempo | Responsable | Acción y explicación |
|---|---|---|
| 0:00–1:00 | José Fernández · Scrum Master | Presenta Mar Fragancias, el objetivo del Avance 4 y PI-27: disponibilidad y métricas observables sobre tráfico real. |
| 1:00–2:00 | Matías Amarilla · DevOps | Muestra `docker compose ps`, `/up` y el recorrido HAProxy → A (Nginx + PHP-FPM) / B (Apache + mod_php) → MySQL compartido. |
| 2:00–3:00 | Jonathan Valdez · DevOps | Abre Stats, señala los dos nodos UP, arranca el tráfico y muestra el aumento de contadores de ambos. |
| 3:00–4:00 | María Paz Gutiérrez · Frontend y QA; Tobías Franco · Backend | María Paz muestra el carrito con un producto. Tobías explica sesiones en MySQL, `APP_KEY` compartida y ausencia de afinidad a un nodo. |
| 4:00–6:00 | Jonathan Valdez; María Paz Gutiérrez | Jonathan detiene A y muestra DOWN en Stats. María Paz recarga catálogo y carrito: siguen respondiendo, con el mismo producto y cantidad. |
| 6:00–7:00 | Matías Amarilla | Recupera A, espera UP y muestra que vuelven a crecer los contadores de ambos nodos; María Paz vuelve a comprobar el carrito. |
| 7:00–8:30 | Tobías Franco; María Paz Gutiérrez | Presentan los números del benchmark y distinguen capacidad de respuesta, latencia y disponibilidad. |
| 8:30–9:00 | José Fernández | Recapitula lo observado, identifica las evidencias del ensayo y cierra la demostración. |

### 1. Generar y observar tráfico

En la primera terminal:

```bash
./docker/scripts/trafico-demo.sh 360
```

El [script](../../docker/scripts/trafico-demo.sh) alterna `/`, `/perfumes`, `/perfumes/coromandel` y `/up`, sin descargar assets secundarios. Envía hasta **2 solicitudes por segundo**, durante el tiempo indicado (120 segundos por defecto), con una solicitud en curso a la vez. Cada respuesta muestra código HTTP, nodo y conteos acumulados A/B. `000` identifica un fallo de transporte; `sin-nodo` indica ausencia de una cabecera reconocida. Ante respuestas inesperadas el script sigue contando y termina con código 1; Ctrl+C corta curl, imprime el resumen y sale con 130. Una solicitud en curso tiene un timeout de 5 segundos.

En Stats, observar las filas de servidores de `nodos_web`:

| Columna | Qué mostrar |
|---|---|
| Sessions | El total acumulado (`Total`, `stot` en CSV) crece en A y B. `Cur` puede permanecer en cero con esta carga ligera. |
| Bytes | Los contadores de entrada y salida (`bin`/`bout`) aumentan con las respuestas de la tienda. |
| Status | Ambos UP al inicio; A DOWN durante la caída y UP después de recuperar. |
| LastChk | `L7OK` / HTTP 200 cuando el health check responde; durante la caída puede mostrar un fallo de conexión. |

Comparar incrementos desde el comienzo de la demo. Roundrobin reparte solicitudes; otras pestañas y el tráfico del navegador pueden hacer que los contadores no sean idénticos.

Para guardar un snapshot CSV en otra terminal, ingresar la misma clave de Stats:

```bash
read -rsp 'Clave de HAProxy Stats: ' HAPROXY_STATS_PASSWORD
printf '\n'
curl --fail --silent --show-error --user "admin:$HAPROXY_STATS_PASSWORD" \
  'http://localhost:8404/;csv' > /tmp/stats-demo.csv
```

### 2. Caída y continuidad de la sesión

Para dejar tiempo a las explicaciones y a la comprobación del carrito, usar en la segunda terminal:

```bash
docker compose stop web-nginx
```

Mantener el generador activo. Esperar a que A aparezca **DOWN** en Stats; B debe seguir **UP**. Mostrar que los contadores de B siguen aumentando y que las respuestas se identifican como `apache-mod-php`:

```bash
curl --fail --silent --show-error --include http://localhost/up
```

En el navegador original, recargar `/perfumes` y `/carrito`. Comprobar nombre, tamaño y cantidad del producto anotado antes de la caída; si se había iniciado sesión, comprobar también la cuenta. Tobías relaciona el resultado con la base de sesiones y la clave compartidas. Registrar el resultado que se observe durante la presentación.

### 3. Recuperar el nodo

```bash
docker start "$(docker compose ps --all --quiet web-nginx)"
```

Esperar a que A vuelva a **UP** y que LastChk sea correcto. Comprobar que el generador vuelve a recibir respuestas de ambos nodos y que sus contadores de Sessions y Bytes aumentan. Recargar el carrito otra vez y finalizar el generador con **Ctrl+C** si todavía está activo.

Para un recorrido automatizado más corto, en lugar del stop/start manual ejecutar el [script de failover existente](../../docker/scripts/demo-failover.sh):

```bash
./docker/scripts/demo-failover.sh
```

Este script muestra balanceo, detiene A, espera su baja, exige respuestas 200 solo de B y restaura el contenedor existente de A. Su arranque directo evita reevaluar las dependencias del Compose actual sobre contenedores creados anteriormente. Ya incluye restauración ante interrupción; su ventana de caída es breve, por lo que conviene el recorrido manual para explicar el carrito con calma.

### 4. Cierre con los resultados del benchmark

Abrir el [informe de benchmark](../benchmark/informe-benchmark.md), medición del 09/11/2026, con 20 procesos PHP por arquitectura y concurrencia 50:

| Medición | A: Nginx + PHP-FPM | B: Apache + mod_php |
|---|---|---|
| Catálogo `/perfumes` | 87,33 RPS; p95 713 ms | 80,87 RPS; p95 794 ms |
| Ficha `/perfumes/coromandel` | 110,52 RPS; p95 563 ms | 102,84 RPS; p95 605 ms |

El informe registra 48.000 solicitudes directas sin errores, reparto 1000/1000 por HAProxy y 924 solicitudes sin errores durante el failover, con p95 de 3193 ms frente a 1194 ms de la corrida normal. La demo muestra disponibilidad; esos números proceden del benchmark guardado. Limitaciones: una corrida por punto, una máquina compartida, sin TLS ni escenarios autenticados ni medición en Portainer; las cargas normal y de failover tienen tamaños distintos.

## Plan B y restauración

Si una ruta falla, revisar la salida HTTP del generador y los logs. Matías restaura los servicios y Jonathan comprueba el estado:

```bash
for servicio in db app-fpm web-nginx apache-php haproxy; do
  docker start "$(docker compose ps --all --quiet "$servicio")"
done
docker compose ps
docker compose logs --tail=60 haproxy web-nginx app-fpm apache-php
curl --fail --silent --show-error --include http://localhost/up
```

Esperar ambos nodos **UP** en Stats. Si la cuenta o el carrito se pierden, registrar el fallo y revisar `SESSION_DRIVER=database`, base compartida y `APP_KEY` común; conservar los datos del stack. Si se interrumpe el recorrido manual, ejecutar siempre `docker start "$(docker compose ps --all --quiet web-nginx)"`.

Si Stats rechaza el acceso, confirmar usuario `admin`, clave del stack y puerto 8404. Si no se recupera la demo en el tiempo disponible, José anuncia el cambio a evidencia del ensayo y Jonathan abre sus CSV y logs enlazados abajo. Para la comparación histórica, mostrar [resultados.csv](../benchmark/resultados.csv), [Stats normal](../benchmark/resultados/stats-normal.csv), [Stats failover](../benchmark/resultados/stats-failover.csv) y los [gráficos del informe](../benchmark/informe-benchmark.md#gráficos). Identificar estas salidas como evidencia guardada y retomar la demo en vivo solo cuando ambos nodos estén UP.

## Registro del ensayo real

**12/11/2026, de 23:19:38 a 23:20:27, hora de Asunción (UTC−03).** Se ejecutó `trafico-demo.sh 30` contra `http://localhost` y después `demo-failover.sh`, sobre el stack que ya estaba levantado.

- **Tráfico:** 60 respuestas HTTP 200, 30 de A y 30 de B, sin respuestas inesperadas; salida 0. El conteo se mostró durante la ejecución.
- **Stats CSV:** ambos nodos recibieron tráfico; los snapshots antes/después del generador dan los siguientes incrementos:

| Nodo | Sessions Total (`stot`) | Δ sesiones | Δ bytes entrada | Δ bytes salida | Estado tras el tráfico |
|---|---|---|---|---|---|
| `nginx-php-fpm` | 42 → 72 | +30 | +2400 | +2879595 | UP / L7OK |
| `apache-mod-php` | 49 → 79 | +30 | +2535 | +2348010 | UP / L7OK |

- **Failover:** se observó A `DOWN` con `LastChk=L4TOUT`, mientras B permanecía `UP/L7OK`. El script comprobó seis respuestas HTTP 200 consecutivas de B con A detenido y terminó con salida 0 tras recuperar A.
- **Sesión/carrito:** se agregó Coromandel de 5 ml, cantidad 1, usando un cliente HTTP con las mismas cookies. El carrito conservó ese producto y cantidad antes de la caída, durante la caída servido por B y después de recuperar, con respuestas de A y B. Todas esas consultas devolvieron HTTP 200. La presentación incluye la misma comprobación en el navegador.
- **Estado final:** A y B `UP/L7OK`, con `stot` 82 y 98 respectivamente después del failover y de las comprobaciones del carrito.

Evidencia guardada: [salida del tráfico](ensayo-2026-11-12/trafico-30s.txt), [salida del failover](ensayo-2026-11-12/failover.txt), [Stats antes](ensayo-2026-11-12/stats-antes-trafico.csv), [Stats después del tráfico](ensayo-2026-11-12/stats-despues-trafico.csv), [Stats durante la caída](ensayo-2026-11-12/stats-durante-caida.csv), [Stats final](ensayo-2026-11-12/stats-final.csv) y [resumen con comprobación del carrito](ensayo-2026-11-12/resumen.json).

El primer intento detectó salida almacenada en búfer por curl y un fallo de `docker compose start` porque el contenedor PHP existente carecía del healthcheck del Compose actual. Se corrigió la salida del generador y el script de failover pasó a iniciar el contenedor existente por su ID; se agregó una prueba de regresión. Se restauró Nginx y se repitió el ensayo completo con los resultados anteriores. Los [logs y CSV del primer intento](ensayo-2026-11-12/primer-intento) se conservan para registrar lo ocurrido.
