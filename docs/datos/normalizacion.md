# Normalización del modelo de Mar Fragancias

## Resumen

**Las 14 tablas de negocio cumplen 1FN y 2FN, y cumplen 3FN salvo dos columnas calculadas que se guardan a propósito.**

| Desnormalización | Por qué se guarda | Tipo |
|---|---|---|
| `detalle_pedidos.subtotal` = cantidad × precio_unitario | Consultar ventas sin recalcular cada línea | Excepción a 3FN, deliberada |
| `pedidos.total` = subtotal + costo_envio | Mostrar y filtrar pedidos por importe sin sumar | Excepción a 3FN, deliberada |

Hay otros datos que parecen redundantes y **no** violan 3FN. Son **registros históricos**: guardan cómo era algo en el momento del hecho, no una copia del valor actual.

- `detalle_pedidos.precio_unitario`: el precio cobrado en esa venta.
- Contacto y entrega en `pedidos`: con quién y dónde se coordinó esa compra.
- `movimientos_inventario.stock_resultante`: el saldo del kardex después de cada movimiento.
- `pedidos.estado`: el estado actual. El historial completo está en `pedido_estados`.

Pendiente de integridad, que no es de normalización: agregar `UNIQUE(producto_id, tamano_id)` en `producto_presentacion` para impedir dos variantes iguales.

## Alcance y criterio

Análisis de las **14 tablas de negocio** de MySQL 8.0.46, con las 20 migraciones aplicadas. Fuentes: [diccionario real](diccionario-de-datos.md), [DER](der.md), modelos, requests, `CrearPedidoDesdeCarrito`, `CicloPedido` e `Inventario`. No se modificaron tablas ni registros. No se hace una auditoría de todas las filas existentes: se evalúan las dependencias que expresan el esquema y los flujos de la aplicación.

La normalización depende de las dependencias funcionales del dominio, no de que todas las tablas tengan una columna `id`:

- **1FN:** cada atributo contiene un valor de su dominio, sin grupos repetidos ni listas de claves en una celda.
- **2FN:** ningún atributo no primo depende de una parte propia de una clave candidata compuesta. No basta con mirar solo la PK si existen otras claves candidatas.
- **3FN:** para cada dependencia funcional no trivial X→A, X es superclave o A es primo (pertenece a alguna clave candidata). Un atributo calculado a partir de atributos no clave puede introducir una excepción, aunque se lo conserve por razones prácticas.

Las restricciones PK/UQ SQL permiten identificar claves declaradas. Las FK preservan referencias, pero **no prueban por sí mismas la 3FN**. Tampoco la prueban los casts, los índices no únicos o las validaciones de formularios. Se distingue lo documentado del dominio de lo que realmente impone MySQL.

## Evaluación por tabla

| Tabla | 1FN | 2FN | 3FN y observaciones |
|---|---|---|---|
| `roles` | Un nombre por fila, sin lista de permisos o usuarios. | PK simple `id`; no hay clave candidata compuesta declarada. | Los atributos dependen del rol. No se copian usuarios ni perfiles. `nombre` no tiene UQ; los nombres duplicados son un riesgo de integridad semántica, no una dependencia transitiva demostrada. |
| `usuarios` | Nombre, email, hash y rol son valores individuales. | `id` y email no nulo/único son identificadores simples; no hay atributos dependientes de parte de una clave compuesta. | `rol_id` referencia a roles sin copiar `roles.nombre`. Que rol_id determine un nombre en otra tabla no es una dependencia transitiva interna de usuarios. Compatible con 3FN para las dependencias observadas. |
| `clientes` | Un teléfono y una dirección del perfil; no hay listas de compras ni de direcciones. | `id` y `usuario_id` único/no nulo son identificadores simples. | Se separa el perfil comercial de la cuenta; no se copian nombre, email ni contraseña. Compatible con 3FN. No implementa un historial de varias direcciones: no se debe describir una tabla que no existe. |
| `marcas` | Nombre, slug, ruta de imagen y banderas individuales. | `id` y slug único/no nulo son claves simples. | Menú y marca dependen de la identidad de la marca. No se duplican productos dentro de la fila. `orden_menu` no es único y no determina la marca. Compatible con 3FN bajo las dependencias observadas. |
| `categorias` | Un nombre de familia por fila. | PK `id` simple; no se observa dependencia parcial. | No contiene nombres de marcas o productos que dependan de otras columnas no clave. Compatible con 3FN; un eventual UQ del nombre sería una decisión de integridad adicional. |
| `tamanos` | Un tamaño y su descripción por fila. | PK `id`; el nombre no tiene UQ y no se presume clave candidata SQL. | Se evita repetir la etiqueta del tamaño en cada presentación. Compatible con 3FN según la función del catálogo; falta unicidad de nombre si se quiere identificar un tamaño por su etiqueta. |
| `productos` | Perfume individual; las notas se trasladan a notas/nota_producto, no a una lista en productos. | `id` y slug son claves simples; marca y categoría no identifican por sí solas un perfume. | No copia los nombres de marca ni categoría: guarda sus FK. Descripción, imagen, género y publicación caracterizan al perfume. Compatible con 3FN para estas dependencias. |
| `producto_presentacion` | Cada fila representa una variante por producto/tamaño; precio, stock y activo son escalares. | PK real `id`. En la identidad natural pretendida producto+tamaño, precio y stock no dependen solo del producto ni solo del tamaño. No se observa una dependencia parcial; esa combinación no está protegida con UQ. | No se copian nombre del perfume ni etiqueta del tamaño. Compatible con 3FN bajo la identidad de variante prevista, pero no se certifica su unicidad de negocio: SQL admite dos filas con el mismo par. Ver pendiente. |
| `notas` | Una nota por fila, no una lista. | `id` y `nombre` único/no nulo son claves simples. | No hay atributos no clave que determinen otros atributos no clave. Compatible con 3FN. |
| `nota_producto` | Cada fila contiene una sola asociación entre dos entidades. | PK compuesta real `(producto_id, nota_id)` y ninguna otra columna: no hay atributos no primos que dependan de una parte. | Ambos atributos son primos; no se copian nombres de producto/nota. La asociación no introduce dependencias transitivas y satisface 3FN para el dominio representado. |
| `pedidos` | Una cabecera, un contacto y un destino de esta compra. Los ítems están en detalle_pedidos. | PK `id`; `numero` es identificador de aplicación pero la UQ nullable no garantiza una clave candidata no nula para todas las filas. Ningún importe depende de parte de una clave compuesta observada. | Contacto y entrega son snapshots históricos; no se presumen determinados por el cliente actual. Sin embargo `total = subtotal + costo_envio` es redundancia calculada: **excepción a 3FN estricta** con la dependencia de cálculo que mantiene el checkout. |
| `detalle_pedidos` | Una presentación y una cantidad por línea; no usa columnas item1/item2 ni listas de productos. | PK real `id`; el par pedido/presentación no es UQ y no se presume clave. Los datos propios de la línea no dependen solo del pedido ni solo de la presentación. | `precio_unitario` histórico no depende del precio actual de la presentación. Pero `subtotal = cantidad × precio_unitario` es redundancia derivada: **excepción a 3FN estricta** si se exige esa fórmula. |
| `pedido_estados` | Un estado ocurrido por fila; no hay columna con toda la secuencia concatenada. | PK `id`. `(pedido_id, created_at)` no es UQ y puede tener empates: no se inventa como clave. | Estado, instante y usuario describen el evento. No se duplica el nombre de usuario. Compatible con 3FN en el historial; la copia del último estado en pedidos es una materialización entre tablas. |
| `movimientos_inventario` | Un movimiento por presentación, con tipo, cantidad, motivo y auditor. | PK `id`; tipo o presentación no identifican por sí solos un movimiento. | El stock resultante es una materialización histórica del saldo, no una copia sincronizada del stock actual. No se deduce una FD interna tipo+cantidad→stock_resultante: falta el saldo anterior. No hay una dependencia transitiva interna demostrada, aunque existe redundancia deliberada frente al proceso de inventario. |

## Datos históricos y desnormalizaciones deliberadas

### Precio de la venta y subtotal de cada línea

`detalle_pedidos.precio_unitario` copia el valor de la presentación en el momento de comprar. Dos líneas de la misma presentación en fechas diferentes pueden tener precios diferentes. Por eso **presentacion_id no determina el precio histórico de todas las líneas**: no se debe reemplazarlo por un JOIN al precio actual ni llamarlo una dependencia transitiva respecto del precio vigente. Es un hecho histórico de la venta.

`subtotal` también congela el importe de la línea, pero hoy el código lo calcula con `cantidad × precio_unitario`, sin descuentos por línea. Con esa regla, `(cantidad, precio_unitario) → subtotal`; ese determinante no es superclave y subtotal no es primo. La decisión de persistir el importe facilita consultas y auditoría, pero **el motivo histórico no elimina su redundancia matemática**. Si se exige 3FN estricta según esa dependencia, esta tabla tiene una excepción que debe reconocerse.

Fuente concreta: `App\Actions\CrearPedidoDesdeCarrito::handle`. La columna precio_unitario es decimal, aunque el checkout actual convierte el precio a entero antes de copiarlo. La coherencia de precios fraccionarios es un pendiente de negocio/datos, no se cambia ni se disfraza como una mejora de normalización en este trabajo.

### Importes en la cabecera

`pedidos.subtotal` conserva la suma de los detalles y `costo_envio` la tarifa utilizada en esa compra. No se recalculan usando precios o tarifas actuales. La suma entre tablas es materialización para lectura y auditoría; no es por sí sola una FD transitiva interna de la cabecera.

En cambio, para los pedidos creados por el checkout se cumple `total = subtotal + costo_envio`. El par de importes no es superclave y determina otro atributo no primo. Es una **desnormalización calculada incompatible con afirmar 3FN estricta sin excepciones**. SQL no agrega CHECK para esa fórmula y el generador no la presenta como una garantía del servidor. La migración de checkout conservó los históricos usando subtotal=total y costo_envio=0.

### Saldo del kardex

`movimientos_inventario.stock_resultante` registra el saldo inmediatamente posterior al evento. `Inventario::registrar` bloquea la presentación, valida el movimiento, actualiza stock y crea la fila de auditoría dentro de una transacción. Un movimiento antiguo no cambia cuando una venta posterior modifica `producto_presentacion.stock`.

Es una materialización de la evolución del inventario para auditoría y consultas rápidas. No debe calcularse leyendo el stock actual ni suponerse que se reconstruye siempre sumando todo el kardex: registros importados o previos a la auditoría pueden carecer de saldo/usuario y no se inventó un movimiento inicial para cada dato histórico. Esta redundancia de proceso se documenta sin inventar una dependencia transitiva entre columnas de la misma fila.

### Contacto y destino del pedido

`nombre_contacto`, `telefono`, `cedula`, `email`, `metodo_entrega`, `ciudad`, `barrio`, `direccion` y `referencia` pertenecen a esta compra. Pueden diferir del perfil del cliente; también existen pedidos invitados con cliente_id NULL. Aunque parte del valor inicial pueda venir del perfil, **cliente_id no determina esos valores a través de todos los pedidos**. Son snapshots, no columnas que deban mantenerse sincronizadas con clientes/usuarios.

Así se preserva dónde y con quién se coordinó una entrega, incluso si la persona cambia su dirección o teléfono. Copiar un dato histórico no equivale automáticamente a violar 3FN; borrar esas columnas y obtener todo del perfil actual alteraría el significado de los pedidos anteriores. Los históricos anteriores al checkout ampliado pueden conservar contacto y entrega en NULL.

### Estado actual frente a historial

`pedidos.estado` permite filtrar sin buscar la última fila de pedido_estados. `CicloPedido` actualiza estado, historial y stock en una transacción. La materialización evita una consulta adicional, pero introduce una obligación de coherencia entre tablas. Pedidos históricos pueden no tener eventos, por lo que no debe fabricarse una equivalencia universal entre estado y última transición para todos los registros existentes.

## Pendientes propuestos, sin cambiar el esquema

1. **Si la evaluación exige 3FN estricta**, calcular `detalle_pedidos.subtotal` desde cantidad y precio_unitario en una consulta o vista y `pedidos.total` desde sus importes base, manteniendo los hechos históricos (precio unitario y tarifa acordada). Antes de eliminar columnas hay que auditar diferencias históricas: los importes persistidos pueden representar lo efectivamente cobrado. Si se conserva la materialización por razones comerciales, documentar la excepción y centralizar su mantenimiento; un CHECK puede mejorar coherencia pero no elimina la redundancia funcional.
2. **Identidad de las variantes:** auditar y deduplicar antes de proponer `UNIQUE(producto_id, tamano_id)`. No afirmar que ya existe. Esto evita dos filas para una variante, pero la falta de UQ no demuestra por sí sola una violación de 2FN/3FN.
3. **Catálogos semánticos:** revisar unicidad de nombres en roles, tamanos y categorias según las reglas deseadas. Hoy solo notas.nombre, slugs, usuarios.email y clientes.usuario_id tienen las restricciones documentadas. No añadir UQ sin revisar datos y requisitos.
4. **CHECK y validación:** género, estados, stock no negativo, tipo/cantidad del kardex y coherencia de importes se controlan en aplicación, en las clases citadas por el diccionario. Los CHECK apropiados podrían reforzar integridad en MySQL 8; no son sinónimo de normalización y no se crean en esta tarea.
5. **Precios fraccionarios y auditoría:** acordar la regla de importes del checkout y revisar históricos antes de recalcular saldos o totales. No rellenar datos faltantes como si fueran hechos conocidos.

## Tablas de infraestructura

Las 8 tablas existentes (`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `migrations`, `personal_access_tokens`) mantienen contratos del framework. Sus cargas serializadas y contadores no se usan para certificar 3FN del negocio ni se proponen cambios a Laravel. `password_reset_tokens` no existe en este esquema. `sessions` centraliza el estado entre nodos HAProxy; `personal_access_tokens` mantiene tokens polimórficos sin FK SQL al usuario.

## Conclusión

El modelo separa catálogo, variantes, notas, pedidos, ítems y auditoría en tablas distintas, cada una con su propia identidad. Ninguna tabla guarda listas, y las notas olfativas van en la tabla pivote `nota_producto`. Ningún atributo depende de una parte de una clave compuesta. Las referencias a marcas, categorías, tamaños, roles y usuarios se guardan como FK, sin copiar sus nombres.

Por eso el esquema está en **3FN, con dos excepciones deliberadas y documentadas**: `detalle_pedidos.subtotal` y `pedidos.total`. Son importes calculados que se guardan para leerlos rápido y para auditoría. Se calculan en un único lugar, `CrearPedidoDesdeCarrito`, al crear el pedido, y después no se modifican. Para cumplir 3FN estricta bastaría con calcularlos en una vista; la sección de pendientes explica qué hay que revisar antes.

Los datos históricos (precio de venta, contacto y entrega del pedido, y saldo del kardex) no son redundancia: representan hechos del momento y no deben reemplazarse por el valor actual.
