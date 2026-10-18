# Mar Fragancias · Tienda de decants y panel de administración — Especificación de diseño

**Fecha:** 2026-10-18
**Autora:** María Paz Gutiérrez (Frontend & QA)
**Tareas de Jira:** PI-12 (Wireframes: login, catálogo público, carrito) y PI-13 (Plan de pruebas para endpoints de Auth y Catálogo); el panel de administración y el flujo de pedidos cubren además épicas del tablero (Catálogo, Inventario, Pedidos, Métricas).
**Maquetas aprobadas:** `docs/diseno/maquetas/diseno-final.html` (abrir en el navegador; es la referencia visual obligatoria)
**Método:** SDD (esta especificación es la fuente de verdad) + TDD (cada comportamiento se escribe primero como test que falla)

---

## 1. Intención y por qué

Mar Fragancias (Asunción, Paraguay) vende **solo decants de 5 ml y 10 ml** de perfumes originales, sobre todo de casas de lujo, para que la clienta pruebe y use fragancias caras pagando solo lo que va a usar. **No vende frascos completos ni decants de 15 ml.**

Hoy vende por Instagram y WhatsApp, a mano. Lo primero que pregunta una clienta es **"¿qué marcas hay?"**. La tienda web tiene que responder eso de inmediato, dejar elegir un perfume y un tamaño, armar un carrito y **cerrar la compra por WhatsApp** con el pedido ya escrito.

Referencias de estilo: laperfumeria.com.py (contenido) en versión más minimalista, y el encabezado de chanel.com.

## 2. Objetivos

1. Catálogo público navegable **por marca** (puerta de entrada), con Mujer / Hombre / Unisex como filtro dentro de cada marca.
2. Página de perfume con selección de **decant 5 ml o 10 ml**, precio según tamaño y stock.
3. Carrito en sesión, sin cuenta obligatoria, que al agregar un producto recomienda **perfumes con notas parecidas**.
4. Checkout con datos personales y de entrega que **guarda el pedido como `pendiente`** y redirige a **WhatsApp** con el mensaje armado.
5. **Termómetro olfativo**: sección interactiva que explica qué perfumes van con cada clima y recomienda decants.
6. Login y registro rediseñados (PI-12) y suite de tests de Auth y Catálogo documentada como plan de pruebas (PI-13).
7. **Panel de administración** rediseñado con el mismo estilo (pantallas 14–20): dashboard, productos con decants, género y notas, pedidos con ciclo de estados, inventario (kardex) y marcas/categorías/tamaños.

## 3. No objetivos (esta etapa)

- "Mi cuenta / Mis pedidos" de la clienta (pantalla 13): etapa siguiente.
- Gestión de clientes en el admin más allá de verlos en el detalle del pedido.
- Pagos en línea: la venta se cierra en WhatsApp.
- Kit de prueba y diario de prueba (ideas descartadas).

## 4. Anti-objetivos (fallaría aunque "funcione")

- **Ningún emoji** ni símbolo de texto usado como icono en la interfaz. Iconos: **Phosphor** (grosor `light`; logo de WhatsApp en `regular`).
- Mostrar 15 ml, frascos completos o precios de frasco en cualquier lugar.
- Mezclar en la barra principal tipos distintos de cosas (marcas + géneros + páginas informativas): la barra lista **marcas**; lo informativo va separado al final.
- Permitir agregar al carrito un tamaño sin stock.
- Que el pedido se pierda si WhatsApp no se abre: el pedido se guarda **antes** de redirigir.

## 5. Identidad visual (decidido por la usuaria)

- **Nombre:** "Mar Fragancias" (como en el logo). Logo: `public/images/logo-mf.png` (azul marino con fondo transparente; original en `docs/diseno/logo-original.png`).
- **Colores:** azul marino del logo `#192438` (color principal, textos, botones), blanco de fondo, verde salvia `#5f7a61` (acentos), salvia suave `#c8d6c4`, fondo salvia `#f4f6f3`, línea `#e6e8e4`, texto secundario `#5b6475`, crema del logo `#f3ebe0`, verde WhatsApp `#1f8f4e`.
- **Tipografías:** Montserrat (títulos, menú en mayúsculas espaciadas), Inter (texto), IBM Plex Mono (etiquetas pequeñas).
- **Tailwind 4** (ya instalado): definir estos valores como tokens en `@theme` de `resources/css/app.css`; no repetir hex sueltos en las vistas.
- **Iconos:** paquete Blade `codeat3/blade-phosphor-icons` (o equivalente oficial de Phosphor para Blade).
- Interacción: transiciones de 150–250 ms con `ease-out`, botones con `scale(0.97)` al presionar, respetar `prefers-reduced-motion`, contraste mínimo 4.5:1, foco visible, objetivos táctiles ≥ 44 px. Diseño **mobile-first** (la mayoría llega desde Instagram).

## 6. Pantallas (ver maquetas para la composición exacta)

| # en maqueta | Pantalla | Ruta propuesta |
|---|---|---|
| 1 | Inicio | `GET /` |
| Panel de marca | Panel desplegable al pasar el mouse por una marca de la barra | (componente del encabezado) |
| Termómetro | Termómetro olfativo | `GET /termometro-olfativo` |
| 2 | Catálogo filtrado (por marca y/o género, familia, tamaño) | `GET /perfumes` |
| 3 | Todas las marcas (índice A–Z) | `GET /marcas` |
| 3b | Página de una marca con pestañas Todos/Mujer/Hombre/Unisex | `GET /marcas/{marca:slug}` |
| 4 | Perfume y selección de decant | `GET /perfumes/{producto:slug}` |
| 5 | Carrito (panel lateral; también página) | `GET /carrito` |
| 6 | Datos y envío | `GET /checkout`, `POST /checkout` |
| 11 | Pedido enviado | `GET /pedido/{numero}` |
| 7 | Ingresar / Crear cuenta | `GET /login`, `GET /register` (existentes) |
| 9 | Búsqueda | `GET /buscar?q=` |
| 10 | Cómo funciona | `GET /como-funciona` |
| 12 | Estados vacíos (carrito vacío, sin resultados) y 404 | — |
| 8 | Versión celular de detalle y carrito | responsive |

### 6.1 Encabezado (todas las páginas públicas)
- Fila 1: logo centrado (enlace a inicio); a la derecha iconos buscar, cuenta y carrito con contador de unidades.
- Fila 2: las **marcas destacadas** en mayúsculas espaciadas, luego "Todas las marcas" (verde salvia) y, separados por un espacio, "Termómetro olfativo" y "Cómo funciona".
- Marcas destacadas: las marcas con `destacada = true`, ordenadas por `orden_menu` (máximo 8). Se gestionan con seeders en esta etapa.
- Al pasar el mouse (o con foco de teclado) por una marca se abre su **panel**: foto, columnas "En portada" (sus `destacado = true`), "Mujer", "Hombre", "Para compartir" (unisex), cada una con hasta 6 perfumes y "Ver todo" → `/marcas/{slug}?genero=...`. Franja superior que invita al Termómetro olfativo. Cerrable.
- Celular: logo, iconos y botón de menú que abre la lista de marcas.

### 6.2 Inicio
Franja superior "ENVÍOS A TODO PARAGUAY · FINALIZÁS TU COMPRA POR WHATSAPP"; hero "Perfumes de lujo, en decant." con dos frascos (5 ml y 10 ml) y botones "Ver perfumes" / "Cómo funciona"; tres pasos centrados (Elegí tu perfume · Elegí 5 o 10 ml · Pedí por WhatsApp); "Los más probados" (4 perfumes); pie de página. **Sin** bloque de marcas ni bloque "Para ella / Para él".

### 6.3 Perfume (detalle)
Marca · género · familia; nombre; descripción; chips de notas; selector de **dos tarjetas** (5 ml, 10 ml) con precio; un tamaño con stock 0 o inactivo se muestra apagado y no se puede elegir; precio grande del tamaño elegido; cantidad (− / +, máximo = stock); "Agregar al carrito"; recuadro "¿Por qué un decant?"; "También te puede gustar" (4 parecidos por notas). En celular, barra fija abajo con precio y "Agregar".

### 6.4 Carrito
Panel lateral desde la derecha al agregar (y página `/carrito`). Muestra "Agregaste {perfume} · Decant {tamaño}", líneas con cantidad editable (0 elimina), subtotal y "Continuar con mis datos". Debajo: **"Con notas parecidas a {último perfume agregado}"** — hasta 3 perfumes con % de parecido, las notas que comparten y botón "+ 5 ml" que agrega su decant de 5 ml.

### 6.5 Checkout y WhatsApp
Pasos: Carrito ✓ → Tus datos → WhatsApp. Campos:
- Datos personales: nombre y apellido (requerido), celular WhatsApp (requerido, formato paraguayo de 10 dígitos que empieza en `09`, se aceptan espacios), cédula (opcional), email (opcional, válido).
- Entrega: **Retiro** (gratis), **Envío Asunción y Gran Asunción** (costo fijo configurable), **Envío al interior** (a coordinar, costo 0 en el pedido). Para envío: ciudad (requerida), barrio, dirección (requerida), referencia (opcional).
- Resumen con subtotal, envío y total; botón verde "Enviar pedido por WhatsApp".
Al enviar: se valida que cada línea siga teniendo stock suficiente; se crea el **pedido `pendiente`** con sus detalles y precios del momento; se vacía el carrito; se redirige a `https://wa.me/{numero_tienda}?text={mensaje}` y la página `/pedido/{numero}` queda disponible con "Abrir WhatsApp de nuevo".
Si la clienta tiene sesión iniciada, los datos se precargan desde su perfil y el pedido se asocia a su cliente.

Mensaje (texto exacto, con saltos de línea):
```
¡Hola Mar Fragancias! Quiero hacer este pedido (#MF-0042):

• Sauvage (Dior) · Decant 10 ml × 1 · ₲ 60.000
• Good Girl (Carolina Herrera) · Decant 5 ml × 2 · ₲ 64.000

Subtotal: ₲ 124.000
Envío Asunción: ₲ 20.000
Total: ₲ 144.000

Nombre: María Paz Gutiérrez
Entrega: Av. Cacique Lambaré 1234, Centro, Lambaré
Ref.: Portón negro, frente a la farmacia
```
Para retiro, la línea de entrega dice `Entrega: Retiro (a coordinar)`; para interior, `Envío al interior: a coordinar`. Montos en guaraníes con punto de miles y símbolo `₲`.

### 6.6 Termómetro olfativo
Sección interactiva (prototipo funcional en la maqueta). Control de temperatura 10–40 °C, Día/Noche, Seco/Húmedo, franja de 12 meses de Paraguay (al tocar uno, toma su temperatura promedio). Cuatro zonas:

| Zona | Rango | Notas que funcionan | Mejor dejar |
|---|---|---|---|
| Fresco o frío | ≤ 18 °C | vainilla, ámbar, oud, canela, tabaco, cuero, haba tonka | cítricos solos, notas acuáticas |
| Templado | 19–26 °C | rosa, jazmín, iris, lavanda, almizcle, té | oud muy intenso |
| Calor | 27–32 °C | bergamota, neroli, té verde, vetiver, pimienta rosa | vainilla pesada, oud, canela |
| Calor intenso | > 32 °C | limón, pomelo, menta, sal marina, notas verdes, cardamomo | vainilla, oud, ámbar, caramelo |

Aplicaciones sugeridas: 4 / 3 / 2 / 1 según zona; Noche +1 (máx. 4); Húmedo −1 (mín. 1). Textos explicativos: los de la maqueta.
Recomendaciones: hasta 3 perfumes con stock cuyas notas coinciden más con "notas que funcionan" de la zona (más coincidencias primero) y que no tengan notas de "mejor dejar".
"Usar el clima de hoy": temperatura actual de Asunción desde **Open-Meteo** (sin clave), cacheada 30 minutos; si el servicio falla, se usa el promedio del mes actual y no se muestra error.
La lógica de zonas, dosis y recomendaciones vive en **PHP** (testeable) y se entrega a la vista; el JavaScript solo cambia el estado visual (Alpine.js o JS sin framework, a elección del builder).

### 6.7 Parecido por notas
`parecido(A, B) = |notas(A) ∩ notas(B)| / |notas(A) ∪ notas(B)|` (Jaccard), mostrado como porcentaje entero. Solo perfumes activos con al menos un decant con stock, distintos de A. Orden: parecido desc, luego nombre. Usado en el carrito (3), en "También te puede gustar" (4) y en el termómetro.

### 6.8 Búsqueda
Busca por nombre de perfume, nombre de marca y nombre de nota (sin distinguir mayúsculas ni tildes). Resultados agrupados; sin resultados muestra el estado vacío con enlace a WhatsApp.

### 6.9 Panel de administración (pantallas 14–20)
Mismo estilo de la tienda: menú lateral azul marino con logo en crema e iconos Phosphor, agrupado en VENTAS (Pedidos, con contador de pendientes), CATÁLOGO (Productos, Marcas, Categorías, Tamaños) y STOCK (Inventario, con contador de stock crítico). Protegido por el middleware `rol:administrador` existente.

- **Dashboard** (`/admin/dashboard`): tarjetas "Pedidos por confirmar" (cantidad y antigüedad del más viejo), "Ventas del mes" (suma de `total` de pedidos confirmados o posteriores del mes, y cantidad), "Decants vendidos" del mes (unidades, y % que es 5 ml) y "Stock crítico" (presentaciones activas con stock ≤ 3). Gráfico de barras horizontales "Más vendidos este mes" (top 5 presentaciones por unidades, contando pedidos confirmados o en estados posteriores, sin cancelados) y tabla de stock crítico.
- **Productos** (`/admin/productos`): tabla con perfume, marca, género, stock de 5 ml y 10 ml (rojo si ≤ 3), visible (activo) y Editar; filtros Todos / Mujer / Hombre / Unisex / Sin stock / Inactivos; búsqueda por nombre; "+ Nuevo perfume".
- **Crear/Editar perfume**: nombre, marca, familia de aroma (categoría), género (Mujer/Hombre/Unisex), descripción, notas (selección múltiple, permite crear nuevas), destacado, visible, foto; tabla de presentaciones 5 ml y 10 ml con **precio** y activo. El **stock no se edita aquí**: se muestra y se cambia desde Inventario (al crear, el stock inicial se registra como movimiento de entrada "Stock inicial").
- **Pedidos** (`/admin/pedidos`): filtros por estado con contadores; columnas número, fecha, cliente (nombre y teléfono), entrega, total, estado (insignia de color); búsqueda por número, nombre o teléfono.
- **Detalle de pedido** (`/admin/pedidos/{pedido}`): productos con tamaño, cantidad, precio y subtotal; envío y total; datos de la clienta con "Escribir por WhatsApp" (`wa.me` al teléfono de la clienta); historial de estados con fecha; botones para avanzar.
  - Ciclo: `pendiente → confirmado → preparando → enviado → entregado`; `cancelado` desde pendiente, confirmado o preparando. No se puede saltar estados ni volver atrás.
  - **Confirmar** descuenta el stock de cada presentación y registra un movimiento `salida` con motivo "Venta MF-XXXX", en una transacción; si alguna presentación no tiene stock suficiente, no confirma y muestra cuál.
  - **Cancelar** un pedido ya confirmado o en preparación devuelve el stock con movimientos `entrada` "Cancelación MF-XXXX". Cancelar uno pendiente no toca el stock.
  - Cada cambio de estado se guarda en `pedido_estados` (pedido, estado, usuario, fecha).
- **Inventario** (`/admin/inventario`): kardex con fecha, perfume · tamaño, tipo (entrada/salida), cantidad (+/−), stock después, motivo y usuario (o "Sistema"); filtros Todos / Entradas / Salidas / Stock crítico y por perfume. "+ Registrar movimiento": presentación, tipo, cantidad (> 0) y motivo (requerido); una salida no puede dejar el stock negativo. El stock de `producto_presentacion` cambia **solo** a través de movimientos.
- **Marcas / Categorías / Tamaños**: listas con nombre, cantidad de perfumes y acciones; solo se puede eliminar si no tiene perfumes. Marcas además gestiona `destacada`, `orden_menu` (las 8 del menú) e imagen del panel. Tamaños: no se permiten tamaños distintos de 5 ml y 10 ml en esta etapa (lista de solo lectura).

## 7. Datos (cambios sobre lo existente)

Existe: `roles`, `usuarios`, `clientes`, `marcas`, `categorias` (se usan como **familia de aroma**: Amaderada, Fresca, Floral, Oriental, Cítrica, Dulce), `tamanos` (5 ml, 10 ml), `productos`, `producto_presentacion` (precio y stock por tamaño), `pedidos`, `detalle_pedidos`, `movimientos_inventario`.

Cambios (con migraciones nuevas, sin editar las existentes):
- `productos`: `genero` string(10) con valores permitidos `mujer|hombre|unisex` validados en la aplicación (default `unisex`), `slug` único, `destacado` boolean (default false).
- `marcas`: `slug` único, `destacada` boolean, `orden_menu` entero nullable, `imagen` nullable (foto del panel).
- `notas` (`id`, `nombre` único) y pivote `nota_producto` (`producto_id`, `nota_id`).
- `pedidos`: `cliente_id` pasa a **nullable** (compra sin cuenta); se agregan `numero` único (`MF-0001`, a partir del id), `nombre_contacto`, `telefono`, `cedula` nullable, `email` nullable, `metodo_entrega` (`retiro|asuncion|interior`), `ciudad`, `barrio`, `direccion`, `referencia` (nullables según método), `subtotal`, `costo_envio`.
- `tamanos`: se mantienen solo 5 ml y 10 ml. Nada de 15 ml ni frascos.
- `pedido_estados` (`id`, `pedido_id`, `estado`, `usuario_id` nullable, `created_at`).
- `movimientos_inventario`: agregar `usuario_id` nullable y `stock_resultante` entero.
- Seeders: completar `ProductoPresentacionSeeder` (hoy vacío) con precios y stock de ejemplo para 5 y 10 ml, notas y género de cada perfume, y marcas de lujo de ejemplo con 8 destacadas (Chanel, Dior, Tom Ford, Creed, Guerlain, Hermès, Yves Saint Laurent, Maison Francis Kurkdjian). Los datos reales los carga la tienda después.

Configuración (en `config/tienda.php`, leyendo `.env`): número de WhatsApp de la tienda (`TIENDA_WHATSAPP`, placeholder hasta tener el real), costo de envío Asunción (`TIENDA_ENVIO_ASUNCION`, default 20000), stock máximo por línea = stock disponible.

El panel completo se describe en 6.9.

## 8. Comportamiento a probar (base del plan de pruebas PI-13)

Auth: ver login/registro; login correcto redirige por rol (admin → dashboard, cliente → `cliente.home`); credenciales inválidas vuelven con error y conservan el email; registro crea usuario rol cliente + perfil cliente y lo deja autenticado; email duplicado y contraseña sin confirmar se rechazan; logout invalida la sesión; rutas `/admin/*` exigen rol administrador (403 a cliente, redirección a login a invitado).

Catálogo: inicio lista "Los más probados" solo con perfumes activos; menú muestra solo marcas destacadas en orden; página de marca filtra por género; producto inactivo da 404; tamaño sin stock aparece deshabilitado; nunca aparece 15 ml ni frasco; búsqueda por perfume, marca y nota; parecido por notas calculado según 6.7.

Carrito y checkout: agregar/actualizar/eliminar; no se puede superar el stock; recomendaciones por notas; validaciones del formulario (incluido el celular); el pedido queda `pendiente` con número, detalles y totales correctos; costo de envío según método; la redirección a `wa.me` lleva el mensaje exacto de 6.5; carrito vacío no puede hacer checkout; el stock **no** se descuenta en esta etapa.

Panel admin: acceso solo administrador; filtros de productos y pedidos; transiciones de estado válidas e inválidas; confirmar descuenta stock y registra salida; confirmar sin stock suficiente falla sin cambios; cancelar confirmado devuelve stock; cancelar pendiente no toca stock; historial de estados; movimiento manual no deja stock negativo; editar producto no cambia stock; no se elimina marca/categoría con perfumes; métricas del dashboard calculadas sobre datos conocidos.

Termómetro: zona por temperatura (bordes 18/19, 26/27, 32/33), dosis con noche/humedad y sus límites, recomendaciones excluyen notas de "mejor dejar", fallo de Open-Meteo usa el promedio del mes (HTTP falseado en tests).

El documento `docs/pruebas/plan-de-pruebas-auth-catalogo.md` lista estos casos con su test automatizado correspondiente (entregable de PI-13).

## 9. Restricciones

- Laravel 13, PHP 8.3+, Blade, Tailwind 4, Vite. Tests con PHPUnit (`php artisan test`), SQLite en memoria (ya configurado en `phpunit.xml`).
- El repo pide (ver `src/AGENTS.md`) tener PHP/Composer e instalar **Laravel Boost** antes de cambiar la aplicación; hoy la máquina **no tiene PHP, Composer ni Docker**. Preparar el entorno es el primer paso.
- Idioma de la interfaz: español de Paraguay (voseo: "Elegí", "Pedí").
- No romper el panel admin ni las rutas existentes.

## 10. Decisiones tomadas por el orquestador (revisar)

- Alcance: tienda pública + panel de administración completo (confirmado por la usuaria). "Mis pedidos" de la clienta queda para después.
- "Para compartir" es la etiqueta visible de `unisex`.
- Parecido = Jaccard sobre notas.
- Clima: Open-Meteo, Asunción, caché 30 min.
- Número de pedido `MF-` + id con 4 dígitos.
- Stock crítico = stock ≤ 3. El stock se descuenta al **confirmar** (no al enviar a WhatsApp).
- **"Los más probados"** (inicio, orden del catálogo, carrito vacío): perfumes activos con stock ordenados por unidades vendidas en pedidos confirmados o posteriores (sin cancelados) de los últimos 90 días; si no hay ventas suficientes se completa con `destacado = true` y luego por nombre.
- **Base de datos:** MySQL 8 en desarrollo y despliegue (como en `docker-compose.yml`), SQLite en memoria para tests. La búsqueda sin tildes se resuelve normalizando en PHP (columna `nombre_busqueda` o comparación normalizada), para que funcione igual en ambos motores.
- **Métricas del mes:** se cuentan por la **fecha de confirmación** del pedido (registro `confirmado` en `pedido_estados`).
- **Tamaños:** la ruta `admin.tamanos` queda solo con `index` (lista de solo lectura); se quitan crear/editar/eliminar y sus vistas.
- **Datos existentes:** las migraciones nuevas no asumen base vacía: `genero` se agrega con valor por defecto `unisex`, `slug` se completa a partir del nombre para filas existentes (y luego se hace único), los pedidos existentes reciben `numero` a partir de su id.
- El celular se valida como paraguayo (`09` + 8 dígitos).

## 11. Pendiente de la tienda (no bloquea el desarrollo)

Número de WhatsApp real, costos de envío reales, lista real de marcas y perfumes con precios, fotos de productos, respuestas reales de "Cómo funciona".
