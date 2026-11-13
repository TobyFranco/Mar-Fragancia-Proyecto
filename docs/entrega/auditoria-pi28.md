# PI-28 · Auditoría técnica y metodológica de la entrega final

Entrega prevista: **27/11/2026**. Corte: **12/11/2026**, sobre `main` actualizado, commit base `e9dafb9` (PI-27 integrado), rama `docs/documentacion-final`.

## Fuente y criterio de verificación

Se leyó la consigna original con:

```bash
pdftotext -layout '/home/paz/Descargas/PROYECTO INTEGRADOR- Programación IV.pdf' /tmp/pi28-requisitos.txt
```

La consigna no está versionada en este repositorio. Esta matriz transcribe sus requisitos de las secciones **2, 3, 5, 6 y 8** para contrastarlos con los documentos, las rutas, las migraciones, la configuración y las evidencias existentes. Cada fila identifica si el documento está enlazado desde el [README](../../README.md#entregables-del-proyecto) y qué se pudo comprobar. Las tareas se tomaron del [registro PI-1 a PI-28](../metodologia/sprint-review-retrospectiva.md#estado-y-trazabilidad-de-jira); no se consultó ni modificó Jira.

**Vigente** significa que el documento existe y coincide con los archivos actuales revisados. **Parcial** indica evidencia externa ausente o una salvedad explícita. La existencia de un plan, una configuración o un commit no demuestra una ceremonia, un despliegue operado por el equipo ni una aprobación del docente. Las cifras históricas del benchmark y del ensayo conservan sus fechas; no se ejecutó un nuevo benchmark en PI-28.

## §2 · Metodología y documentación obligatoria

| Requisito | Documento/evidencia existente | README | Comprobación y resultado | Jira |
|---|---|---|---|---|
| Adoptar Scrum o Kanban | [Resumen de metodología](../metodologia/sprint-review-retrospectiva.md#1-resumen-del-proyecto-y-metodología) | Sí | Scrum declarado; el calendario académico no sustituye sprints | PI-14, PI-26 |
| Tablero ágil digital obligatorio | [Registro y enlace al tablero PI](../metodologia/sprint-review-retrospectiva.md#estado-y-trazabilidad-de-jira) | Sí | Parcial: enlace y correspondencia de tareas disponibles; exportación/capturas por adjuntar | PI-14 |
| Product Backlog: historias priorizadas con criterios de aceptación | [Registro metodológico](../metodologia/sprint-review-retrospectiva.md#pendientes-para-la-entrega) | Sí | Parcial: la tarea figura finalizada en el registro; el backlog completo no está exportado en Git | PI-14 |
| Sprint Backlog y Sprint Planning, sprints de 2–3 semanas | [Registro de ceremonias](../metodologia/sprint-review-retrospectiva.md#4-registro-de-ceremonias) | Sí | Parcial: faltan fuentes del equipo con fechas, objetivos, selección y duración; los planes técnicos no son Sprint Backlogs | PI-15, PI-16 |
| Actas de Daily Standups breves | [Registro de ceremonias](../metodologia/sprint-review-retrospectiva.md#4-registro-de-ceremonias) | Sí | Parcial: plantilla sin actas; el check-in de PI-15 no acredita Daily por sí solo | PI-15, PI-26 |
| Sprint Review al finalizar cada hito | [Review y registro](../metodologia/sprint-review-retrospectiva.md#2-sprint-review-final) | Sí | Parcial: resumen técnico elaborado; faltan actas y acuerdos reales | PI-26 |
| Sprint Retrospective al finalizar cada hito | [Retrospectiva y registro](../metodologia/sprint-review-retrospectiva.md#3-retrospectiva) | Sí | Parcial: hechos/propuestas documentados, opiniones y acuerdos aún sin registrar | PI-26 |
| Kanban: columnas To Do, In Progress, Code Review/QA y Done | [Metodología elegida](../metodologia/sprint-review-retrospectiva.md#1-resumen-del-proyecto-y-metodología) | Sí | No aplica: alternativa a Scrum | PI-14 |
| Kanban: límites de trabajo en proceso por columna | [Metodología elegida](../metodologia/sprint-review-retrospectiva.md#1-resumen-del-proyecto-y-metodología) | Sí | No aplica: alternativa a Scrum | PI-14 |
| Kanban: CFD o seguimiento de Lead Time y Cycle Time | [Metodología elegida](../metodologia/sprint-review-retrospectiva.md#1-resumen-del-proyecto-y-metodología) | Sí | No aplica: alternativa a Scrum | PI-14 |

## §3 · Alcance y modelo de datos

| Requisito | Documento/evidencia existente | README | Comprobación y resultado | Jira |
|---|---|---|---|---|
| Declaración de alcance y caso PyME paraguaya | [Alcance](../alcance.md) y [PDF actualizado](../../Alcance_Proyecto_Mar_Fragancia.pdf) | Sí | Vigente: Mar Fragancias, comercio de decants de Asunción; contrasta tienda, panel y área cliente con las rutas web | PI-1 |
| Objetivos del negocio e impacto en la PyME | [Objetivos](../alcance.md#objetivos-del-negocio) | Sí | Vigente: centralización, disponibilidad y reducción de errores; el 50 % es una meta sin medición comercial, no un resultado demostrado | PI-1 |
| Límites explícitos In-Scope / Out-of-Scope | [Dentro](../alcance.md#dentro-del-alcance) y [fuera del alcance](../alcance.md#fuera-del-alcance-de-este-semestre) | Sí | Corregido: solo 5/10 ml, dirección actual del perfil y datos históricos por pedido; sin cobro ni mensajería automáticos | PI-1, PI-28 |
| Entregables principales: módulos funcionales | [Funcionalidades](../../README.md#funcionalidades) y [plan del panel](../pruebas/plan-de-pruebas-panel-admin.md) | Sí | Vigente: rutas y tests de catálogo, pedidos, inventario, Auth y área cliente existen; tamaños es consulta, no CRUD completo | PI-5 a PI-9, PI-24 |
| Entregable API REST | [Guía](../api/README.md) y [OpenAPI](../api/openapi.yaml) | Sí | Vigente: las rutas v1 están cubiertas por DocumentacionApiTest; tokens y autorización comparten la lógica de negocio | PI-22 |
| Entregable despliegue en Portainer | [Guía Portainer](../../README.md#portainer) | Sí | Parcial: configuración y tests presentes; constancia de operación del Stack por adjuntar | PI-23 |
| Entregable balanceo HAProxy | [Arquitectura](../../README.md#docker-y-haproxy) y [ensayo](../demo/guion-demo-avance4.md#registro-del-ensayo-real) | Sí | Vigente: configuración y evidencia A/B disponibles | PI-17, PI-18, PI-20 |
| Entregable documentación | [Índice de entregables](../../README.md#entregables-del-proyecto) | Sí | Documentación técnica revisada en PI-28; no se dan por completas las evidencias externas pendientes | PI-28, PI-26 |
| MER/DER conceptual | [Nivel conceptual](../datos/der.md#nivel-conceptual) | Sí | Vigente: 14 entidades de negocio y 15 FK reales; separa las tablas operativas | PI-2 |
| MER/DER lógico | [Nivel lógico](../datos/der.md#nivel-lógico) y [SVG](../datos/der-logico.svg) | Sí | Vigente: tipos, PK, FK, nulabilidad y cardinalidades coinciden con el diccionario | PI-2 |
| Diccionario: tablas, campos y tipos | [Diccionario MySQL](../datos/diccionario-de-datos.md) | Sí | Verificado: regenerado desde MySQL del stack, 22 tablas, sin diferencias frente al archivo versionado; 20 migraciones aplicadas | PI-4 |
| Diccionario: PK, FK, restricciones e índices | [Diccionario](../datos/diccionario-de-datos.md) | Sí | Verificado en la misma regeneración; no inventa UNIQUE(producto_id,tamano_id), CHECK ni tablas ausentes | PI-4 |
| Justificación de normalización en 3FN | [Normalización](../datos/normalizacion.md) | Sí | Parcial frente a 3FN estricta: se documentan dos importes calculados persistidos, detalle_pedidos.subtotal y pedidos.total; aceptación académica pendiente | PI-2, PI-4 |

## §5 · Equipo y asignación formal de roles

| Requisito | Documento/evidencia existente | README | Comprobación y resultado | Trazabilidad |
|---|---|---|---|---|
| Equipo de 3 a 4 integrantes | [Equipo actual](../../README.md#equipo) | Sí | Discrepancia: se registran cinco personas; requiere confirmación del docente, no se elimina a nadie para aparentar cumplimiento | PI-26; pendiente en README |
| Scrum Master / Project Manager: metodología, tablero y alcance | [Responsabilidades](../alcance.md#roles-y-responsabilidades-actuales) | Sí | José Fernández; asignación documentada | PI-26 |
| Backend & Software Architect Lead: Laravel, datos, migraciones y APIs | [Responsabilidades](../alcance.md#roles-y-responsabilidades-actuales) | Sí | Tobías Franco; asignación documentada | PI-26 |
| DevOps & Cloud Engineer: Docker, Compose, Stack y HAProxy | [Responsabilidades](../alcance.md#roles-y-responsabilidades-actuales) | Sí | Matías Amarilla y Jonathan Valdez; responsabilidades documentadas | PI-26 |
| Frontend & QA Engineer: interfaz, endpoints, pruebas y benchmarking | [Responsabilidades](../alcance.md#roles-y-responsabilidades-actuales) y [demo](../demo/guion-demo-avance4.md) | Sí | María Paz Gutiérrez; QA y operación de la demo compartidos según el guion | PI-26 |

El PDF de alcance se actualizó para reflejar el equipo vigente. La búsqueda de `romero` en el contenido textual del repositorio solo conserva las dos notas olfativas de ProductoSeeder; se revisó también el texto extraído del PDF. El historial Git permanece como registro histórico.

## §6 · Requisitos técnicos e infraestructura

| Requisito | Documento/evidencia existente | README | Comprobación y resultado | Jira |
|---|---|---|---|---|
| Laravel v10 o v11+ y MySQL/PostgreSQL | [Tecnologías y arranque](../../README.md#tecnologías), [composer.lock](../../src/composer.lock), [Compose](../../compose.yaml) | Sí | Vigente: Laravel 13.39.0 en lock, MySQL 8.0; PHP de las imágenes 8.4 y del host de pruebas 8.5 | PI-3, PI-10 |
| Sesiones Redis o BD centralizada | [Arquitectura](../../README.md#docker-y-haproxy), [test de sesiones](../../src/tests/Feature/Infra/SesionesEnBaseDeDatosTest.php), [carrito PI-27](../demo/ensayo-2026-11-12/carrito.txt) | Sí | Verificado: SESSION_DRIVER=database, MySQL y APP_KEY compartidos; carrito antes/durante/después con mismas cookies en el ensayo | PI-19 |
| HAProxy con roundrobin o leastconn | [HAProxy](../../docker/haproxy/haproxy.cfg) y [arquitectura](../../README.md#docker-y-haproxy) | Sí | Vigente: balance roundrobin, sin afinidad a nodo | PI-17 |
| Dashboard Stats activo en 8404 | [Guion y CSV](../demo/guion-demo-avance4.md#registro-del-ensayo-real) | Sí | Verificado: Stats con autenticación y CSV; PI-27 guarda tráfico real y estados | PI-17, PI-27 |
| Health checks y failover automático | [Configuración](../../docker/haproxy/haproxy.cfg), [guion](../demo/guion-demo-avance4.md) | Sí | Vigente: GET /up cada 2 s, fall 3, rise 2, redispatch y retries; ensayo real de caída y recuperación | PI-20 |
| Despliegue del Stack mediante Portainer | [Portainer](../../README.md#portainer), [entrypoint](../../docker/php/entrypoint.sh), [tests](../../src/tests/Feature/Infraestructura/PortainerTest.php) | Sí | Parcial: solo compose.yaml para Portainer Standalone, sin override local; evidencia externa centralizada en PI-26 | PI-23 |

## §8 · Calendario, entregables y prerrequisito de defensa

Fechas de la consigna; no equivalen a fechas de aprobación verificadas.

| Hito / fecha de 2026 | Entregable obligatorio | Documento/evidencia existente | README | Resultado y Jira |
|---|---|---|---|---|
| Prerrequisito de Defensa Final | Presentación y aprobación de cada avance | [Calendario metodológico](../metodologia/sprint-review-retrospectiva.md#1-resumen-del-proyecto-y-metodología) | Sí | No acreditado en Git: constancias a cargo del equipo/docente; pendiente adicional del índice |
| A1 · 28/08 | Alcance y caso PyME | [Alcance](../alcance.md), [PDF](../../Alcance_Proyecto_Mar_Fragancia.pdf) | Sí | Documento actualizado; PI-1 |
| A1 · 28/08 | Tablero y Product Backlog | [Metodología](../metodologia/sprint-review-retrospectiva.md) | Sí | Exportación pendiente; PI-14 |
| A1 · 28/08 | MER/DER y diccionario | [DER](../datos/der.md), [diccionario](../datos/diccionario-de-datos.md) | Sí | Vigentes; PI-2, PI-4 |
| A1 · 28/08 | Entorno Docker Laravel + MySQL local | [Arranque](../../README.md#puesta-en-marcha), [Compose](../../compose.yaml) | Sí | Configuración e inicialización revisadas; PI-3, PI-10 |
| A2 · 25/09 | Reporte de sprints o flujo Kanban acumulado | [Registro metodológico](../metodologia/sprint-review-retrospectiva.md#pendientes-para-la-entrega) | Sí | Reportes Scrum pendientes; PI-15, PI-16 |
| A2 · 25/09 | CRUD principal con reglas de negocio | [Plan del panel](../pruebas/plan-de-pruebas-panel-admin.md) | Sí | Suite actual aprobada, sin afirmar ejecución histórica en la fecha límite; PI-8 |
| A2 · 25/09 | Sesiones centralizadas | [Arquitectura](../../README.md#docker-y-haproxy), [ensayo](../demo/guion-demo-avance4.md#registro-del-ensayo-real) | Sí | MySQL y clave compartida; PI-19 |
| A2 · 25/09 | Despliegue inicial en Portainer | [Portainer](../../README.md#portainer) | Sí | Configuración presente, constancia por adjuntar; PI-23 |
| A3 · 30/10 | Evidencia de ceremonias y tablero actualizado | [Registro de actas](../metodologia/sprint-review-retrospectiva.md#4-registro-de-ceremonias) | Sí | Pendiente del equipo; PI-15, PI-26 |
| A3 · 30/10 | Integración HAProxy | [Arquitectura](../../README.md#docker-y-haproxy), [configuración](../../docker/haproxy/haproxy.cfg) | Sí | Vigente; PI-17 |
| A3 · 30/10 | Arquitecturas A y B activas | [Arquitectura](../../README.md#docker-y-haproxy), [ensayo](../demo/guion-demo-avance4.md#registro-del-ensayo-real) | Sí | Ambas UP/L7OK en el ensayo y en validación PI-28; PI-18 |
| A3 · 30/10 | Demostración en vivo de failover | [Guion y ensayo](../demo/guion-demo-avance4.md), [script](../../docker/scripts/demo-failover.sh) | Sí | Ensayo disponible; la presentación se realiza ante el docente; PI-20 |
| A4 · 27/11 | Cierre Review/Retrospective final o métricas de flujo | [Cierre Scrum](../metodologia/sprint-review-retrospectiva.md) | Sí | Documento elaborado; actas/opiniones/acuerdos pendientes; PI-26 |
| A4 · 27/11 | Benchmark RPS/latencia A vs B | [Informe del 09/11](../benchmark/informe-benchmark.md), [CSV](../benchmark/resultados.csv) | Sí | Datos crudos y limitaciones presentes; PI-21 |
| A4 · 27/11 | Stats 8404 con tráfico real | [Ensayo PI-27](../demo/guion-demo-avance4.md#registro-del-ensayo-real) | Sí | 60 HTTP 200, 30/30, cuatro CSV y carrito conservado; PI-27 |
| A4 · 27/11 | Git y documentación completa | [README](../../README.md), esta auditoría y [validaciones](validaciones-pi28.txt) | Sí | Revisión técnica preparada en PI-28; faltan las evidencias externas identificadas, no se declara cierre académico total |

## Trazabilidad complementaria

El índice del README enlaza **PI-1 a PI-28**, incluidas tareas de implementación que el PDF no enumera por nombre: modelos/roles (PI-5), Auth/API (PI-6), autorización (PI-7), seeders (PI-9), diseño (PI-12), QA (PI-13 y PI-25), API REST (PI-22) y área de cliente (PI-24).

**PI-11:** el registro metodológico la identifica como pipeline CI finalizado, pero no hay workflow en `.github/workflows/`, `.gitlab-ci.yml`, Jenkinsfile ni otras configuraciones de CI versionadas en el árbol actual. Se conserva esa diferencia entre el registro y la evidencia disponible; no se creó un pipeline en esta tarea documental. El pendiente está en el README.

## Correcciones y límites de esta revisión

- El README pasó a mapear los requisitos del PDF a documentos y Jira, incorporando el plan de pruebas del panel y distinguiendo evidencias presentes de las externas pendientes.
- Se actualizó el alcance en Markdown/PDF: equipo vigente, límites explícitos, tamaños admitidos, perfil actual frente a snapshots por pedido, stock crítico consultable y WhatsApp como coordinación manual.
- Se aclararon primera inicialización, `docker compose up -d --build`, override local frente a Stack Portainer, comandos PHP por nodo, APP_KEY compartida y responsabilidades del setup/entrypoint. `up --build` no sustituye migraciones ni instalación de dependencias en los bind mounts.
- Se ajustaron requisitos Node a los lockfiles (Node 22.13+ cubre Vite y la dependencia opcional multiplex), se incluyó `storage:link` en la alternativa local y se retiraron rutas PATH y de venv específicas de otra instalación en las guías de pruebas, DER y gráficos.
- Se actualizaron PI-26, resultados actuales de pruebas y la referencia al PR #11. Las cifras 150 y 269 de las fases 1/2 se preservan como históricas, sin presentarlas como el total actual.
- Se corrigió el pendiente histórico de overflow de stock: el servicio actual ya rechaza exceder INT y la API tiene prueba de 422 sin escrituras. No se modificó código.
- El diccionario se regeneró desde el MySQL real sin diferencias. La suite comprueba además nombres del esquema y cobertura de rutas de OpenAPI. No se cambiaron las excepciones de normalización ya documentadas.
- El barrido inicial no encontró enlaces locales rotos; el script volvió a verificar los nuevos enlaces, anclas, imágenes y referencias HTML tras los cambios. Los documentos bajo `docs/superpowers/` se excluyen como fuentes según el alcance solicitado; sus destinos siguen debiendo existir si se enlazan desde otro documento.
- No se repitieron el benchmark, las pruebas visuales ni un despliegue Portainer; sus resultados y límites conservan contexto histórico. No se alteró código de la aplicación ni archivos Compose.

### Observaciones del código, sin cambios

Se conservan las limitaciones ya señaladas en el [plan del panel](../pruebas/plan-de-pruebas-panel-admin.md#resultado-y-límites). La lectura del código actual confirma que el filtro administrativo `q=0` se omite por la evaluación booleana de la cadena en [ProductoController](../../src/app/Http/Controllers/Admin/ProductoController.php), y que [Carrito](../../src/app/Services/Carrito.php) y [CrearPedidoDesdeCarrito](../../src/app/Actions/CrearPedidoDesdeCarrito.php) convierten los precios a enteros, mientras [Moneda](../../src/app/Support/Moneda.php) redondea al mostrarlos. Con precios fraccionarios puede haber diferencias entre ficha e importe del pedido. Se registran para otra tarea, sin afirmar una reproducción nueva en navegador ni modificar las reglas monetarias.

## Verificación reproducible y pendientes

Desde la raíz:

```bash
python3 docs/verificar-enlaces.py
(cd src && php artisan test)
curl --fail --silent --show-error --include http://localhost/up
```

El [verificador](../verificar-enlaces.py) requiere Python 3 y `markdown-it-py` 3+ en el entorno de herramientas; usa el parser CommonMark para enlaces inline, referencias e imágenes y también lee `href`/`src` HTML. No agrega dependencias a la aplicación. Enumera Markdown versionado y nuevo no ignorado por Git (incluidas carpetas ocultas), excluye `docs/superpowers/`, comprueba destinos y anclas de encabezados Markdown, y devuelve código distinto de cero ante errores. No hace peticiones externas ni valida fragmentos internos de PDF/SVG.

La [salida guardada](validaciones-pi28.txt) recoge enlaces, suite, estado HTTP y nodos de Stats. Los pendientes del equipo están centralizados en [PI-26](../metodologia/sprint-review-retrospectiva.md#pendientes-para-la-entrega) y los adicionales de esta revisión en [el índice del README](../../README.md#pendientes-del-equipo-para-la-entrega), una línea por ítem.
