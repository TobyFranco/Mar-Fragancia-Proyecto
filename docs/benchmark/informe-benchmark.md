# Benchmark de arquitecturas · 9 de noviembre de 2026

Comparación de rendimiento entre la **Arquitectura A** (`nginx-php-fpm`: Nginx + PHP-FPM) y la **Arquitectura B** (`apache-mod-php`: Apache prefork + mod_php) de la tienda Mar Fragancias. Además se mide el balanceo y el failover con HAProxy.

## Resumen

Con la misma cantidad de procesos PHP en las dos arquitecturas (20 cada una), **Nginx + PHP-FPM rinde algo mejor en las páginas dinámicas**. Los números son con 50 solicitudes concurrentes:

| Página | A: Nginx + PHP-FPM | B: Apache + mod_php | Diferencia a favor de A |
|---|---:|---:|---:|
| `/up` | 456,68 RPS · p95 161 ms | 434,43 RPS · p95 158 ms | +5 % RPS, p95 similar |
| Catálogo `/perfumes` | 87,33 RPS · p95 713 ms | 80,87 RPS · p95 794 ms | +8 % RPS, −10 % p95 |
| Ficha `/perfumes/coromandel` | 110,52 RPS · p95 563 ms | 102,84 RPS · p95 605 ms | +7 % RPS, −7 % p95 |

- En archivos estáticos, las dos sirven unas 8.300 a 8.900 solicitudes por segundo. No hay diferencia concluyente.
- No hubo errores en ninguna de las 48.000 solicitudes directas.
- A través de HAProxy, la carga se repartió exactamente 1000 / 1000 entre las dos arquitecturas.
- Al apagar Nginx durante la carga, HAProxy siguió respondiendo sin errores. El costo fue latencia: el p95 subió de 1194 a 3193 ms durante la caída.

La ventaja de A es chica (5 a 8 %) y sale de una sola corrida por punto, así que hay que leerla como tendencia, no como diferencia definitiva.

## Entorno

**Hardware y software:**

- Máquina: AMD Ryzen 5 5500 (6 núcleos, 12 hilos) y unos 15,5 GiB de RAM, todo en una sola máquina.
- Contenedores: Docker 29.1.3 sobre Linux 7.0.0-38-generic.
- PHP: 8.4.27, con OPcache habilitado y comprobado en el SAPI web de las dos arquitecturas.
- Servidores: Nginx 1.31.8 + PHP-FPM, Apache 2.4.69 + mod_php, HAProxy 2.9.15 y MySQL 8.0.

Las versiones exactas y los identificadores de imágenes están en [versiones](versiones.txt), [imágenes](imagenes.json), [host](host.txt) y [entorno](entorno.json).

**Configuración igualada para que la comparación sea justa.** Las dos arquitecturas tienen el mismo número fijo de procesos PHP:

| | A: PHP-FPM | B: Apache prefork |
|---|---|---|
| Archivo | `docker/php/procesos.conf` | `docker/apache/mpm_prefork.conf` |
| Configuración | `pm = static`, `pm.max_children = 20` | `StartServers`, `MinSpareServers`, `MaxSpareServers`, `ServerLimit` y `MaxRequestWorkers` en 20 |

El valor 20 sale de los 12 hilos de CPU más un margen para el tiempo en que cada proceso espera a MySQL. Un test (`ProcesosPhpTest`) verifica que los dos archivos coincidan.

No se asignaron cuotas de CPU ni de RAM por arquitectura: las dos comparten la máquina.

## Primera corrida, con la configuración de fábrica

La primera medición se hizo con los valores por defecto de cada imagen:

- PHP-FPM: `pm = dynamic` con `pm.max_children = 5`.
- Apache: `MaxRequestWorkers = 150`.

Con 5 procesos contra 150, la comparación no era justa. Nginx se quedaba en unos 57 RPS en el catálogo aunque subiera la concurrencia, porque las solicitudes esperaban en la cola de FPM. Apache conseguía más RPS, pero con una latencia de cola mucho peor.

| Página (concurrencia 50) | A de fábrica | A igualada | B de fábrica | B igualada |
|---|---:|---:|---:|---:|
| `/up` | 341,4 RPS · p95 156 ms | 456,68 RPS · p95 161 ms | 435,27 RPS · p95 171 ms | 434,43 RPS · p95 158 ms |
| `/perfumes` | 57,59 RPS · p95 913 ms | 87,33 RPS · p95 713 ms | 80,82 RPS · p95 1273 ms | 80,87 RPS · p95 794 ms |
| `/perfumes/coromandel` | 72,4 RPS · p95 716 ms | 110,52 RPS · p95 563 ms | 103,26 RPS · p95 1077 ms | 102,84 RPS · p95 605 ms |

Al igualar los procesos:

- **A** subió entre 34 y 53 % de RPS.
- **B** mantuvo el throughput pero bajó mucho su p95, porque 20 procesos compiten menos por la CPU que 150.

La configuración de los procesos pesa más que la elección del servidor web. Los datos de esa corrida se conservan en [resultados-fabrica.csv](resultados-fabrica.csv) y [resultados-fabrica/](resultados-fabrica/), con los gráficos con prefijo `fabrica-`.

## Metodología y reproducción

Ejecutar desde la raíz:

```bash
./docker/scripts/benchmark.sh
```

- **Requisitos:** Bash, Python 3 y Docker. No instala generadores de carga en el host.
- **Variables:** `BENCHMARK_REQUESTS` ajusta las solicitudes por punto (mínimo 100, 2000 por defecto) y `BENCHMARK_OUTPUT` cambia la carpeta de salida.
- **Herramienta:** ApacheBench 2.3, de la imagen fija `httpd:2.4.65-alpine`. Corre como servicio de perfil `benchmark` dentro de la red Compose y le pega directo a cada arquitectura, porque los nodos no publican puertos.
- **Parámetros:** `-l -r -s 60 -n 2000`, concurrencias 10, 50 y 100, sin keepalive. `-l` evita contar como fallo las diferencias de longitud del HTML dinámico, como los tokens de sesión. No oculta errores de conexión ni respuestas que no sean 2xx. [Referencia de ApacheBench](https://httpd.apache.org/docs/2.4/programs/ab.html).
- **Preparación:** Laravel en modo producción (`APP_ENV=production`, `APP_DEBUG=false`) con `config:cache`, `route:cache` y `view:cache`. Antes de cada endpoint se hacen 30 solicitudes de calentamiento, que no entran en las tablas. No se usan cookies ni login.
- **Orden:** primero Nginx y después Apache, siempre en el orden `/up`, catálogo, ficha y CSS.
- **Métricas:** RPS de ab, latencia media de la fila Total, percentiles 50/95/99 en milisegundos enteros, y errores = Failed requests + Non-2xx responses.
- **Restauración:** al terminar, o ante cualquier error, el script vuelve a encender `web-nginx`, limpia las cachés de configuración, rutas y vistas, y exige las dos arquitecturas UP en Stats. No toca `.env`, `APP_KEY` ni datos de productos o pedidos.

La salida cruda de ab, incluidos los calentamientos, está en [resultados/](resultados/), y el consolidado en [resultados.csv](resultados.csv).

## Resultados directos

### /up

Respuesta mínima de Laravel: mide el costo de cada pila sin base de datos.

| Arquitectura | Concurrencia | RPS | Media ms | p50 ms | p95 ms | p99 ms | Errores / solicitudes |
|---|---:|---:|---:|---:|---:|---:|---:|
| nginx-php-fpm | 10 | 431.88 | 23.0 | 23.0 | 30.0 | 38.0 | 0 / 2000 |
| nginx-php-fpm | 50 | 456.68 | 108.0 | 100.0 | 161.0 | 185.0 | 0 / 2000 |
| nginx-php-fpm | 100 | 457.27 | 213.0 | 211.0 | 262.0 | 283.0 | 0 / 2000 |
| apache-mod-php | 10 | 419.63 | 24.0 | 24.0 | 30.0 | 34.0 | 0 / 2000 |
| apache-mod-php | 50 | 434.43 | 114.0 | 109.0 | 158.0 | 179.0 | 0 / 2000 |
| apache-mod-php | 100 | 434.0 | 225.0 | 224.0 | 273.0 | 290.0 | 0 / 2000 |

### /perfumes

Catálogo: consultas a MySQL y render de Blade con 96 productos.

| Arquitectura | Concurrencia | RPS | Media ms | p50 ms | p95 ms | p99 ms | Errores / solicitudes |
|---|---:|---:|---:|---:|---:|---:|---:|
| nginx-php-fpm | 10 | 83.87 | 119.0 | 119.0 | 131.0 | 140.0 | 0 / 2000 |
| nginx-php-fpm | 50 | 87.33 | 565.0 | 553.0 | 713.0 | 796.0 | 0 / 2000 |
| nginx-php-fpm | 100 | 86.28 | 1130.0 | 1132.0 | 1309.0 | 1396.0 | 0 / 2000 |
| apache-mod-php | 10 | 78.36 | 127.0 | 127.0 | 141.0 | 149.0 | 0 / 2000 |
| apache-mod-php | 50 | 80.87 | 611.0 | 595.0 | 794.0 | 875.0 | 0 / 2000 |
| apache-mod-php | 100 | 81.31 | 1199.0 | 1201.0 | 1385.0 | 1473.0 | 0 / 2000 |

### /perfumes/coromandel

Ficha de un producto, con decants y recomendaciones por notas.

| Arquitectura | Concurrencia | RPS | Media ms | p50 ms | p95 ms | p99 ms | Errores / solicitudes |
|---|---:|---:|---:|---:|---:|---:|---:|
| nginx-php-fpm | 10 | 105.71 | 94.0 | 94.0 | 105.0 | 114.0 | 0 / 2000 |
| nginx-php-fpm | 50 | 110.52 | 447.0 | 440.0 | 563.0 | 618.0 | 0 / 2000 |
| nginx-php-fpm | 100 | 110.38 | 883.0 | 889.0 | 1017.0 | 1087.0 | 0 / 2000 |
| apache-mod-php | 10 | 99.5 | 100.0 | 100.0 | 111.0 | 118.0 | 0 / 2000 |
| apache-mod-php | 50 | 102.84 | 480.0 | 469.0 | 605.0 | 656.0 | 0 / 2000 |
| apache-mod-php | 100 | 103.19 | 945.0 | 950.0 | 1087.0 | 1152.0 | 0 / 2000 |

### /build/assets/app-DUsGpcYZ.css

Archivo estático servido directamente por el servidor web, sin PHP.

| Arquitectura | Concurrencia | RPS | Media ms | p50 ms | p95 ms | p99 ms | Errores / solicitudes |
|---|---:|---:|---:|---:|---:|---:|---:|
| nginx-php-fpm | 10 | 8543.39 | 1.0 | 1.0 | 1.0 | 1.0 | 0 / 2000 |
| nginx-php-fpm | 50 | 8416.87 | 6.0 | 6.0 | 6.0 | 7.0 | 0 / 2000 |
| nginx-php-fpm | 100 | 8875.44 | 11.0 | 11.0 | 12.0 | 13.0 | 0 / 2000 |
| apache-mod-php | 10 | 8399.87 | 1.0 | 1.0 | 1.0 | 1.0 | 0 / 2000 |
| apache-mod-php | 50 | 8486.77 | 6.0 | 6.0 | 7.0 | 8.0 | 0 / 2000 |
| apache-mod-php | 100 | 8307.51 | 12.0 | 12.0 | 13.0 | 14.0 | 0 / 2000 |

## Gráficos

Desde la raíz, con Python 3 y matplotlib disponibles, ejecutar `python3 -I docs/benchmark/graficos.py`. La ruta de un venv temporal de la corrida original no es un requisito de reproducción. Los gráficos usan la paleta salvia de la marca y escala lineal desde cero.

![RPS /up](up-rps.png)

![p95 /up](up-p95_ms.png)

![RPS catálogo](catalogo-rps.png)

![p95 catálogo](catalogo-p95_ms.png)

![RPS ficha](ficha-rps.png)

![p95 ficha](ficha-p95_ms.png)

## HAProxy y caída bajo carga

| Escenario | Concurrencia | RPS | Media ms | p50 ms | p95 ms | p99 ms | Errores / solicitudes |
|---|---:|---:|---:|---:|---:|---:|---:|
| haproxy | 50 | 78.18 | 633.0 | 588.0 | 1194.0 | 1403.0 | 0 / 2000 |
| haproxy-failover | 50 | 61.53 | 785.0 | 584.0 | 3193.0 | 4881.0 | 0 / 924 |

**Corrida normal.** 2000 solicitudes a `/perfumes` a través de HAProxy con `roundrobin`.

Da menos RPS que cualquiera de las dos arquitecturas por separado. Hay dos motivos:

- Las dos arquitecturas y MySQL comparten la misma CPU, así que balancear no agrega capacidad. Al contrario, 40 procesos PHP compiten por 12 hilos.
- HAProxy agrega un salto de red.

En una sola máquina el balanceador aporta **disponibilidad, no throughput**. Con cada arquitectura en su propio servidor, la capacidad sí se sumaría.

**Failover.** La carga corre con un límite de 15 segundos (`-t 15 -n 1000000`). Cuando Stats muestra que el tráfico ya está activo, se detiene `web-nginx` durante 3 segundos y se vuelve a encender.

- **Resultado:** 924 solicitudes, 0 errores.
- **Costo:** mientras A estaba caída, el p95 subió de 1194 a 3193 ms y el p99 de 1403 a 4881 ms.
- **Por qué no hubo errores:** HAProxy (`check inter 2s fall 3 rise 2`, `option redispatch`, `retries 3`) reintentó las conexiones fallidas contra Apache hasta marcar a Nginx como caído.

Como esta corrida no tiene el mismo número de solicitudes que la normal, no se compara con ella como A/B.

### Tráfico real en Stats

Las diferencias de contadores por servidor salen de los snapshots de `http://haproxy:8404/;csv` tomados antes y después de cada fase. El dashboard en vivo está en `http://localhost:8404/`.

| Fase | Servidor | Δ sesiones | Δ bytes entrada | Δ bytes salida | Estado al final |
|---|---|---:|---:|---:|---|
| Normal | nginx-php-fpm | 1000 | 86000 | 181874000 | UP |
| Normal | apache-mod-php | 1000 | 86000 | 181904000 | UP |
| Failover | nginx-php-fpm | 514 | 37926 | 79529625 | UP |
| Failover | apache-mod-php | 518 | 42054 | 88817056 | UP |

En el failover, las sesiones contadas pueden superar las solicitudes completadas por los reintentos de conexión.

## Conclusiones

1. **Con recursos iguales, Nginx + PHP-FPM es entre 5 y 8 % más rápido que Apache + mod_php** en las páginas dinámicas, y tiene igual o menor latencia p95. En archivos estáticos no hay diferencia concluyente.
2. **La configuración de procesos pesa más que el servidor web.** Con los valores de fábrica, A parecía 30 % más lenta. Al darle los mismos 20 procesos que a B, pasó adelante.
3. **El cuello de botella es PHP + MySQL, no el servidor web.** Pasar de 10 a 100 usuarios concurrentes casi no cambia el RPS, que se mantiene en unos 85 en el catálogo, y la latencia crece en proporción. Las solicitudes esperan en cola.
4. **HAProxy cumple su función de alta disponibilidad.** Reparte la carga en partes iguales y, ante la caída de una arquitectura, mantiene la tienda sin errores, a cambio de unos segundos de latencia mayor.

## Limitaciones

- **Una sola corrida por punto**, en orden fijo, sin intervalos de confianza. Diferencias del 5 al 8 % pueden estar dentro del ruido.
- **Una sola máquina** para las dos arquitecturas, MySQL y el generador de carga. El generador compite por CPU con lo que mide.
- **Archivos estáticos:** 2000 solicitudes duran una fracción de segundo, así que esas filas son muy sensibles al ruido.
- **Sin escenarios reales de uso:** no hay TLS, red externa, usuarios autenticados ni prueba de continuidad del carrito.
- **Failover acotado:** es una caída controlada de 3 segundos, no una prueba prolongada de disponibilidad.
- **Sin Portainer:** no incluye el despliegue en Portainer ni garantiza rendimiento de producción.
