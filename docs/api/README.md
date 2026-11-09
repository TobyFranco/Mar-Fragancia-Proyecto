# API REST v1 · Mar Fragancias

API JSON con tres usos:

- **Catálogo público:** decants, marcas y familias de aroma.
- **Consulta de pedidos de la clienta:** el estado de cada pedido en tiempo real.
- **Gestión para el administrador:** pedidos e inventario.

La especificación completa está en [`openapi.yaml`](openapi.yaml) (OpenAPI 3.1). Se puede abrir en [Swagger Editor](https://editor.swagger.io/) o importar en Postman o Insomnia.

## Conceptos básicos

| | |
|---|---|
| URL base | `http://localhost/api/v1`, a través de HAProxy |
| Formato | JSON en todas las respuestas, también en los errores |
| Autenticación | Token personal de Laravel Sanctum en la cabecera `Authorization: Bearer <token>` |
| Límite de uso | 60 solicitudes por minuto por usuario, o por IP si no hay token. El login permite 5 intentos por minuto por IP y email |
| Paginación | 20 elementos por página, con `?page=N`. Los listados devuelven `data`, `links` y `meta` |

Los tokens se guardan en MySQL. Por eso un mismo token funciona en las dos arquitecturas del balanceador, `nginx-php-fpm` y `apache-mod-php`. La cabecera `X-Servido-Por` de cada respuesta dice cuál respondió.

## Códigos de respuesta

| Código | Cuándo | Cuerpo |
|---|---|---|
| 200 / 201 | Todo bien (201 al crear un movimiento) | `{"data": ...}` |
| 401 | Falta el token o no es válido | `{"message": "Unauthenticated."}` |
| 403 | El usuario no tiene el rol necesario | `{"message": ...}` |
| 404 | El recurso no existe, el producto está oculto o el pedido es de otra persona | `{"message": "Recurso no encontrado."}` |
| 422 | Error de validación o una regla de negocio no se cumple | `{"message": ..., "errors": {"campo": [...]}}` |
| 429 | Se superó el límite de uso | `{"message": "Too Many Attempts."}` |

## Autenticación

### `POST /auth/login`

```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H 'Accept: application/json' \
  -d email=mariapaz@example.com -d password=password123 -d device_name=mi-celular
```

```json
{
  "token": "3|Xk9...",
  "usuario": {
    "id": 2,
    "nombre": "Maria Paz Gutierrez",
    "email": "mariapaz@example.com",
    "rol": { "id": 2, "nombre": "cliente" },
    "cliente": { "id": 1, "telefono": "0993957652", "direccion": "Asunción, Paraguay" }
  }
}
```

- Con credenciales incorrectas responde **422**, con `errors.email`.
- El token se muestra **una sola vez**. Guardalo para usarlo en las siguientes solicitudes.

### `GET /auth/me`

Devuelve el usuario dueño del token, con su rol y su perfil de cliente.

```bash
curl http://localhost/api/v1/auth/me -H "Authorization: Bearer $TOKEN"
```

### `POST /auth/logout`

Revoca el token actual. Si se usa después, responde 401.

```bash
curl -X POST http://localhost/api/v1/auth/logout -H "Authorization: Bearer $TOKEN"
```

## Catálogo público

Estos endpoints no necesitan token. Solo muestran productos visibles con decants de 5 ml y 10 ml activos.

### `GET /marcas`

Marcas ordenadas por nombre, con la cantidad de productos visibles de cada una.

```json
{ "data": [ { "id": 15, "slug": "armaf", "nombre": "Armaf", "productos_visibles": 4 } ], "links": {...}, "meta": {...} }
```

### `GET /categorias`

Familias de aroma: Amaderada, Cítrica, Dulce, Floral, Fresca y Oriental.

### `GET /productos`

| Filtro | Ejemplo | Descripción |
|---|---|---|
| `marca` | `chanel` | Slug de la marca |
| `genero` | `mujer`, `hombre`, `unisex` | |
| `categoria` | `4` | ID de la familia de aroma |
| `tamano` | `5` o `10` | Solo productos con stock en ese decant |
| `q` | `vainilla` | Busca en nombre, marca y notas, sin distinguir tildes ni mayúsculas |

```bash
curl 'http://localhost/api/v1/productos?marca=chanel&genero=hombre'
```

### `GET /productos/{slug}`

Ficha completa. Si el producto no existe o está oculto, responde 404.

```bash
curl http://localhost/api/v1/productos/coromandel
```

```json
{
  "data": {
    "id": 38,
    "slug": "coromandel",
    "nombre": "Coromandel",
    "descripcion": "Un perfume con notas de pachuli, incienso, iris, vainilla.",
    "genero": "unisex",
    "marca": { "id": 1, "slug": "chanel", "nombre": "Chanel" },
    "categoria": { "id": 4, "nombre": "Oriental" },
    "notas": [ { "id": 4, "nombre": "pachuli" }, { "id": 9, "nombre": "iris" }, { "id": 20, "nombre": "vainilla" }, { "id": 49, "nombre": "incienso" } ],
    "imagen_url": "http://localhost/storage/productos/coromandel.jpg",
    "presentaciones": [
      { "id": 75, "tamano": "5 ml", "precio": "62000.00", "stock": 12, "disponible": true },
      { "id": 76, "tamano": "10 ml", "precio": "105400.00", "stock": 12, "disponible": true }
    ]
  }
}
```

Los precios están en guaraníes y se devuelven como texto decimal.

## Pedidos de la clienta

Necesitan token. Cada clienta ve **solo sus propios pedidos**. Si pide uno de otra persona, recibe 404.

### `GET /mis-pedidos`

Sus pedidos, del más nuevo al más viejo, con número, estado, fecha y totales.

### `GET /mis-pedidos/{id}`

El detalle de un pedido, con:

- los datos de contacto y de entrega;
- los ítems: perfume, tamaño, cantidad y precios;
- el `historial` de cambios de estado, con su fecha.

```bash
curl http://localhost/api/v1/mis-pedidos/12 -H "Authorization: Bearer $TOKEN"
```

## Administración

Necesitan un token de un usuario con rol **`administrador`**. Una clienta recibe 403.

### `GET /admin/pedidos`

Todos los pedidos, paginados. Se puede filtrar con `?estado=pendiente`.

### `GET /admin/pedidos/{id}`

El detalle completo del pedido, con los mismos campos que `/mis-pedidos/{id}`.

### `PATCH /admin/pedidos/{id}/estado`

Cambia el estado del pedido con **las mismas reglas que el panel web**. Las dos usan el servicio `CicloPedido`.

```
pendiente  → confirmado | cancelado
confirmado → preparando | cancelado
preparando → enviado    | cancelado
enviado    → entregado
```

Cambios de stock:

- Al **confirmar**, descuenta el stock de cada decant y lo registra en el kardex como "Venta".
- Al **cancelar** un pedido confirmado o en preparación, devuelve el stock.

Si la transición no está permitida o falta stock, responde **422** con `errors.estado`, y no cambia nada.

```bash
curl -X PATCH http://localhost/api/v1/admin/pedidos/12/estado \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  -d estado=confirmado
```

### `GET /admin/inventario/stock-critico`

Decants activos con 3 unidades o menos, el mismo criterio que el dashboard. Cada uno incluye su producto.

### `POST /admin/inventario/movimientos`

Registra una entrada o una salida en el kardex y actualiza el stock. Es la misma lógica que el panel web, a través del servicio `Inventario`.

| Campo | Regla |
|---|---|
| `presentacion_id` | Debe ser un decant de 5 ml o 10 ml que exista |
| `tipo` | `entrada` o `salida` |
| `cantidad` | Entero mayor o igual a 1 |
| `motivo` | Obligatorio, hasta 255 caracteres |

```bash
curl -X POST http://localhost/api/v1/admin/inventario/movimientos \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  -d presentacion_id=75 -d tipo=entrada -d cantidad=5 -d motivo='Reposición de proveedor'
```

```json
{ "data": { "id": 31, "presentacion_id": 75, "tipo": "entrada", "cantidad": 5, "motivo": "Reposición de proveedor", "stock_resultante": 17, "usuario_id": 1, "fecha": "2026-11-09T19:40:12+00:00" } }
```

Una salida mayor al stock disponible, o una entrada que supere el máximo permitido, responde 422 con `errors.cantidad` y no escribe nada.

## Pruebas

- **Tests automáticos:** `cd src && php artisan test --filter=Api` corre los tests de la API (`tests/Feature/Api/`).
- **Prueba en Docker:** con el stack levantado, se probó a través de HAProxy:
  - el catálogo;
  - el login de admin y de clienta;
  - el mismo token alternando entre las dos arquitecturas;
  - el 403 de una clienta en rutas de admin;
  - el logout con el token revocado (401);
  - el 422 con credenciales inválidas.
