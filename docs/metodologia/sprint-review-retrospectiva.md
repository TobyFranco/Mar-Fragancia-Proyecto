# PI-26 · Sprint Review y retrospectiva final

Avance 4 de Programación IV · Entrega prevista: **27/11/2026**.

**Nota:** Actualizado en PI-28 sobre `main` con PI-27 integrado (#11), al 12/11/2026. Las actas del equipo se suman en la sección 4.

## 1. Resumen del proyecto y metodología

Mar Fragancias es una tienda de decants de perfumes de Asunción, Paraguay. Centraliza catálogo, stock y pedidos; el checkout guarda el pedido pendiente y prepara su envío por WhatsApp. Incluye tienda pública, área de cliente, panel administrativo y API REST v1 con autenticación Sanctum. La solución usa Laravel, MySQL y Docker, con HAProxy como frontal de dos arquitecturas: A, Nginx + PHP-FPM; B, Apache + mod_php. Alcance y funcionalidades: [declaración de alcance](../../Alcance_Proyecto_Mar_Fragancia.pdf) y [README](../../README.md).

La metodología de trabajo es **Scrum**, gestionada en [Jira, proyecto/tablero PI](https://marfragancias.atlassian.net). El calendario académico organiza las entregas en cuatro avances.

| Entrega | Fecha límite de 2026 |
|---|---|
| Avance 1 | 28/08/2026 |
| Avance 2 | 25/09/2026 |
| Avance 3 | 30/10/2026 |
| Avance 4 | 27/11/2026 |

Fuente de requisitos y calendario: `PROYECTO INTEGRADOR- Programación IV.pdf`. Para Scrum, el PDF exige Product Backlog priorizado con criterios de aceptación, Sprint Backlog y Planning de sprints de 2 a 3 semanas, y actas de Daily, Review y Retrospective.

### Equipo actual

Según la sección Equipo del [README](../../README.md):

| Integrante | Rol |
|---|---|
| José Fernández | Scrum Master / Project Manager |
| Tobías Franco | Backend & Architect Lead |
| Matías Amarilla | DevOps & Cloud Engineer |
| Jonathan Valdez | DevOps & Cloud Engineer |
| María Paz Gutiérrez | Frontend & QA Engineer |

### Estado y trazabilidad de Jira

Correspondencia de tareas tomada del registro **PI al 12/11/2026**. Se conservan los estados registrados de PI-1 a PI-25; PI-26 a PI-28 describen la evidencia documental actual en Git. No se consultó ni se cambió el estado del tablero durante PI-28.

| Tarea | Estado registrado / evidencia local | Alcance |
|---|---|---|
| [PI-1](https://marfragancias.atlassian.net/browse/PI-1) | Finalizada | Crear alcance |
| [PI-2](https://marfragancias.atlassian.net/browse/PI-2) | Finalizada | Diseño MER/DER |
| [PI-3](https://marfragancias.atlassian.net/browse/PI-3) | Finalizada | Generar repositorio Docker |
| [PI-4](https://marfragancias.atlassian.net/browse/PI-4) | Finalizada | Diccionario de datos |
| [PI-5](https://marfragancias.atlassian.net/browse/PI-5) | Finalizada | Migraciones y modelos de roles y usuarios |
| [PI-6](https://marfragancias.atlassian.net/browse/PI-6) | Finalizada | Login y registro con API (Sanctum) |
| [PI-7](https://marfragancias.atlassian.net/browse/PI-7) | Finalizada | Middleware de autorización por rol |
| [PI-8](https://marfragancias.atlassian.net/browse/PI-8) | Finalizada | CRUD de marcas, categorías y productos |
| [PI-9](https://marfragancias.atlassian.net/browse/PI-9) | Finalizada | Seeders de roles y tamaños y configuración de entorno |
| [PI-10](https://marfragancias.atlassian.net/browse/PI-10) | Finalizada | Repositorio y entorno compartido (docker-compose) |
| [PI-11](https://marfragancias.atlassian.net/browse/PI-11) | Finalizada | Pipeline CI básico |
| [PI-12](https://marfragancias.atlassian.net/browse/PI-12) | Finalizada | Wireframes |
| [PI-13](https://marfragancias.atlassian.net/browse/PI-13) | Finalizada | Plan de pruebas Auth y Catálogo |
| [PI-14](https://marfragancias.atlassian.net/browse/PI-14) | Finalizada | Backlog en Jira y criterios de aceptación de cada historia |
| [PI-15](https://marfragancias.atlassian.net/browse/PI-15) | Finalizada | Seguimiento (check-in cada 2–3 días) |
| [PI-16](https://marfragancias.atlassian.net/browse/PI-16) | Finalizada | Documentación del Avance 2 |
| [PI-17](https://marfragancias.atlassian.net/browse/PI-17) | Finalizada | HAProxy |
| [PI-18](https://marfragancias.atlassian.net/browse/PI-18) | Finalizada | Arquitecturas A y B |
| [PI-19](https://marfragancias.atlassian.net/browse/PI-19) | Finalizada | Sesiones en MySQL |
| [PI-20](https://marfragancias.atlassian.net/browse/PI-20) | Finalizada | Failover |
| [PI-21](https://marfragancias.atlassian.net/browse/PI-21) | Finalizada | Benchmark |
| [PI-22](https://marfragancias.atlassian.net/browse/PI-22) | Finalizada | API REST v1 |
| [PI-23](https://marfragancias.atlassian.net/browse/PI-23) | Finalizada | Portainer |
| [PI-24](https://marfragancias.atlassian.net/browse/PI-24) | Finalizada | Área de cliente |
| [PI-25](https://marfragancias.atlassian.net/browse/PI-25) | Finalizada | Corrección de tests |
| [PI-26](https://marfragancias.atlassian.net/browse/PI-26) | Documento elaborado; actas y acuerdos pendientes | Cierre metodológico: Sprint Review y retrospectiva final |
| [PI-27](https://marfragancias.atlassian.net/browse/PI-27) | Integrada en main (#11), ensayo documentado | Demo de Stats con tráfico real |
| [PI-28](https://marfragancias.atlassian.net/browse/PI-28) | Documentación preparada en la rama PI-28; revisión del equipo pendiente | Documentación completa |

> **[Completar por el equipo]: Adjuntar la exportación del backlog y los Sprint Backlogs.**

## 2. Sprint Review final

### Matriz de entregables del PDF

La tabla reúne los entregables del calendario del PDF, su estado y la evidencia disponible.

| Avance | Entregable requerido | Estado al corte | Evidencia del repositorio o PR | Tarea de Jira |
|---|---|---|---|---|
| 1 | Definición del alcance y caso PyME | Documentado | [Alcance del proyecto](../../Alcance_Proyecto_Mar_Fragancia.pdf), [README](../../README.md) | [PI-1](https://marfragancias.atlassian.net/browse/PI-1) |
| 1 | Configuración de metodología: tablero y Product Backlog | Finalizado; exportación del backlog por adjuntar | [Tablero PI](https://marfragancias.atlassian.net) y [especificación con referencias Jira](../superpowers/specs/2026-10-18-tienda-y-admin-design.md) | [PI-14](https://marfragancias.atlassian.net/browse/PI-14) |
| 1 | Diagrama MER/DER y diccionario de datos | Documentado | [DER conceptual y lógico](../datos/der.md), [diagrama SVG](../datos/der-logico.svg), [diccionario](../datos/diccionario-de-datos.md); PR #6 | [PI-2](https://marfragancias.atlassian.net/browse/PI-2), [PI-4](https://marfragancias.atlassian.net/browse/PI-4) |
| 1 | Entorno base Docker: Laravel + MySQL local | Implementado | [Compose](../../compose.yaml), [override de desarrollo](../../compose.override.yaml), [setup](../../setup.sh), [dependencias Laravel](../../src/composer.json); PR #3 y #7 para el stack actual | [PI-3](https://marfragancias.atlassian.net/browse/PI-3), [PI-10](https://marfragancias.atlassian.net/browse/PI-10) |
| 2 | Reporte de sprints / flujo Kanban acumulado | Finalizado; reporte de sprints por adjuntar | [Planes de trabajo](../superpowers/plans/2026-10-18-fase-1-tienda-publica.md) e historial Git; reportes en PI-15 y PI-16 | [PI-15](https://marfragancias.atlassian.net/browse/PI-15), [PI-16](https://marfragancias.atlassian.net/browse/PI-16) |
| 2 | Módulo CRUD principal funcional con reglas de negocio | Implementado y cubierto por pruebas | [Controladores administrativos](../../src/app/Http/Controllers/Admin), [tests de productos](../../src/tests/Feature/Admin/ProductosTest.php), [ciclo de pedidos](../../src/tests/Feature/Admin/CicloPedidoTest.php), [plan de pruebas del panel](../pruebas/plan-de-pruebas-panel-admin.md) | [PI-8](https://marfragancias.atlassian.net/browse/PI-8) |
| 2 | Manejo de sesiones centralizadas: Redis/BD | Implementado con BD centralizada | [Configuración de sesión](../../src/config/session.php), [migración sessions](../../src/database/migrations/2026_10_19_000001_create_sessions_table.php), [test de sesiones](../../src/tests/Feature/Infra/SesionesEnBaseDeDatosTest.php), [Compose](../../compose.yaml); PR #3 | [PI-19](https://marfragancias.atlassian.net/browse/PI-19) |
| 2 | Despliegue inicial del stack en Portainer | Finalizado; constancia de despliegue por adjuntar | [Compose](../../compose.yaml), [entrypoint](../../docker/php/entrypoint.sh), [instrucciones Portainer](../../README.md#portainer), [test](../../src/tests/Feature/Infraestructura/PortainerTest.php); PR #7 | [PI-23](https://marfragancias.atlassian.net/browse/PI-23) |
| 3 | Evidencia de ceremonias ágiles y actualización del tablero | Seguimiento finalizado; actas por adjuntar en la sección 4 | Tablero PI, historial Git y registro de ceremonias de la sección 4 | [PI-15](https://marfragancias.atlassian.net/browse/PI-15), [PI-26](https://marfragancias.atlassian.net/browse/PI-26) |
| 3 | Configuración e integración de HAProxy | Implementado | [haproxy.cfg](../../docker/haproxy/haproxy.cfg): roundrobin, health checks sobre `/up`, reintentos y Stats en 8404; [tests de balanceo](../../src/tests/Feature/Infraestructura/BalanceoTest.php); PR #3 | [PI-17](https://marfragancias.atlassian.net/browse/PI-17) |
| 3 | Despliegue de las arquitecturas A y B activas | Implementadas y medidas en el benchmark | [Nginx](../../docker/nginx), [PHP-FPM](../../docker/php), [Apache](../../docker/apache), [Compose](../../compose.yaml), [informe de ejecución](../benchmark/informe-benchmark.md); PR #3 y #4 | [PI-18](https://marfragancias.atlassian.net/browse/PI-18) |
| 3 | Demostración de failover en vivo | Implementado; script y corrida documentados | [Demo reproducible](../../docker/scripts/demo-failover.sh), [resultado de failover](../benchmark/resultados/haproxy-failover.txt), [informe](../benchmark/informe-benchmark.md); PR #3 y #4 | [PI-20](https://marfragancias.atlassian.net/browse/PI-20) |
| 4 | Cierre metodológico: Review/Retrospective final o métricas de flujo | Pendiente de cierre; documento elaborado | Este documento y plantilla de actas de la sección 4 | [PI-26](https://marfragancias.atlassian.net/browse/PI-26) |
| 4 | Benchmarking: RPS y latencia A vs B | Documentado con resultados y limitaciones | [Informe](../benchmark/informe-benchmark.md), [CSV](../benchmark/resultados.csv), [script](../../docker/scripts/benchmark.sh); PR #4 | [PI-21](https://marfragancias.atlassian.net/browse/PI-21) |
| 4 | HAProxy Stats en 8404 con tráfico real | Ensayo real documentado e integrado (#11); presentación final prevista el 27/11 | [Guion y registro PI-27](../demo/guion-demo-avance4.md#registro-del-ensayo-real), [resumen](../demo/ensayo-2026-11-12/resumen.json), cuatro CSV y comprobación del carrito enlazados allí | [PI-27](https://marfragancias.atlassian.net/browse/PI-27) |
| 4 | Repositorio Git y documentación técnica/metodológica completa | Índice de documentación preparado en PI-28; evidencias externas pendientes del equipo | [Entregables del README](../../README.md#entregables-del-proyecto), [documentación API](../api/README.md), datos, pruebas, benchmark y este documento | [PI-28](https://marfragancias.atlassian.net/browse/PI-28), [PI-26](https://marfragancias.atlassian.net/browse/PI-26) |

Otros requisitos del cuerpo del PDF cuentan con evidencia: la [evaluación de normalización](../datos/normalizacion.md) documenta 3FN y explicita dos excepciones deliberadas, `detalle_pedidos.subtotal` y `pedidos.total` (PR #6); la [API REST](../api/README.md) y su [OpenAPI](../api/openapi.yaml) describen endpoints y autenticación Sanctum (PR #5); los roles actuales se registran en la sección 1 y en el README (PR #9). Las [maquetas](../diseno/maquetas/diseno-final.html) y el [plan de pruebas Auth/Catálogo](../pruebas/plan-de-pruebas-auth-catalogo.md) trazan PI-12 y PI-13. El resultado de tests registrado abajo corresponde a una ejecución local.

### PR integrados y fechas comprobables

Fuente: `git log main --first-parent --format='%h %cs %s' --grep='(#[0-9]'`. Se registran las fechas de los commits de integración y los enlaces a los PR del repositorio.

| PR | Fecha en Git | Commit | Cambio integrado |
|---|---|---|---|
| [#3](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/3) | 09/11/2026 | `1ad65f7` | HAProxy con balanceo entre Nginx + PHP-FPM y Apache + mod_php |
| [#4](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/4) | 09/11/2026 | `3010df1` | Benchmarking A vs B |
| [#5](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/5) | 09/11/2026 | `961a850` | API REST v1 con Sanctum |
| [#6](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/6) | 10/11/2026 | `532a2f7` | DER, diccionario y justificación de 3FN |
| [#7](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/7) | 11/11/2026 | `18db4eb` | Área de cliente y stack preparado para Portainer CE |
| [#8](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/8) | 12/11/2026 | `d7da1b7` | Corrección de tests del seeder y Portainer |
| [#9](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/9) | 12/11/2026 | `b6d2c2c` | Actualización del equipo en el README |
| [#11](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/11) | 12/11/2026 | `e9dafb9` | Demo del Avance 4, ensayo de carrito y failover y ajuste del override local |

Son **8 PR integrados en la tabla**. En toda la línea principal consultada hay **9 referencias de integración de PR**: las ocho anteriores y [#1](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/1), commit `29ecbaf` del 19/10/2026.

### Métricas verificadas

El 12/11/2026 se ejecutó desde la raíz:

```bash
cd src
php artisan test
```

Resultado PI-28: **333 tests aprobados de 333, 3411 aserciones, sin fallos**, duración informada de 20316 ms. El entorno de tests está definido en [phpunit.xml](../../src/phpunit.xml); usa SQLite en memoria.

Resultados históricos del [informe de benchmark del 09/11/2026](../benchmark/informe-benchmark.md), con 20 procesos PHP por arquitectura y concurrencia 50:

| Endpoint / escenario | A: Nginx + PHP-FPM | B: Apache + mod_php |
|---|---|---|
| `/up` | 456,68 RPS; p95 161 ms | 434,43 RPS; p95 158 ms |
| `/perfumes` | 87,33 RPS; p95 713 ms | 80,87 RPS; p95 794 ms |
| `/perfumes/coromandel` | 110,52 RPS; p95 563 ms | 102,84 RPS; p95 605 ms |

El informe registra **48.000 solicitudes directas sin errores** y reparto de **1000/1000** en la corrida normal por HAProxy. En esa corrida HAProxy obtuvo **78,18 RPS** y **p95 1194 ms** sobre 2000 solicitudes. Durante el failover controlado completó **924 solicitudes sin errores**, con **p95 3193 ms**.

**Limitaciones del benchmark:** una corrida por punto en una máquina compartida, sin TLS, usuarios autenticados, validación de continuidad del carrito ni medición en Portainer; los tamaños de carga normal y failover difieren y la ventaja de A del 5 al 8 % es una tendencia.

### Pendientes para la entrega

Registro único de evidencias externas del cierre metodológico y de despliegue; no se sustituyen con planes técnicos ni con estados de tareas:

- **PI-14:** adjuntar exportación o capturas del Product Backlog priorizado, con historias y criterios de aceptación legibles.
- **PI-15 / PI-16:** adjuntar Sprint Backlogs, Planning con objetivos y fechas de sprints de 2 a 3 semanas, y reportes de sprints del Avance 2.
- **PI-15 / PI-26:** completar la sección 4 con actas reales de Daily, Review y Retrospective de los hitos, incluida la final; incorporar opiniones, acuerdos, responsables y plazos del equipo.
- **PI-23:** adjuntar constancia del despliegue real en Portainer (Stack, servicios y nodos disponibles), sin publicar claves.

PI-27 tiene [ensayo real integrado](../demo/guion-demo-avance4.md#registro-del-ensayo-real). PI-28 aporta el [índice de entregables](../../README.md#entregables-del-proyecto) y la documentación técnica de la entrega.

## 3. Retrospectiva

La retrospectiva reúne resultados observables y propuestas de mejora para acordar con el equipo.

### Qué salió bien: hechos observables

- La suite completa pasa con 333 tests (validación PI-28). El [PR #8](https://github.com/TobyFranco/Mar-Fragancia-Proyecto/pull/8) corrigió dos supuestos de tests: el correo de ejemplo del seeder y la dependencia de un binario exclusivo de la imagen PHP. El test CLI conserva la comprobación de `APP_KEY` vacía y salida `cli-ok` mediante un ejecutable temporal.
- El benchmark conserva [datos crudos](../benchmark/resultados), [CSV](../benchmark/resultados.csv), gráficos, configuración y limitaciones. El informe distingue la corrida de fábrica de la comparación con 20 procesos por arquitectura.
- La documentación del modelo de datos incluye [DER](../datos/der.md), [diccionario](../datos/diccionario-de-datos.md) y [justificación de 3FN](../datos/normalizacion.md) en el PR #6; la API conserva una especificación OpenAPI en el PR #5.
- El historial permite identificar por separado infraestructura, benchmark, API, modelo de datos, área de cliente/Portainer, correcciones de tests y actualización de integrantes en los PR de la tabla anterior, incluido el ensayo PI-27 del PR #11.

### Qué mejorar y acciones propuestas

| Observación | Mejora propuesta | Evidencia de cierre esperada |
|---|---|---|
| Los planes técnicos y el seguimiento de Jira se complementan con las actas del equipo | Reunir actas y exportaciones de Jira con objetivos, criterios de aceptación y resultados de cada sprint | Registro de ceremonias completado con fuentes y Sprint Backlogs enlazados |
| El informe conserva una comparación A/B reproducible | Ampliar las mediciones y los escenarios de uso sobre el stack desplegado | Nuevas salidas crudas y registro del entorno y los resultados |
| PI-27 conserva un ensayo de Stats, failover y carrito | Usar el guion integrado para preparar la presentación del 27/11 | [Ensayo real](../demo/guion-demo-avance4.md#registro-del-ensayo-real) y ambos nodos UP antes de presentar |
| Los tests de seeder y CLI necesitaron ajustes en el PR #8 | Al cambiar datos de ejemplo o infraestructura, revisar las expectativas y dependencias externas de los tests afectados | Suite completa aprobada y dependencias aisladas en los tests |

> **[Completar por el equipo]: Añadir opiniones sobre aciertos y dificultades; acordar acciones, responsables y plazos.**

## 4. Registro de ceremonias

Esta plantilla **se completa con las actas del equipo**: Daily Standups, Sprint Planning, Sprint Review y Sprint Retrospective. En Temas o Acuerdos se incluye la referencia al acta correspondiente.

| Fecha | Ceremonia | Asistentes | Temas | Acuerdos |
|---|---|---|---|---|

> **[Completar por el equipo]: Incorporar las ceremonias realizadas y enlazar sus actas.**
