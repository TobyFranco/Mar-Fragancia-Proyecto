# PI-26 · Sprint Review y retrospectiva final

Avance 4 de Programación IV · Entrega prevista: **27/11/2026**.

**Corte de evidencia: 12/11/2026**, sobre `main` en `b6d2c2cb385ed1a468ac29e6ae9d63c54c7fd71c`. Este documento prepara el cierre metodológico con el estado comprobable del repositorio; no constituye un acta de una ceremonia celebrada ni acredita la aprobación académica de los avances.

## 1. Resumen del proyecto y metodología

Mar Fragancias es una tienda de decants de perfumes de Asunción, Paraguay. Centraliza catálogo, stock y pedidos; el checkout guarda el pedido pendiente y prepara su envío por WhatsApp. Incluye tienda pública, área de cliente, panel administrativo y API REST v1 con autenticación Sanctum. La solución usa Laravel, MySQL y Docker, con HAProxy como frontal de dos arquitecturas: A, Nginx + PHP-FPM; B, Apache + mod_php. Alcance y funcionalidades: [declaración de alcance](../../Alcance_Proyecto_Mar_Fragancia.pdf) y [README](../../README.md).

La metodología indicada por el equipo es **Scrum**, gestionada en [Jira, proyecto/tablero PI](https://marfragancias.atlassian.net). El calendario académico organiza entregas por avances; estas fechas no se toman como fechas de ceremonias ni como límites de sprints.

| Entrega | Fecha límite de 2026 |
|---|---|
| Avance 1 | 28/08/2026 |
| Avance 2 | 25/09/2026 |
| Avance 3 | 30/10/2026 |
| Avance 4 | 27/11/2026 |

Fuente de requisitos y calendario: `PROYECTO INTEGRADOR- Programación IV.pdf`, leído mediante `pdftotext -layout` desde `/home/paz/Descargas/PROYECTO INTEGRADOR- Programación IV.pdf`. El PDF exige Product Backlog priorizado con criterios de aceptación, Sprint Backlog y Planning de sprints de 2 a 3 semanas, y actas de Daily, Review y Retrospective. Al haberse elegido Scrum, las métricas de flujo y límites WIP de la alternativa Kanban no se presentan como entregables propios. La asignación de arquitecturas figura pendiente de definir en el PDF; la implementación A/B aquí descrita procede del repositorio.

### Equipo actual

Según la sección Equipo del [README](../../README.md):

| Integrante | Rol |
|---|---|
| José Fernández | Scrum Master / Project Manager |
| Tobías Franco | Backend & Architect Lead |
| Matías Amarilla | DevOps & Cloud Engineer |
| Jonathan Valdez | DevOps & Cloud Engineer |
| María Paz Gutiérrez | Frontend & QA Engineer |

Esta nómina no acredita asistencia a ninguna ceremonia.

### Estado de Jira y alcance de la trazabilidad

Los estados siguientes fueron proporcionados por el equipo para este cierre; no se consultó ni modificó Jira durante su elaboración. El historial solo explicita la correspondencia individual de PI-12 y PI-13. En el resto se conservan los rangos informados, sin inventar qué número corresponde a cada tema.

| Tareas | Estado informado | Alcance |
|---|---|---|
| PI-1 a PI-16 | Finalizadas | Avances 1 y 2: alcance, MER/DER, Docker, diccionario, migraciones de roles y usuarios, login Sanctum, middleware de rol, CRUD de marcas, categorías y productos, seeders, entorno compartido, CI básico, wireframes, plan de pruebas, backlog, seguimiento y documentación del Avance 2. |
| [PI-12](https://marfragancias.atlassian.net/browse/PI-12) y [PI-13](https://marfragancias.atlassian.net/browse/PI-13) | Finalizadas; incluidas en el rango anterior | Wireframes y plan de pruebas, respectivamente; correspondencia explícita en la [especificación](../superpowers/specs/2026-10-18-tienda-y-admin-design.md) y el PR #1. |
| PI-17 a PI-25 | Finalizadas | HAProxy, arquitecturas A y B, sesiones en MySQL, failover, benchmark, API REST, Portainer, área de cliente y corrección de tests. |
| [PI-26](https://marfragancias.atlassian.net/browse/PI-26) | Pendiente de cierre | Este documento y su revisión por el equipo. |
| [PI-27](https://marfragancias.atlassian.net/browse/PI-27) | Pendiente | Demo de HAProxy Stats con tráfico real. |
| [PI-28](https://marfragancias.atlassian.net/browse/PI-28) | Pendiente | Documentación completa. |

> **[Completar por el equipo]: Adjuntar una exportación o captura del tablero PI y confirmar la correspondencia individual de las tareas PI-1 a PI-25 con sus entregables. Incorporar el Product Backlog con prioridades y criterios de aceptación y los Sprint Backlogs con sus fechas y objetivos reales.**

## 2. Sprint Review final

### Matriz de entregables del PDF

La tabla reproduce los entregables del calendario del PDF. «Implementado» indica evidencia técnica disponible al corte; no prueba que se haya presentado en la fecha límite. Las fechas de integración de noviembre son posteriores a los primeros tres avances y se mantienen explícitas en el historial de PR.

| Avance | Entregable requerido | Estado al corte | Evidencia del repositorio o PR | Tarea de Jira |
|---|---|---|---|---|
| 1 | Definición del alcance y caso PyME | Documentado | [Alcance del proyecto](../../Alcance_Proyecto_Mar_Fragancia.pdf), [README](../../README.md) | PI-1 a PI-16; identificación individual por confirmar |
| 1 | Configuración de metodología: tablero y Product Backlog | Finalizado según estado informado; falta evidencia exportada del backlog | Referencia al tablero PI en este documento; [especificación con referencias Jira](../superpowers/specs/2026-10-18-tienda-y-admin-design.md). No sustituye un backlog priorizado con criterios de aceptación | PI-1 a PI-16; identificación individual por confirmar |
| 1 | Diagrama MER/DER y diccionario de datos | Documentado | [DER conceptual y lógico](../datos/der.md), [diagrama SVG](../datos/der-logico.svg), [diccionario](../datos/diccionario-de-datos.md); PR #6 | PI-1 a PI-16; identificación individual por confirmar |
| 1 | Entorno base Docker: Laravel + MySQL local | Implementado | [Compose](../../compose.yaml), [override de desarrollo](../../compose.override.yaml), [setup](../../setup.sh), [dependencias Laravel](../../src/composer.json); PR #3 y #7 para el stack actual | PI-1 a PI-16; identificación individual por confirmar |
| 2 | Reporte de sprints / flujo Kanban acumulado | Finalizado según estado informado; reporte histórico de sprints por adjuntar | [Planes de trabajo](../superpowers/plans/2026-10-18-fase-1-tienda-publica.md) y Git permiten seguir implementación, pero no acreditan sprints ni ceremonias | PI-1 a PI-16; identificación individual por confirmar |
| 2 | Módulo CRUD principal funcional con reglas de negocio | Implementado y cubierto por pruebas | [Controladores administrativos](../../src/app/Http/Controllers/Admin), [tests de productos](../../src/tests/Feature/Admin/ProductosTest.php), [ciclo de pedidos](../../src/tests/Feature/Admin/CicloPedidoTest.php), [plan de pruebas del panel](../pruebas/plan-de-pruebas-panel-admin.md) | PI-1 a PI-16; identificación individual por confirmar |
| 2 | Manejo de sesiones centralizadas: Redis/BD | Implementado con BD centralizada | [Configuración de sesión](../../src/config/session.php), [migración sessions](../../src/database/migrations/2026_10_19_000001_create_sessions_table.php), [test de sesiones](../../src/tests/Feature/Infra/SesionesEnBaseDeDatosTest.php), [Compose](../../compose.yaml); PR #3 | PI-17 a PI-25; identificación individual por confirmar |
| 2 | Despliegue inicial del stack en Portainer | Stack preparado; tarea finalizada según estado informado; falta constancia del despliegue real | [Compose](../../compose.yaml), [entrypoint](../../docker/php/entrypoint.sh), [instrucciones Portainer](../../README.md#portainer), [test](../../src/tests/Feature/Infraestructura/PortainerTest.php); PR #7 | PI-17 a PI-25; identificación individual por confirmar |
| 3 | Evidencia de ceremonias ágiles y actualización del tablero | Seguimiento finalizado según estado informado; actas y capturas por adjuntar | Historial Git y PR listados abajo; plantilla de ceremonias en la sección 4. Los commits no acreditan ceremonias | PI-1 a PI-16 (seguimiento), PI-26 (registro de cierre) |
| 3 | Configuración e integración de HAProxy | Implementado | [haproxy.cfg](../../docker/haproxy/haproxy.cfg): roundrobin, health checks sobre `/up`, reintentos y Stats en 8404; [tests de balanceo](../../src/tests/Feature/Infraestructura/BalanceoTest.php); PR #3 | PI-17 a PI-25; identificación individual por confirmar |
| 3 | Despliegue de las arquitecturas A y B activas | Configuradas y medidas en el benchmark; actividad actual no comprobada en esta revisión | [Nginx](../../docker/nginx), [PHP-FPM](../../docker/php), [Apache](../../docker/apache), [Compose](../../compose.yaml), [informe de ejecución](../benchmark/informe-benchmark.md); PR #3 y #4 | PI-17 a PI-25; identificación individual por confirmar |
| 3 | Demostración de failover en vivo | Script y corrida documentados; falta constancia de presentación en vivo | [Demo reproducible](../../docker/scripts/demo-failover.sh), [resultado de failover](../benchmark/resultados/haproxy-failover.txt), [informe](../benchmark/informe-benchmark.md); PR #3 y #4 | PI-17 a PI-25; identificación individual por confirmar |
| 4 | Cierre metodológico: Review/Retrospective final o métricas de flujo | Documento elaborado; revisión y aportes del equipo pendientes | Este documento y plantilla de actas de la sección 4 | PI-26 |
| 4 | Benchmarking: RPS y latencia A vs B | Documentado con resultados y limitaciones | [Informe](../benchmark/informe-benchmark.md), [CSV](../benchmark/resultados.csv), [script](../../docker/scripts/benchmark.sh); PR #4 | PI-17 a PI-25; identificación individual por confirmar |
| 4 | HAProxy Stats en 8404 con tráfico real | Pendiente de demo PI-27; existen snapshots de una corrida anterior | [Configuración Stats](../../docker/haproxy/haproxy.cfg), [CSV previo](../benchmark/resultados/stats-antes.csv), [CSV normal](../benchmark/resultados/stats-normal.csv), [CSV failover](../benchmark/resultados/stats-failover.csv), [informe](../benchmark/informe-benchmark.md) | PI-27 |
| 4 | Repositorio Git y documentación técnica/metodológica completa | Repositorio y documentación parcial disponibles; cierre pendiente | [Índice del README](../../README.md#documentación), [documentación API](../api/README.md), datos, pruebas, benchmark y este documento | PI-28; PI-26 para el componente metodológico |

Otros requisitos del cuerpo del PDF cuentan con evidencia: la [evaluación de normalización](../datos/normalizacion.md) documenta 3FN y explicita dos excepciones deliberadas, `detalle_pedidos.subtotal` y `pedidos.total` (PR #6); la [API REST](../api/README.md) y su [OpenAPI](../api/openapi.yaml) describen endpoints y autenticación Sanctum (PR #5); los roles actuales se registran en la sección 1 y en el README (PR #9). Las [maquetas](../diseno/maquetas/diseno-final.html) y el [plan de pruebas Auth/Catálogo](../pruebas/plan-de-pruebas-auth-catalogo.md) trazan PI-12 y PI-13. El estado informado de CI básico no se toma como prueba de una ejecución de CI: el resultado registrado abajo corresponde a una ejecución local.

> **[Completar por el equipo]: Adjuntar reportes de sprints, actas y capturas históricas del tablero; constancia del despliegue en Portainer y de la demostración de failover en vivo; constancias de presentación y aprobación de cada avance. No deducir estas fechas a partir de los commits.**

### PR integrados y fechas comprobables

Fuente: `git log main --first-parent --format='%h %cs %s' --grep='(#[0-9]'`. Las fechas son las de los commits de integración que conserva Git, no las de inicio de tareas ni de reuniones. Los enlaces se construyen con el remoto `origin` del repositorio.

| PR | Fecha en Git | Commit | Cambio integrado |
|---|---|---|---|
| [#3](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/3) | 09/11/2026 | `1ad65f7` | HAProxy con balanceo entre Nginx + PHP-FPM y Apache + mod_php |
| [#4](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/4) | 09/11/2026 | `3010df1` | Benchmarking A vs B |
| [#5](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/5) | 09/11/2026 | `961a850` | API REST v1 con Sanctum |
| [#6](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/6) | 10/11/2026 | `532a2f7` | DER, diccionario y justificación de 3FN |
| [#7](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/7) | 11/11/2026 | `18db4eb` | Área de cliente y stack preparado para Portainer CE |
| [#8](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/8) | 12/11/2026 | `d7da1b7` | Corrección de tests del seeder y Portainer |
| [#9](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/9) | 12/11/2026 | `b6d2c2c` | Actualización del equipo en el README |

Son **7 PR integrados en el tramo #3–#9**. En toda la línea principal consultada hay **8 referencias de integración de PR**: las siete anteriores y [#1](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/1), commit `29ecbaf` del 19/10/2026. La secuencia numérica no acredita un PR #2 integrado. Este conteo procede de Git; no es una consulta del estado remoto de GitHub.

### Métricas verificadas

El 12/11/2026 se ejecutó desde la raíz:

```bash
cd src
php artisan test
```

Resultado: **331 tests aprobados de 331, 3405 aserciones, sin fallos**, duración informada de 20002 ms. El entorno de tests está definido en [phpunit.xml](../../src/phpunit.xml); usa SQLite en memoria. Este resultado no sustituye pruebas de infraestructura desplegada ni mide cobertura porcentual de código.

Resultados históricos del [informe de benchmark del 09/11/2026](../benchmark/informe-benchmark.md), con 20 procesos PHP por arquitectura y concurrencia 50:

| Endpoint / escenario | A: Nginx + PHP-FPM | B: Apache + mod_php |
|---|---|---|
| `/up` | 456,68 RPS; p95 161 ms | 434,43 RPS; p95 158 ms |
| `/perfumes` | 87,33 RPS; p95 713 ms | 80,87 RPS; p95 794 ms |
| `/perfumes/coromandel` | 110,52 RPS; p95 563 ms | 102,84 RPS; p95 605 ms |

El informe registra **48.000 solicitudes directas sin errores** y reparto de **1000/1000** en la corrida normal por HAProxy. En esa corrida HAProxy obtuvo **78,18 RPS** y **p95 1194 ms** sobre 2000 solicitudes. Durante el failover controlado completó **924 solicitudes sin errores**, con **p95 3193 ms**. Las corridas normal y de failover tienen tamaños distintos y no constituyen una comparación A/B equivalente.

La ventaja observada de A del 5 al 8 % es una tendencia de esa medición: hubo una sola corrida por punto, en una máquina compartida, sin TLS ni usuarios autenticados. No se verificó continuidad real del carrito ni rendimiento en Portainer. Estos resultados se transcriben de los artefactos guardados; no se repitió el benchmark para elaborar este documento.

### Pendientes para la entrega

- **PI-26:** revisión del documento por el equipo, opiniones y acuerdos reales de retrospectiva y actas correspondientes.
- **PI-27:** demo de Stats en 8404 con tráfico real y evidencia de contadores y estados de los nodos. Los snapshots históricos no cierran esta tarea.
- **PI-28:** revisar y completar el índice técnico/metodológico, la trazabilidad Jira y las constancias pendientes de la matriz.

> **[Completar por el equipo]: Registrar la aceptación o los ajustes de esta Review, con fecha real, asistentes y acuerdos. Confirmar el estado de PI-26, PI-27 y PI-28 en Jira después de aportar las evidencias.**

## 3. Retrospectiva

Los puntos siguientes son observaciones de artefactos y del historial; las acciones son propuestas para acordar, no compromisos atribuidos al equipo.

### Qué salió bien: hechos observables

- La suite completa pasa con 331 tests. El [PR #8](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/8) corrigió dos supuestos de tests: el correo de ejemplo del seeder y la dependencia de un binario exclusivo de la imagen PHP. El test CLI conserva la comprobación de `APP_KEY` vacía y salida `cli-ok` mediante un ejecutable temporal.
- El benchmark conserva [datos crudos](../benchmark/resultados), [CSV](../benchmark/resultados.csv), gráficos, configuración y limitaciones. El informe distingue la corrida de fábrica de la comparación con 20 procesos por arquitectura.
- La documentación del modelo de datos incluye [DER](../datos/der.md), [diccionario](../datos/diccionario-de-datos.md) y [justificación de 3FN](../datos/normalizacion.md) en el PR #6; la API conserva una especificación OpenAPI en el PR #5.
- El historial permite identificar por separado infraestructura, benchmark, API, modelo de datos, área de cliente/Portainer, correcciones de tests y actualización de integrantes en los PR #3 a #9.

> **[Completar por el equipo]: ¿Qué prácticas ayudaron al trabajo cotidiano y por qué? Incluir únicamente opiniones confirmadas por sus participantes y ejemplos concretos.**

### Qué mejorar y acciones propuestas

| Hecho verificable / límite de la evidencia | Mejora propuesta | Evidencia de cierre esperada |
|---|---|---|
| El repo contiene planes técnicos, pero no actas ni exportaciones históricas de sprints en la documentación revisada | Incorporar las actas existentes y exportaciones reales de Jira; conservar objetivos, criterios de aceptación y resultados de cada sprint | Registro de ceremonias completado con fuentes y Sprint Backlogs enlazados |
| El informe declara una sola corrida por punto y recursos compartidos | Repetir mediciones y documentar variabilidad antes de generalizar la diferencia A/B | Nuevas salidas crudas, configuración y comparación con límites explícitos |
| El benchmark no incluye usuarios autenticados, continuidad del carrito ni despliegue en Portainer | Validar esos escenarios sobre el stack desplegado | Registro de pasos, entorno y resultados reales |
| Hay snapshots de Stats, pero PI-27 continúa pendiente según el estado informado | Ejecutar y registrar la demo requerida de Stats con tráfico real | Evidencia de tráfico, contadores y estados de ambos nodos vinculada a PI-27 |
| Los tests de seeder y CLI necesitaron ajustes en el PR #8 | Al cambiar datos de ejemplo o infraestructura, revisar las expectativas y dependencias externas de los tests afectados | Suite completa aprobada y dependencias aisladas en los tests |
| Las integraciones #3 a #9 son del 09 al 12/11, posteriores a las fechas límite de los primeros tres avances | Separar cronología de integración, presentación y aprobación académica | Constancias reales de cada entrega y aprobación, sin retrofechar el historial |

> **[Completar por el equipo]: Confirmar qué dificultades se experimentaron, sus causas y el impacto percibido. Elegir las acciones de mejora y registrar responsables, plazos y acuerdos reales; las propuestas de esta tabla todavía no son compromisos del equipo.**

## 4. Registro de ceremonias

Esta plantilla **se completa con las actas del equipo**: Daily Standups, Sprint Planning, Sprint Review y Sprint Retrospective. Cada entrada debe conservar una referencia a su acta o evidencia en Temas o Acuerdos. Las fechas de commits y la nómina de integrantes no se usan para inferir fechas ni asistentes.

| Fecha | Ceremonia | Asistentes | Temas | Acuerdos |
|---|---|---|---|---|

> **[Completar por el equipo]: Incorporar las ceremonias efectivamente realizadas, con fecha, asistentes, temas, acuerdos y referencia al acta correspondiente. Mantener vacía la plantilla hasta contar con esas fuentes.**
