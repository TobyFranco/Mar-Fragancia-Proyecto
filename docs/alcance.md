# Declaración de alcance · Mar Fragancias

PI-1 · Actualización documental PI-28 · Corte del repositorio: 12/11/2026 · Entrega: 27/11/2026.

Versión para imprimir: [Alcance del proyecto en PDF](../Alcance_Proyecto_Mar_Fragancia.pdf). Índice y puesta en marcha: [README](../README.md).

## Empresa y problema

Mar Fragancias es una PyME comercial de Asunción, Paraguay, que vende decants de perfumes originales de 5 ml y 10 ml. La atención por Instagram y WhatsApp exige consultar manualmente precios y disponibilidad, coordinar pedidos y llevar el inventario en herramientas separadas. Esto dificulta responder a tiempo, evitar ventas sin stock y reconstruir el historial de operaciones.

## Objetivos del negocio

- Centralizar catálogo, precios, stock por presentación y pedidos en una sola base de datos.
- Reducir el tiempo de consulta de disponibilidad; la meta inicial es una reducción del 50 %. Es un objetivo de negocio, sin medición de impacto comercial en este repositorio.
- Reducir errores de transcripción preparando el mensaje de WhatsApp desde el pedido persistido.
- Permitir a cada clienta consultar sus pedidos y al equipo seguir estados y movimientos de inventario.

## Dentro del alcance

- Tienda pública: marcas, catálogo, búsqueda y filtros, fichas de perfumes, decants de 5 ml y 10 ml, recomendaciones por notas y termómetro olfativo.
- Autenticación y autorización: registro e ingreso web, roles administrador/cliente y tokens Sanctum para la API.
- Carrito sin cuenta obligatoria y checkout con retiro o entrega; guarda un pedido pendiente y redirige a WhatsApp para coordinarlo. Crear el pedido no reserva ni descuenta stock.
- Pedidos: confirmación, preparación, envío, entrega o cancelación según las transiciones admitidas; confirmar descuenta stock y una cancelación permitida lo devuelve, con historial y kardex.
- Panel administrativo: productos, presentaciones, marcas, categorías, consulta de tamaños, pedidos, dashboard e inventario con entradas y salidas auditadas.
- Área de cliente: consulta de pedidos propios y edición de nombre, teléfono y dirección actual. Cada pedido conserva sus datos de contacto y entrega del momento.
- API REST v1: catálogo público, autenticación, pedidos propios y administración de pedidos e inventario; contrato en [OpenAPI](api/openapi.yaml).
- Infraestructura: Laravel 13 y MySQL 8; sesiones y caché centralizadas; HAProxy con roundrobin, Stats 8404 y health checks; A con Nginx/PHP-FPM y B con Apache/mod_php; configuración de Stack para Portainer CE.
- Entrega técnica: repositorio Git, modelo de datos, diccionario, evaluación de 3FN, pruebas, benchmark A/B y guion con evidencia de tráfico, failover y continuidad del carrito.
- Entrega metodológica: trazabilidad en Jira y cierre Scrum con evidencias reales del equipo, cuyo registro pendiente está en [PI-26](metodologia/sprint-review-retrospectiva.md#4-registro-de-ceremonias).

## Fuera del alcance de este semestre

- Cobro en línea, pasarelas de pago, facturación tributaria e integración con sistemas contables o transportistas.
- Envío automático de mensajes: el sistema abre WhatsApp con el texto preparado; una persona confirma su envío y coordina la compra.
- Venta pública de frascos completos o tamaños distintos de 5 ml y 10 ml.
- Múltiples direcciones por clienta o historial de cambios de su perfil; se conserva el destino de cada pedido.
- Notificaciones automáticas externas de stock: el stock crítico se consulta en el panel y la API.
- Alta disponibilidad de MySQL, del host o del propio HAProxy: el failover demostrado corresponde a los nodos web.
- TLS y validación de capacidad de producción: el entorno documentado usa HTTP y un benchmark local de una máquina compartida.

## Modelo de datos y criterios de aceptación técnicos

El [DER conceptual y lógico](datos/der.md), el [diccionario MySQL](datos/diccionario-de-datos.md) y la [evaluación de normalización](datos/normalizacion.md) describen las migraciones actuales. La justificación reconoce dos importes calculados persistidos, `detalle_pedidos.subtotal` y `pedidos.total`; no se presenta como 3FN estricta sin excepciones.

Los criterios funcionales se verifican mediante los [planes de pruebas](pruebas/plan-de-pruebas-auth-catalogo.md) y el [plan del panel](pruebas/plan-de-pruebas-panel-admin.md). El [ensayo PI-27](demo/guion-demo-avance4.md#registro-del-ensayo-real) demuestra disponibilidad de ambos nodos, caída y recuperación de A y conservación de Coromandel de 5 ml, cantidad 1, con las mismas cookies.

## Roles y responsabilidades actuales

- José Fernández — Scrum Master / Project Manager: tablero, coordinación, alcance y ceremonias.
- Tobías Franco — Backend & Software Architect Lead: lógica Laravel, arquitectura, datos, migraciones y API.
- Matías Amarilla — DevOps & Cloud Engineer: contenedores, despliegue, automatización y operación.
- Jonathan Valdez — DevOps & Cloud Engineer: HAProxy, Stats, failover y apoyo de infraestructura.
- María Paz Gutiérrez — Frontend & QA Engineer: interfaz, consumo de endpoints, pruebas funcionales y benchmarking.

La distribución identifica responsables de las áreas; el trabajo y la revisión pueden ser compartidos. La correspondencia PI-1 a PI-28 y las evidencias por avance se consultan en [Entregables del proyecto](../README.md#entregables-del-proyecto).
