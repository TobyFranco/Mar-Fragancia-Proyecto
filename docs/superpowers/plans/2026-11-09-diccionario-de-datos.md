# Plan · MER/DER, diccionario de datos y 3FN (Avance 1 y 4)

**Rama:** `feature/diccionario-datos`, creada desde `main` actualizado. **No hacer push**: Claude revisa y pushea.

Trabajá por tareas y hacé **un commit al terminar cada una**, con los tests en verde.

## Objetivo

La sección 3.B del PDF de la materia pide:

- **Diagrama MER / DER:** el diagrama conceptual y lógico de la base de datos.
- **Diccionario de datos:** las tablas, campos, tipos, PK, FK, restricciones e índices de optimización.
- **Normalización:** la justificación de que la estructura está en Tercera Forma Normal (3FN).

Todo tiene que salir del **esquema real**, es decir, de las 20 migraciones de `src/database/migrations` aplicadas en MySQL 8. No se inventa nada.

## Tareas

### 1. Generador del diccionario, sin inventar datos

Crear un comando Artisan `docs:diccionario` que lea el esquema real con:

- `Schema::getTables()`
- `Schema::getColumns($tabla)`
- `Schema::getIndexes($tabla)`
- `Schema::getForeignKeys($tabla)`

El comando escribe `docs/datos/diccionario-de-datos.md`.

**Descripciones.** Las descripciones en español de cada tabla y de cada columna van en `docs/datos/descripciones.php`, un array `tabla => ['_tabla' => '...', 'columna' => '...']`. Escribilas leyendo los modelos, las migraciones y el uso real en el código. Por ejemplo, qué estados admite `pedidos.estado` o qué significa `stock_resultante`.

**Para cada tabla, el documento incluye:**

- la descripción;
- una tabla con estas columnas: Campo, Tipo MySQL, Nulo, Default, Clave (PK / FK → tabla.columna / UQ) y Descripción;
- los índices, con su nombre, columnas, si es único y para qué sirven;
- las FK, con sus acciones ON DELETE y ON UPDATE;
- las restricciones de negocio que aplica la aplicación y no la base, como "solo 5 ml y 10 ml" o "stock ≥ 0 validado en `Inventario`". Indicá en qué clase está cada una.

**Tablas del framework.** Las tablas de Laravel (`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `migrations` y `personal_access_tokens`) van en una sección aparte, "Tablas de infraestructura", con una descripción breve. Mencioná que `sessions` es la que centraliza las sesiones para el balanceo con HAProxy.

**Tipos.** Los tipos exactos se toman de **MySQL**, no de SQLite. Corré el comando dentro de Docker:

```bash
sg docker -c "docker compose exec -T app-fpm php artisan docs:diccionario"
```

### 2. Diagrama DER

Archivo `docs/datos/der.md` con un `erDiagram` de Mermaid, que GitHub muestra como diagrama. Incluí:

- todas las entidades de negocio con sus atributos (tipo, PK, FK);
- las relaciones con la cardinalidad real. Por ejemplo, la tabla intermedia `producto_presentacion` (producto × tamaño), `producto_nota` o la tabla pivote real, y `pedido_estados` como historial.

**Dos niveles:**

- **conceptual:** solo entidades y relaciones, con verbos en español;
- **lógico:** con atributos y claves.

**Exportación.** Exportá también un PNG o SVG del lógico a `docs/datos/der-logico.svg` para la presentación. Usá `npx @mermaid-js/mermaid-cli` con una versión fija, con Node de `~/.local/node/bin`. Si no se puede exportar, dejalo explicado y no lo inventes.

### 3. Justificación de 3FN

Archivo `docs/datos/normalizacion.md`. Para cada tabla de negocio:

- **1FN:** valores atómicos y sin grupos repetidos. Por ejemplo, las notas olfativas están en una tabla pivote y no en una columna con listas.
- **2FN:** sin dependencias parciales de una PK compuesta. Revisar las tablas intermedias.
- **3FN:** sin dependencias transitivas.

**Desnormalizaciones deliberadas.** Si hay redundancia intencional, documentala con su justificación:

- `detalle_pedidos.precio_unitario` y `subtotal` guardan el precio del momento de la venta, que es un dato histórico y no una dependencia transitiva;
- `pedidos.total`, `subtotal` y `costo_envio`;
- `movimientos_inventario.stock_resultante`;
- los datos de contacto y de entrega copiados en `pedidos`.

**No afirmes 3FN donde no se cumple.** Si encontrás una violación real, explicala y proponé la corrección como pendiente, sin cambiar el esquema en esta tarea.

### 4. Test

Agregar `tests/Feature/Documentacion/DiccionarioDatosTest.php`, que compruebe que:

- cada tabla y cada columna del esquema migrado (en SQLite de test, por nombres) tiene descripción en `descripciones.php`;
- `diccionario-de-datos.md` menciona todas las tablas de negocio.

Así, si alguien agrega una columna y no la documenta, el test falla.

### 5. README

- Agregar a la tabla "Documentación" el DER, el diccionario y la normalización.
- Agregar un párrafo con cómo regenerar el diccionario.

## Verificación

- `cd src && vendor/bin/pint --dirty --format agent && php artisan test --compact` en verde.
- El diccionario se genera de verdad contra MySQL en Docker y se commitea esa salida.
- Commits chicos en español, sin atribución a IA. **No pushear.**
- Al terminar, resumí qué tablas hay, si el esquema cumple 3FN y qué desnormalizaciones documentaste.

## Progreso de ejecución

- [x] Tarea 1: comando docs:diccionario, descripciones explícitas y salida real de MySQL8.0.46. 22 tablas, 148 columnas, 15 FK. Rechaza SQLite antes de escribir (RED2→GREEN2). Suite320/2822 y Pint verdes. Montaje docs→/var/docs agregado solo a app-fpm porque el volumen previo incluía únicamente src; sin cambios de esquema/datos.
- [x] Tarea 2: DER conceptual y lógico,14 entidades/99 atributos/15 FK, cardinalidades por nullability y UQ. SVG real exportado con Mermaid CLI11.12.0, Node24.22.0. Ruta Node del plan ausente: se usó ~/.local/bin; Puppeteer requirió --no-sandbox solo para el archivo local. Suite320/2822 verde.
- [x] Tarea 3: evaluación1FN/2FN/3FN por cada tabla, snapshots y saldos históricos, excepciones de importes derivados y pendientes de unicidad/integridad. No se certifica3FN estricta de todo el esquema ni se modifica SQL. Suite320/2822 verde.
- [ ] Tarea 4: regresión de cobertura documental.
- [ ] Tarea 5: README y cierre.
