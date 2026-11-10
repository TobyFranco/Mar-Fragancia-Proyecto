# Diccionario de datos

Fuente: MySQL 8.0.46, base `mar_fragancia`. Generado por `php artisan docs:diccionario` con Schema::getTables/getColumns/getIndexes/getForeignKeys. No consulta datos de clientes ni modifica el esquema.

Tipos y nulabilidad son los del servidor; no se sustituyen por casts de Eloquent. `NULL / no declarado` significa que la introspección devuelve default nulo: no diferencia la omisión de DEFAULT del DEFAULT NULL explícito. Los valores no nulos reproducen el texto devuelto por MySQL. AUTO_INCREMENT se indica en la descripción. PK/UQ compuestas se identifican con su conjunto de columnas, no como unicidad individual.

## Tablas de negocio

### categorias

Familias de aroma asignadas a los productos.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador de la familia. Autoincremental. |
| nombre | varchar(100) | No | NULL / no declarado | — | Nombre de la familia de aroma. |
| created_at | timestamp | Sí | NULL / no declarado | — | Instante de creación de la fila. |
| updated_at | timestamp | Sí | NULL / no declarado | — | Instante de la última actualización de la fila. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- CategoriaController impide borrar familias usadas por productos; las FK también preservan las referencias.

### clientes

Perfil comercial opcional de una cuenta; cada usuario puede tener como máximo un perfil.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador del perfil. Autoincremental. |
| usuario_id | bigint unsigned | No | NULL / no declarado | UQ (usuario_id); FK → usuarios.id | Cuenta propietaria; la restricción única evita dos perfiles para la misma cuenta. |
| telefono | varchar(30) | Sí | NULL / no declarado | — | Teléfono del perfil que puede precargar el checkout. |
| direccion | varchar(255) | Sí | NULL / no declarado | — | Dirección del perfil que puede precargar el checkout. |
| created_at | timestamp | Sí | NULL / no declarado | — | Instante de creación de la fila. |
| updated_at | timestamp | Sí | NULL / no declarado | — | Instante de la última actualización de la fila. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| clientes_usuario_id_unique | usuario_id | Sí | btree | Impide repetir la combinación indexada; MySQL admite varios NULL si las columnas lo permiten. |
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

| Nombre | Columnas | Referencia | ON DELETE | ON UPDATE |
|---|---|---|---|---|
| clientes_usuario_id_foreign | usuario_id | mar_fragancia.usuarios (id) | NO ACTION | NO ACTION |

**Reglas y semántica de aplicación**

- Registro crea perfil con rol cliente; el UNIQUE(usuario_id) sí pertenece a la base. Checkout puede crear pedidos sin perfil.

### detalle_pedidos

Líneas de un pedido con cantidad y precio histórico.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador de la línea. Autoincremental. |
| pedido_id | bigint unsigned | No | NULL / no declarado | FK → pedidos.id | Pedido al que pertenece el ítem. |
| presentacion_id | bigint unsigned | No | NULL / no declarado | FK → producto_presentacion.id | Variante elegida en la compra. |
| cantidad | int | No | NULL / no declarado | — | Unidades solicitadas; el checkout exige entero positivo y stock suficiente. |
| precio_unitario | decimal(10,2) | No | NULL / no declarado | — | Precio de la variante copiado al confirmar el checkout; no sigue los cambios del precio actual. |
| subtotal | decimal(10,2) | No | NULL / no declarado | — | Importe de la línea; checkout lo calcula como cantidad por precio_unitario. |
| created_at | timestamp | No | CURRENT_TIMESTAMP | — | Instante de registro de la fila; no hay updated_at en esta tabla. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| detalle_pedidos_pedido_id_foreign | pedido_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| detalle_pedidos_presentacion_id_foreign | presentacion_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

| Nombre | Columnas | Referencia | ON DELETE | ON UPDATE |
|---|---|---|---|---|
| detalle_pedidos_pedido_id_foreign | pedido_id | mar_fragancia.pedidos (id) | NO ACTION | NO ACTION |
| detalle_pedidos_presentacion_id_foreign | presentacion_id | mar_fragancia.producto_presentacion (id) | NO ACTION | NO ACTION |

**Reglas y semántica de aplicación**

- CrearPedidoDesdeCarrito crea líneas con cantidad positiva y precio del momento. Subtotal=cantidad×precio_unitario; no existe CHECK SQL para esas condiciones. El checkout convierte el precio a entero: los precios fraccionarios admitidos en admin requieren revisar consistencia, sin cambiarla aquí.

### marcas

Casas de perfumes y su configuración en el menú de la tienda.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador de la marca. Autoincremental. |
| nombre | varchar(100) | No | NULL / no declarado | — | Nombre comercial de la casa. |
| created_at | timestamp | Sí | NULL / no declarado | — | Instante de creación de la fila. |
| updated_at | timestamp | Sí | NULL / no declarado | — | Instante de la última actualización de la fila. |
| slug | varchar(255) | No | NULL / no declarado | UQ (slug) | Clave pública legible y única para rutas por marca. |
| destacada | tinyint(1) | No | 0 | — | Indica inclusión entre las marcas destacadas del menú. |
| orden_menu | int | Sí | NULL / no declarado | — | Orden relativo del menú; puede ser nulo y no es único. |
| imagen | varchar(255) | Sí | NULL / no declarado | — | Ruta de la imagen de marca; no contiene el binario. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| marcas_slug_unique | slug | Sí | btree | Impide repetir la combinación indexada; MySQL admite varios NULL si las columnas lo permiten. |
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- MarcaController limita a ocho destacadas bajo transacción y bloqueo; no existe CHECK SQL para ese máximo. No borra marcas usadas por productos. Marca genera slug; SQL impone su unicidad.

### movimientos_inventario

Kardex de entradas y salidas por presentación.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador del movimiento. Autoincremental. |
| presentacion_id | bigint unsigned | No | NULL / no declarado | FK → producto_presentacion.id | Variante cuyo inventario cambió. |
| tipo | varchar(20) | No | NULL / no declarado | — | entrada o salida según Inventario y MovimientoInventarioRequest; SQL es varchar. |
| cantidad | int | No | NULL / no declarado | — | Unidades positivas del movimiento; el signo lo determina tipo. |
| fecha | datetime | No | NULL / no declarado | — | Instante de negocio del movimiento en UTC mediante FechaUtc. |
| motivo | varchar(255) | Sí | NULL / no declarado | — | Justificación del ajuste; SQL permite nulo, la aplicación lo exige para movimientos nuevos. |
| created_at | timestamp | No | CURRENT_TIMESTAMP | — | Instante de registro de la fila; no hay updated_at en esta tabla. |
| usuario_id | bigint unsigned | Sí | NULL / no declarado | FK → usuarios.id | Cuenta responsable; nullable para registros históricos o de sistema. |
| stock_resultante | int | Sí | NULL / no declarado | — | Stock inmediatamente posterior al movimiento; nullable para auditoría anterior a su incorporación. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| movimientos_inventario_presentacion_id_foreign | presentacion_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| movimientos_inventario_usuario_id_foreign | usuario_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

| Nombre | Columnas | Referencia | ON DELETE | ON UPDATE |
|---|---|---|---|---|
| movimientos_inventario_presentacion_id_foreign | presentacion_id | mar_fragancia.producto_presentacion (id) | NO ACTION | NO ACTION |
| movimientos_inventario_usuario_id_foreign | usuario_id | mar_fragancia.usuarios (id) | NO ACTION | NO ACTION |

**Reglas y semántica de aplicación**

- Inventario y MovimientoInventarioRequest: tipo entrada/salida, cantidad entera positiva hasta 2147483647, motivo no vacío y presentación de 5/10 ml. No son enum/CHECK SQL.
- Inventario modifica stock y crea kardex en la misma transacción. stock_resultante es auditoría histórica; no debe confundirse con el stock actual.

### nota_producto

Asociación muchos a muchos entre perfume y nota, sin columnas con listas.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| producto_id | bigint unsigned | No | NULL / no declarado | PK (producto_id, nota_id); FK → productos.id | Perfume asociado; parte de la PK compuesta. |
| nota_id | bigint unsigned | No | NULL / no declarado | PK (producto_id, nota_id); FK → notas.id | Nota asociada; parte de la PK compuesta. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| nota_producto_nota_id_foreign | nota_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| primary | producto_id, nota_id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

| Nombre | Columnas | Referencia | ON DELETE | ON UPDATE |
|---|---|---|---|---|
| nota_producto_nota_id_foreign | nota_id | mar_fragancia.notas (id) | CASCADE | NO ACTION |
| nota_producto_producto_id_foreign | producto_id | mar_fragancia.productos (id) | CASCADE | NO ACTION |

**Reglas y semántica de aplicación**

- La PK compuesta evita duplicar la misma asociación. Cada FK real elimina la asociación en cascada al borrar su entidad.

### notas

Notas olfativas reutilizables para búsqueda, afinidad y termómetro.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador de la nota. Autoincremental. |
| nombre | varchar(255) | No | NULL / no declarado | UQ (nombre) | Nombre único de la nota; el admin reutiliza nombres normalizados. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| notas_nombre_unique | nombre | Sí | btree | Impide repetir la combinación indexada; MySQL admite varios NULL si las columnas lo permiten. |
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- ProductoController normaliza nombres para reutilizar notas; SQL solo garantiza UNIQUE(nombre) según la collation del campo.

### pedido_estados

Historial de estados del pedido; pedidos históricos pueden no tener filas.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador de la transición registrada. Autoincremental. |
| pedido_id | bigint unsigned | No | NULL / no declarado | FK → pedidos.id | Pedido cuyo cambio se registra. |
| estado | varchar(30) | No | NULL / no declarado | — | Estado al que se pasó; comparte los valores de Pedido::ESTADOS. |
| usuario_id | bigint unsigned | Sí | NULL / no declarado | FK → usuarios.id | Cuenta que generó el registro; nula para acciones de sistema o checkout invitado. |
| created_at | timestamp | No | CURRENT_TIMESTAMP | — | Instante de registro de la fila; no hay updated_at en esta tabla. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| pedido_estados_pedido_id_foreign | pedido_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| pedido_estados_usuario_id_foreign | usuario_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

| Nombre | Columnas | Referencia | ON DELETE | ON UPDATE |
|---|---|---|---|---|
| pedido_estados_pedido_id_foreign | pedido_id | mar_fragancia.pedidos (id) | NO ACTION | NO ACTION |
| pedido_estados_usuario_id_foreign | usuario_id | mar_fragancia.usuarios (id) | NO ACTION | NO ACTION |

**Reglas y semántica de aplicación**

- CicloPedido registra transiciones válidas y usuario junto con el cambio del pedido. CrearPedidoDesdeCarrito registra pendiente; los históricos sin auditoría no se rellenaron ficticiamente.

### pedidos

Cabecera y fotografía comercial del pedido, incluso para compra sin cuenta.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador interno del pedido. Autoincremental. |
| cliente_id | bigint unsigned | Sí | NULL / no declarado | FK → clientes.id | Perfil asociado si se compró con cuenta; puede ser nulo. |
| fecha | datetime | No | NULL / no declarado | — | Instante de creación del pedido; FechaUtc conserva UTC en la persistencia. |
| estado | varchar(30) | No | pendiente | — | Estado actual: pendiente, confirmado, preparando, enviado, entregado o cancelado; validado en Pedido/CicloPedido, no en SQL. |
| total | decimal(10,2) | No | NULL / no declarado | — | Importe total persistido; en checkout equivale a subtotal más costo_envio. |
| observacion | varchar(255) | Sí | NULL / no declarado | — | Observación opcional del pedido. |
| created_at | timestamp | Sí | NULL / no declarado | — | Instante de creación de la fila. |
| updated_at | timestamp | Sí | NULL / no declarado | — | Instante de la última actualización de la fila. |
| numero | varchar(255) | Sí | NULL / no declarado | UQ (numero) | Número público MF- seguido del id con al menos cuatro dígitos; único pero nullable en SQL. |
| nombre_contacto | varchar(255) | Sí | NULL / no declarado | — | Nombre copiado al realizar la compra; no se sincroniza con el perfil. |
| telefono | varchar(30) | Sí | NULL / no declarado | — | Celular de contacto del pedido, normalizado por CheckoutRequest a 09 seguido de ocho dígitos. |
| cedula | varchar(255) | Sí | NULL / no declarado | — | Documento de contacto opcional; se guarda como texto. |
| email | varchar(255) | Sí | NULL / no declarado | — | Email opcional de contacto de esta compra. |
| metodo_entrega | enum('retiro','asuncion','interior') | Sí | NULL / no declarado | — | retiro, asuncion o interior; enum real y nullable. |
| ciudad | varchar(255) | Sí | NULL / no declarado | — | Ciudad de entrega histórica. |
| barrio | varchar(255) | Sí | NULL / no declarado | — | Barrio de entrega opcional. |
| direccion | varchar(255) | Sí | NULL / no declarado | — | Dirección copiada para esta entrega. |
| referencia | varchar(255) | Sí | NULL / no declarado | — | Referencia de ubicación opcional. |
| subtotal | decimal(10,2) | No | 0.00 | — | Suma persistida de los subtotales de los ítems al crear el pedido. |
| costo_envio | decimal(10,2) | No | 0.00 | — | Tarifa persistida de esta compra; para interior se coordina y el checkout registra cero. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| pedidos_cliente_id_foreign | cliente_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| pedidos_numero_unique | numero | Sí | btree | Impide repetir la combinación indexada; MySQL admite varios NULL si las columnas lo permiten. |
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

| Nombre | Columnas | Referencia | ON DELETE | ON UPDATE |
|---|---|---|---|---|
| pedidos_cliente_id_foreign | cliente_id | mar_fragancia.clientes (id) | NO ACTION | NO ACTION |

**Reglas y semántica de aplicación**

- Pedido::ESTADOS y CicloPedido: pendiente→confirmado→preparando→enviado→entregado; cancelación solo desde pendiente/confirmado/preparando. Sin enum/CHECK SQL para estado.
- CicloPedido confirma descontando stock y auditando en una transacción; cancelar confirmado/preparando devuelve stock; cancelar pendiente no mueve inventario.
- CrearPedidoDesdeCarrito valida stock bajo bloqueo, copia precios/datos de entrega y guarda pendiente; no descuenta stock en checkout. Total=subtotal+costo_envio.
- CheckoutRequest valida entrega y celular; las filas históricas pueden tener contacto/entrega NULL. Pedido genera numero tras insertar, aunque SQL permite NULL.

### producto_presentacion

Variantes de un perfume por tamaño, con precio y stock actuales.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador de la variante. Autoincremental. |
| producto_id | bigint unsigned | No | NULL / no declarado | FK → productos.id | Perfume del que se fracciona el decant. |
| tamano_id | bigint unsigned | No | NULL / no declarado | FK → tamanos.id | Tamaño de la presentación. |
| precio | decimal(10,2) | No | NULL / no declarado | — | Precio actual de esta variante; el checkout copia el precio al detalle histórico. |
| stock | int | No | 0 | — | Unidades disponibles actuales; INT con signo, sin CHECK de no negatividad. |
| activo | tinyint(1) | No | 1 | — | Habilita esta presentación independientemente del producto. |
| created_at | timestamp | Sí | NULL / no declarado | — | Instante de creación de la fila. |
| updated_at | timestamp | Sí | NULL / no declarado | — | Instante de la última actualización de la fila. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| primary | id | Sí | btree | Identifica filas por la clave primaria. |
| producto_presentacion_producto_id_foreign | producto_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| producto_presentacion_tamano_id_foreign | tamano_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |

**Claves foráneas**

| Nombre | Columnas | Referencia | ON DELETE | ON UPDATE |
|---|---|---|---|---|
| producto_presentacion_producto_id_foreign | producto_id | mar_fragancia.productos (id) | NO ACTION | NO ACTION |
| producto_presentacion_tamano_id_foreign | tamano_id | mar_fragancia.tamanos (id) | NO ACTION | NO ACTION |

**Reglas y semántica de aplicación**

- Inventario mantiene stock no negativo bajo bloqueo de fila; rechaza salidas insuficientes y entradas superiores a STOCK_MAXIMO=2147483647. No existe CHECK SQL para stock≥0.
- ProductoRequest: precio no negativo, hasta dos decimales y 99999999.99; stock inicial no negativo. La edición del producto no cambia stock: se usa Inventario.
- No existe UNIQUE(producto_id,tamano_id). El panel busca/reutiliza variantes por tamaño, pero SQL permite duplicados.

### productos

Perfumes del catálogo, con marca, familia, género y publicación.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador del perfume. Autoincremental. |
| marca_id | bigint unsigned | No | NULL / no declarado | FK → marcas.id | Casa a la que pertenece el perfume. |
| categoria_id | bigint unsigned | No | NULL / no declarado | FK → categorias.id | Familia de aroma del perfume. |
| nombre | varchar(150) | No | NULL / no declarado | — | Nombre del perfume. |
| descripcion | text | Sí | NULL / no declarado | — | Descripción extensa opcional. |
| imagen | varchar(255) | Sí | NULL / no declarado | — | Ruta de la foto del perfume en el almacenamiento público. |
| activo | tinyint(1) | No | 1 | — | Controla si el perfume puede mostrarse y venderse. |
| created_at | timestamp | Sí | NULL / no declarado | — | Instante de creación de la fila. |
| updated_at | timestamp | Sí | NULL / no declarado | — | Instante de la última actualización de la fila. |
| genero | varchar(10) | No | unisex | — | mujer, hombre o unisex según Producto::GENEROS; SQL es varchar. |
| slug | varchar(255) | No | NULL / no declarado | UQ (slug) | Identificador público legible, obligatorio y único. |
| destacado | tinyint(1) | No | 0 | — | Destaca el producto en la portada. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| primary | id | Sí | btree | Identifica filas por la clave primaria. |
| productos_categoria_id_foreign | categoria_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| productos_marca_id_foreign | marca_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| productos_slug_unique | slug | Sí | btree | Impide repetir la combinación indexada; MySQL admite varios NULL si las columnas lo permiten. |

**Claves foráneas**

| Nombre | Columnas | Referencia | ON DELETE | ON UPDATE |
|---|---|---|---|---|
| productos_categoria_id_foreign | categoria_id | mar_fragancia.categorias (id) | NO ACTION | NO ACTION |
| productos_marca_id_foreign | marca_id | mar_fragancia.marcas (id) | NO ACTION | NO ACTION |

**Reglas y semántica de aplicación**

- Producto::GENEROS y evento saving: mujer, hombre o unisex; no existe enum/CHECK para genero.
- Producto::visibles: activo y al menos una presentación activa de 5/10 ml; stock cero no oculta por sí solo.
- ProductoController bloquea borrado si hay pedidos o movimientos y ofrece ocultar. ProductoRequest valida marca/categoría y exige al menos un precio.

### roles

Roles utilizados para autorizar el panel y la API.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador del rol. Autoincremental. |
| nombre | varchar(50) | No | NULL / no declarado | — | Nombre del rol; la aplicación utiliza administrador y cliente. |
| created_at | timestamp | Sí | NULL / no declarado | — | Instante de creación de la fila. |
| updated_at | timestamp | Sí | NULL / no declarado | — | Instante de la última actualización de la fila. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- CheckRole y AuthController utilizan los nombres administrador y cliente. SQL admite otros nombres y no declara UNIQUE(nombre).

### tamanos

Catálogo de tamaños de presentación; la tienda utiliza las filas llamadas 5 ml y 10 ml.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador del tamaño. Autoincremental. |
| nombre | varchar(20) | No | NULL / no declarado | — | Etiqueta del tamaño, por ejemplo 5 ml; no es enum ni única en SQL. |
| descripcion | varchar(100) | Sí | NULL / no declarado | — | Descripción opcional del tamaño. |
| created_at | timestamp | Sí | NULL / no declarado | — | Instante de creación de la fila. |
| updated_at | timestamp | Sí | NULL / no declarado | — | Instante de la última actualización de la fila. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- TamanoController ofrece solo lectura. Producto::visibles, ProductoRequest y MovimientoInventarioRequest usan nombres 5 ml y 10 ml; SQL no restringe el catálogo a esos valores.

### usuarios

Cuentas que autentican en la web y mediante tokens Sanctum.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador de la cuenta. Autoincremental. |
| rol_id | bigint unsigned | No | NULL / no declarado | FK → roles.id | Rol asignado a la cuenta. |
| nombre | varchar(100) | No | NULL / no declarado | — | Nombre de la persona. |
| email | varchar(150) | No | NULL / no declarado | UQ (email) | Correo de acceso único. |
| password | varchar(255) | No | NULL / no declarado | — | Hash de contraseña escrito por registro y seeders; oculto en la serialización del modelo. |
| created_at | timestamp | Sí | NULL / no declarado | — | Instante de creación de la fila. |
| updated_at | timestamp | Sí | NULL / no declarado | — | Instante de la última actualización de la fila. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| primary | id | Sí | btree | Identifica filas por la clave primaria. |
| usuarios_email_unique | email | Sí | btree | Impide repetir la combinación indexada; MySQL admite varios NULL si las columnas lo permiten. |
| usuarios_rol_id_foreign | rol_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |

**Claves foráneas**

| Nombre | Columnas | Referencia | ON DELETE | ON UPDATE |
|---|---|---|---|---|
| usuarios_rol_id_foreign | rol_id | mar_fragancia.roles (id) | NO ACTION | NO ACTION |

**Reglas y semántica de aplicación**

- AuthController valida registro y crea el hash; API/V1/AuthController verifica Hash::check. Usuario oculta password al serializar.

## Tablas de infraestructura

### cache

Valores de caché compartidos entre ambas arquitecturas.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| key | varchar(255) | No | NULL / no declarado | PK (key) | Clave primaria de la entrada de caché. |
| value | mediumtext | No | NULL / no declarado | — | Valor serializado de la entrada. |
| expiration | bigint | No | NULL / no declarado | — | Instante de expiración en segundos Unix; tiene índice. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| cache_expiration_index | expiration | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| primary | key | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- No se agregan reglas de negocio específicas a las restricciones y al comportamiento del framework documentados arriba.

### cache_locks

Bloqueos de caché compartidos para coordinar operaciones como checkout.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| key | varchar(255) | No | NULL / no declarado | PK (key) | Clave primaria del bloqueo. |
| owner | varchar(255) | No | NULL / no declarado | — | Token identificador del propietario del bloqueo. |
| expiration | bigint | No | NULL / no declarado | — | Instante de caducidad del bloqueo en segundos Unix. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| cache_locks_expiration_index | expiration | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| primary | key | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- No se agregan reglas de negocio específicas a las restricciones y al comportamiento del framework documentados arriba.

### failed_jobs

Registro de trabajos fallidos para diagnóstico y reintento.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador interno del fallo. Autoincremental. |
| uuid | varchar(255) | No | NULL / no declarado | UQ (uuid) | Identificador único del trabajo fallido. |
| connection | varchar(255) | No | NULL / no declarado | — | Conexión de cola utilizada. |
| queue | varchar(255) | No | NULL / no declarado | — | Nombre de la cola. |
| payload | longtext | No | NULL / no declarado | — | Carga serializada del trabajo fallido. |
| exception | longtext | No | NULL / no declarado | — | Detalle técnico de la excepción. |
| failed_at | timestamp | No | CURRENT_TIMESTAMP | — | Instante de registro del fallo, por defecto CURRENT_TIMESTAMP. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| failed_jobs_connection_queue_failed_at_index | connection, queue, failed_at | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| failed_jobs_uuid_unique | uuid | Sí | btree | Impide repetir la combinación indexada; MySQL admite varios NULL si las columnas lo permiten. |
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- No se agregan reglas de negocio específicas a las restricciones y al comportamiento del framework documentados arriba.

### job_batches

Metadatos y contadores de lotes de trabajos Laravel.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | varchar(255) | No | NULL / no declarado | PK (id) | Identificador textual del lote. |
| name | varchar(255) | No | NULL / no declarado | — | Nombre del lote. |
| total_jobs | int | No | NULL / no declarado | — | Cantidad total de trabajos del lote. |
| pending_jobs | int | No | NULL / no declarado | — | Cantidad todavía pendiente. |
| failed_jobs | int | No | NULL / no declarado | — | Cantidad de trabajos fallidos. |
| failed_job_ids | longtext | No | NULL / no declarado | — | Identificadores serializados de trabajos fallidos. |
| options | mediumtext | Sí | NULL / no declarado | — | Opciones serializadas del lote. |
| cancelled_at | int | Sí | NULL / no declarado | — | Instante Unix de cancelación si ocurrió. |
| created_at | int | No | NULL / no declarado | — | Instante Unix de creación del lote. |
| finished_at | int | Sí | NULL / no declarado | — | Instante Unix de finalización si terminó. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- No se agregan reglas de negocio específicas a las restricciones y al comportamiento del framework documentados arriba.

### jobs

Cola de trabajos pendientes del driver database.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador del trabajo. Autoincremental. |
| queue | varchar(255) | No | NULL / no declarado | — | Cola a la que pertenece. |
| payload | longtext | No | NULL / no declarado | — | Carga serializada del trabajo. |
| attempts | smallint unsigned | No | NULL / no declarado | — | Cantidad de intentos de ejecución. |
| reserved_at | int unsigned | Sí | NULL / no declarado | — | Instante Unix de reserva, si está reservado. |
| available_at | int unsigned | No | NULL / no declarado | — | Instante Unix desde el que puede ejecutarse. |
| created_at | int unsigned | No | NULL / no declarado | — | Instante Unix de encolado; es entero, no timestamp SQL. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| jobs_queue_index | queue | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- No se agregan reglas de negocio específicas a las restricciones y al comportamiento del framework documentados arriba.

### migrations

Control de versiones de migraciones aplicado por Laravel; la tabla la crea el repositorio de migraciones.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | int unsigned | No | NULL / no declarado | PK (id) | Identificador del registro de migración. Autoincremental. |
| migration | varchar(255) | No | NULL / no declarado | — | Nombre del archivo de migración sin extensión. |
| batch | int | No | NULL / no declarado | — | Lote de ejecución de la migración. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- No se agregan reglas de negocio específicas a las restricciones y al comportamiento del framework documentados arriba.

### personal_access_tokens

Tokens personales Sanctum compartidos por ambas arquitecturas; el token en claro se entrega solo al emitirlo.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | bigint unsigned | No | NULL / no declarado | PK (id) | Identificador del token. Autoincremental. |
| tokenable_type | varchar(255) | No | NULL / no declarado | — | Clase del modelo autenticable en la relación polimórfica. |
| tokenable_id | bigint unsigned | No | NULL / no declarado | — | Id del modelo autenticable; no tiene FK SQL por ser una relación polimórfica. |
| name | text | No | NULL / no declarado | — | Nombre del dispositivo o uso del token. |
| token | varchar(64) | No | NULL / no declarado | UQ (token) | Hash SHA-256 del token, de 64 caracteres y único. |
| abilities | text | Sí | NULL / no declarado | — | Permisos serializados del token. |
| last_used_at | timestamp | Sí | NULL / no declarado | — | Último uso autenticado registrado por Sanctum. |
| expires_at | timestamp | Sí | NULL / no declarado | — | Caducidad opcional del token; indexada. |
| created_at | timestamp | Sí | NULL / no declarado | — | Instante de creación de la fila. |
| updated_at | timestamp | Sí | NULL / no declarado | — | Instante de la última actualización de la fila. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| personal_access_tokens_expires_at_index | expires_at | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| personal_access_tokens_token_unique | token | Sí | btree | Impide repetir la combinación indexada; MySQL admite varios NULL si las columnas lo permiten. |
| personal_access_tokens_tokenable_type_tokenable_id_index | tokenable_type, tokenable_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| primary | id | Sí | btree | Identifica filas por la clave primaria. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- Sanctum autentica Bearer contra esta tabla; config/sanctum.php no acepta la sesión web como sustituto. tokenable es relación polimórfica de Eloquent, no FK SQL.

### sessions

Sesiones de Laravel centralizadas en MySQL para conservar login y carrito al balancear con HAProxy.

| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |
|---|---|---|---|---|---|
| id | varchar(255) | No | NULL / no declarado | PK (id) | Identificador de sesión; clave primaria textual. |
| user_id | bigint unsigned | Sí | NULL / no declarado | — | Identificador opcional de usuarios; está indexado pero NO tiene FK. |
| ip_address | varchar(45) | Sí | NULL / no declarado | — | IP del cliente si está disponible. |
| user_agent | text | Sí | NULL / no declarado | — | Agente de usuario declarado por el cliente. |
| payload | longtext | No | NULL / no declarado | — | Contenido serializado de la sesión. |
| last_activity | int | No | NULL / no declarado | — | Última actividad en segundos Unix; índice para caducidad. |

**Índices** (nombres normalizados por Laravel)

| Nombre | Columnas | Único | Tipo | Utilidad |
|---|---|---|---|---|
| primary | id | Sí | btree | Identifica filas por la clave primaria. |
| sessions_last_activity_index | last_activity | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |
| sessions_user_id_index | user_id | No | btree | Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador. |

**Claves foráneas**

No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.

**Reglas y semántica de aplicación**

- SESSION_DRIVER=database; ambas arquitecturas comparten base y APP_KEY. user_id se refiere lógicamente a usuarios.id, sin FK SQL.

Tablas de infraestructura contempladas pero ausentes del esquema inspeccionado: password_reset_tokens. No se generan filas ni relaciones para tablas inexistentes.
