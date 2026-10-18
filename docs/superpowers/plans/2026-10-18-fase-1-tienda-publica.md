# Fase 1 · Tienda pública de Mar Fragancias — Plan de implementación

> **Para agentes:** ejecutar tarea por tarea con TDD estricto (test que falla → código mínimo → test pasa → commit). Los pasos usan casillas `- [ ]`; marcalas al completarlas. No avanzar a la siguiente tarea con tests en rojo.

**Objetivo:** construir la tienda pública de decants (inicio, marcas, perfume, carrito, checkout a WhatsApp, termómetro olfativo, búsqueda, login/registro) según la especificación y las maquetas aprobadas.

**Arquitectura:** Laravel 13 con Blade y Tailwind 4. La lógica de negocio vive en servicios PHP pequeños y testeables (`app/Services`, `app/Support`); los controladores solo orquestan y devuelven vistas. La interfaz pública usa componentes Blade anónimos en `resources/views/components/tienda/`. El carrito vive en la sesión.

**Stack:** PHP 8.3+, Laravel 13, PHPUnit 12 (`php artisan test`), SQLite en memoria para tests, MySQL 8 para desarrollo, Tailwind 4 + Vite, `codeat3/blade-phosphor-icons`, Alpine.js (para drawer, panel de marca y termómetro).

**Spec:** `docs/superpowers/specs/2026-10-18-tienda-y-admin-design.md` (leerla completa antes de empezar). **Maquetas:** `docs/diseno/maquetas/diseno-final.html` (abrir en el navegador; cada vista se compara contra su pantalla).

**Rama:** `feature/tienda-publica` (crear desde `main`). Commits pequeños por tarea. **No hacer push.**

Todas las rutas de comandos son relativas a `src/` salvo que se indique.

## Restricciones globales

- Solo decants de **5 ml y 10 ml**. Nunca mostrar 15 ml, frascos completos ni precios de frasco.
- **Ningún emoji** ni símbolo de texto como icono (✕ ✓ → ▾ etc.): solo iconos Phosphor (`light`; WhatsApp en `regular`).
- Colores como tokens en `@theme` de `resources/css/app.css`: `navy #192438`, `sage #5f7a61`, `sage-soft #c8d6c4`, `sage-bg #f4f6f3`, `line #e6e8e4`, `ink-soft #5b6475`, `cream #f3ebe0`, `wa #1f8f4e`. Fuentes: Montserrat (títulos y menú), Inter (texto), IBM Plex Mono (etiquetas).
- Español de Paraguay con voseo ("Elegí", "Pedí", "Completá").
- Montos en guaraníes: `₲ 124.000` (punto de miles, sin decimales).
- Etiqueta visible de `unisex` = **"Para compartir"**.
- Mobile-first; objetivos táctiles ≥ 44 px; foco visible; contraste ≥ 4.5:1; transiciones 150–250 ms `ease-out`; respetar `prefers-reduced-motion`.
- No romper el panel admin ni las rutas existentes (`login`, `register`, `logout`, `admin.*`, `cliente.home`).
- No editar migraciones existentes: solo migraciones nuevas.

## Foco de revisión

Casos que la spec implica pero ningún test "obvio" cubre; cada uno tiene su test en la tarea indicada:

1. **El mensaje de WhatsApp contiene `#`, tildes, `&` y saltos de línea**: si no se codifica con `rawurlencode`, el `#MF-0042` corta la URL (todo lo que sigue se vuelve fragmento). → Tarea 8, `test_url_de_whatsapp_codifica_numeral_tildes_y_saltos`.
2. **Cantidades manipuladas en el POST** (0, negativas, texto, mayores al stock) → Tarea 7, `test_no_acepta_cantidades_invalidas`.
3. **Lo que está en el carrito cambia antes del checkout** (la presentación se desactiva o se queda sin stock) → Tarea 8, `test_checkout_rechaza_linea_sin_stock_y_no_crea_pedido`.
4. **Celular escrito de distintas formas** (`0981 234 567`, `0981234567`, `+595 981 234 567`): se aceptan y se guardan normalizados como `0981234567`; `0981 23` se rechaza → Tarea 8, `test_normaliza_celular_paraguayo`.
5. **Open-Meteo lento o caído**: la página del termómetro responde igual, con el promedio del mes y sin error visible (timeout 3 s) → Tarea 9, `test_si_open_meteo_falla_usa_promedio_del_mes`.

---

### Tarea 0: Entorno de desarrollo

**Archivos:** `src/.env` (local, no se commitea), `src/composer.json`, `src/package.json`, `src/AGENTS.md`/`CLAUDE.md` (los regenera Boost)

- [x] **Paso 1:** Si `php -v` no existe, instalar PHP y Composer como indica `src/AGENTS.md` para Linux: `/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"`. Abrir una shell nueva y verificar con `php -v` (≥ 8.3) y `composer -V`.
- [x] **Paso 2:** `git checkout -b feature/tienda-publica` (desde la raíz del repo).
- [x] **Paso 3:** `composer install && npm install && cp -n .env.example .env && php artisan key:generate`.
- [x] **Paso 4:** Laravel Boost: `composer require laravel/boost --dev && php artisan boost:install`. Releer el `AGENTS.md` generado y seguir sus guías.
- [x] **Paso 5:** `composer require codeat3/blade-phosphor-icons` y `npm install alpinejs` (importarlo e iniciarlo en `resources/js/app.js`).
- [x] **Paso 6:** `php artisan test` → Esperado: PASS (los dos ExampleTest).
- [x] **Paso 7:** Commit: `chore: entorno, Laravel Boost, Phosphor y Alpine`.

**Registro Tarea 0 (2026-10-18):** PHP 8.5.0 y Composer 2.8.12 instalados. Rama creada antes de la instalación, como pidió el orquestador. RED inicial: ExampleTest esperaba 200 en `/` y recibió 404 (la ruta aún no existe). El orquestador autorizó mover el smoke test a `/login`; GREEN: 2 tests, 2 assertions. En Tarea 4 corresponde probar el inicio real. Boost regeneró guías, skills y configuración MCP. Alpine importado e iniciado; `npm run build` correcto (aviso preexistente de `fontaine` opcional). `.env` local no versionado.

### Tarea 1: Datos del catálogo (migraciones, modelos, factories)

**Archivos:**
- Crear: `database/migrations/2026_10_18_000001_add_catalogo_fields.php`, `..._000002_create_notas_tables.php`, `..._000003_add_checkout_fields_to_pedidos.php`
- Crear: `app/Models/Nota.php`, factories `MarcaFactory`, `CategoriaFactory`, `TamanoFactory`, `ProductoFactory`, `ProductoPresentacionFactory`, `NotaFactory`
- Modificar: `app/Models/Producto.php`, `Marca.php`, `Pedido.php` (agregar `HasFactory` donde haga falta)
- Test: `tests/Feature/Catalogo/ModeloCatalogoTest.php`

**Interfaces producidas:**
- `Producto::GENEROS = ['mujer','hombre','unisex']`; `Producto::etiquetaGenero(): string` → `Mujer` | `Hombre` | `Para compartir`
- `Producto::scopeVisibles($q)` → activos con al menos una presentación activa de 5 ml o 10 ml
- `Producto::notas(): BelongsToMany`, `Nota::productos()`
- `Producto::presentacionPorTamano(string $nombre): ?ProductoPresentacion` (`'5 ml'` | `'10 ml'`)
- `ProductoPresentacion::disponible(): bool` (activa y stock > 0)
- `Marca::scopeDelMenu($q)` → `destacada = true`, orden `orden_menu`, límite 8
- Rutas por slug: `Producto` y `Marca` usan `getRouteKeyName(): 'slug'`
- `Pedido::numero` (`MF-0001`), generado al crear a partir del id (relleno con 4 ceros)

- [x] **Paso 1: tests que fallan**
  - `test_producto_tiene_genero_slug_y_notas`: crea producto con `genero='mujer'` y 2 notas; `assertSame('Mujer', $p->etiquetaGenero())`; `assertCount(2, $p->notas)`; `assertNotEmpty($p->slug)`.
  - `test_unisex_se_muestra_como_para_compartir`.
  - `test_visibles_excluye_inactivos_y_sin_presentaciones_activas`.
  - `test_marcas_del_menu_son_las_destacadas_en_orden_y_maximo_ocho`: 10 destacadas con `orden_menu` desordenado + 1 no destacada → 8 resultados ordenados.
  - `test_pedido_sin_cliente_genera_numero`: `Pedido::create([... 'cliente_id' => null ...])` → `numero === 'MF-' . str_pad($id, 4, '0', STR_PAD_LEFT)`.
  - `test_migracion_completa_datos_existentes`: un producto insertado sin genero/slug antes de migrar queda con `genero='unisex'` y slug derivado (usar `RefreshDatabase` + inserción por query en un test de migración, o probar el backfill como método estático `BackfillCatalogo::run()` invocado por la migración).
- [x] **Paso 2:** `php artisan test --filter=ModeloCatalogoTest` → FAIL.
- [x] **Paso 3:** Migraciones según spec §7: `productos.genero` (string 10, default `unisex`), `productos.slug` (único, backfill desde `Str::slug(nombre)` con sufijo `-2`, `-3` si se repite), `productos.destacado` (bool false); `marcas.slug` (único, backfill), `marcas.destacada` (bool false), `marcas.orden_menu` (int nullable), `marcas.imagen` (nullable); `notas` + `nota_producto`; en `pedidos`: `cliente_id` nullable, `numero` único (backfill), `nombre_contacto`, `telefono`, `cedula`, `email`, `metodo_entrega`, `ciudad`, `barrio`, `direccion`, `referencia`, `subtotal`, `costo_envio`. Slugs se generan automáticamente al crear (evento `creating`).
- [x] **Paso 4:** `php artisan test` → PASS completo.
- [x] **Paso 5:** Commit `feat(catalogo): género, slug, notas y campos de pedido`.

**Registro Tarea 1 (2026-10-18):** RED: 9 casos sin factories/migraciones, pedido sin cliente rechazado; después se verificó el 404 del admin al usar slugs. GREEN: 12 tests, 43 assertions. Se fijaron bindings `id` explícitos en los recursos admin, conservando enlaces y URLs existentes; los modelos públicos resuelven por slug. El orquestador confirmó `genero` string(10), actualizó la spec y pidió validarlo con `Producto::GENEROS`. Confirmó backfill de pedidos: subtotal = total, costo_envio = 0, contacto y entrega NULL; ambas aclaraciones tuvieron test RED → GREEN. Solo se agregaron migraciones nuevas. La herramienta `task-start` del skill no reconoce encabezados «Tarea»; se mantiene el registro aquí y en el ledger sin cambiar el formato del plan.

### Tarea 2: Seeders con marcas de lujo, notas y decants

**Archivos:** Modificar `database/seeders/MarcaSeeder.php`, `ProductoSeeder.php`, `ProductoPresentacionSeeder.php` (hoy vacío); crear `NotaSeeder.php`; registrar en `DatabaseSeeder`. Test: `tests/Feature/Catalogo/SeedersTest.php`.

- [ ] **Paso 1: tests que fallan**
  - `test_seed_crea_solo_tamanos_5_y_10_ml`: tras `$this->seed()`, `Tamano::pluck('nombre')->sort()->values()->all() === ['10 ml','5 ml']` y no existe ningún tamaño con `15` ni `frasco`.
  - `test_seed_tiene_ocho_marcas_del_menu_en_orden`: `Marca::delMenu()->pluck('nombre')->all() === ['Chanel','Dior','Tom Ford','Creed','Guerlain','Hermès','Yves Saint Laurent','Maison Francis Kurkdjian']`.
  - `test_cada_producto_visible_tiene_genero_notas_y_dos_decants`: cada `Producto::visibles()` tiene ≥ 3 notas y presentaciones de 5 y 10 ml con precio > 0.
- [ ] **Paso 2:** correr → FAIL.
- [ ] **Paso 3:** Seeders: ≥ 24 perfumes de ejemplo repartidos entre las 8 marcas del menú y otras (Carolina Herrera, Versace, Paco Rabanne, Parfums de Marly, Xerjoff, Lattafa, Armaf), con género, familia (categoría existente), notas reales del perfume y precios de ejemplo (5 ml entre ₲ 15.000 y ₲ 65.000; 10 ml ≈ 1,7×). Al menos uno por marca destacada en cada género cuando aplique; uno con 10 ml en stock 0 (para ver el estado deshabilitado). Mantener usuarios y roles existentes.
- [ ] **Paso 4:** `php artisan test` → PASS. `php artisan migrate:fresh --seed` (con MySQL o SQLite local) sin errores.
- [ ] **Paso 5:** Commit `feat(seed): marcas de lujo, notas y decants de ejemplo`.

### Tarea 3: Recomendaciones (parecido por notas y "más probados")

**Archivos:** Crear `app/Services/Recomendador.php`. Test: `tests/Unit/RecomendadorTest.php` (con `RefreshDatabase`, en `tests/Feature/Catalogo/` si necesita base).

**Interfaces producidas:**
- `Recomendador::parecido(Producto $a, Producto $b): int` → porcentaje 0–100 (Jaccard, redondeo a entero)
- `Recomendador::parecidos(Producto $base, int $limite): Collection` de arrays `['producto' => Producto, 'porcentaje' => int, 'compartidas' => Collection<string>]`
- `Recomendador::masProbados(int $limite): Collection<Producto>`

- [ ] **Paso 1: tests que fallan**
  - `test_parecido_es_jaccard`: A {bergamota, pimienta, ambroxan, lavanda}, B {bergamota, pimienta, ámbar} → 2/5 → `40`.
  - `test_parecidos_ordena_por_porcentaje_y_nombre_y_excluye_base_inactivos_y_sin_stock`.
  - `test_parecidos_sin_notas_en_comun_no_aparecen` (porcentaje 0 se excluye).
  - `test_mas_probados_usa_ventas_confirmadas_90_dias_y_completa_con_destacados`: pedidos `confirmado` recientes cuentan, `pendiente`/`cancelado` y de hace 120 días no; si hay menos que `$limite`, completa con `destacado` y luego por nombre.
- [ ] **Paso 2:** FAIL. **Paso 3:** implementar (el estado "confirmado o posterior" = `confirmado|preparando|enviado|entregado`; la fecha de venta en Fase 1 es `pedidos.fecha`, en Fase 2 pasará a la de confirmación). **Paso 4:** PASS. **Paso 5:** Commit `feat: recomendador por notas y más probados`.

### Tarea 4: Layout público, encabezado con panel de marca e inicio

**Archivos:**
- Crear: `resources/views/components/tienda/layout.blade.php`, `components/tienda/encabezado.blade.php`, `components/tienda/panel-marca.blade.php`, `components/tienda/pie.blade.php`, `components/tienda/tarjeta-perfume.blade.php`, `resources/views/tienda/inicio.blade.php`, `app/Http/Controllers/Tienda/InicioController.php`, `app/View/Composers/EncabezadoComposer.php`, `app/Support/Moneda.php`
- Modificar: `routes/web.php` (`GET /` → `tienda.inicio`), `resources/css/app.css` (tokens `@theme`, fuentes), `tests/Feature/ExampleTest.php` (si deja de aplicar)
- Test: `tests/Feature/Tienda/InicioTest.php`, `tests/Unit/MonedaTest.php`

**Interfaces producidas:** `Moneda::gs(int|float $monto): string` → `'₲ 124.000'`; componente `<x-tienda.layout title="...">`; `<x-tienda.tarjeta-perfume :producto="$p" />` (marca, nombre, chips "5ml"/"10ml" y "Decant desde ₲ X"; un tamaño sin stock con tachado).

- [ ] **Paso 1: tests que fallan**
  - `MonedaTest`: `gs(124000) === '₲ 124.000'`, `gs(0) === '₲ 0'`, `gs(1500000) === '₲ 1.500.000'`.
  - `test_inicio_muestra_marcas_del_menu_en_orden_y_enlaces_informativos`: `assertSeeInOrder(['Chanel','Dior','Tom Ford', ...,'Todas las marcas','Termómetro olfativo','Cómo funciona'])`.
  - `test_inicio_muestra_hero_pasos_y_mas_probados`: ve "Perfumes de lujo", "en decant.", "Elegí tu perfume", "Elegí 5 o 10 ml", "Pedí por WhatsApp", "Los más probados" y 4 tarjetas.
  - `test_inicio_no_muestra_15ml_frasco_ni_bloques_descartados`: `assertDontSee('15 ml')`, `assertDontSee('15ml')`, `assertDontSee('Frasco completo')`, `assertDontSee('Para ella')`.
  - `test_panel_de_marca_lista_perfumes_por_genero`: el HTML del panel de Chanel contiene columnas "En portada", "Mujer", "Hombre", "Para compartir" con nombres de perfumes de esa marca y enlaces `/marcas/chanel?genero=mujer`.
- [ ] **Paso 2:** FAIL. **Paso 3:** implementar siguiendo la pantalla "1 · Inicio" y "Menú · Panel de cada marca" de la maqueta (panel con Alpine: abre con hover y con foco de teclado, cierra con Escape y con el botón X; en celular, botón de menú con la lista de marcas). Imagen del panel: `marcas.imagen` o un placeholder neutro. **Paso 4:** PASS + revisar visualmente en `php artisan serve` contra la maqueta a 375 px y 1440 px. **Paso 5:** Commit `feat(tienda): layout, encabezado con panel de marca e inicio`.

### Tarea 5: Marcas, catálogo y búsqueda

**Archivos:** Crear `app/Http/Controllers/Tienda/MarcaController.php` (`index`, `show`), `CatalogoController.php` (`index`), `BusquedaController.php` (`index`), `app/Support/Texto.php`, vistas `tienda/marcas/index.blade.php`, `tienda/marcas/show.blade.php`, `tienda/catalogo.blade.php`, `tienda/busqueda.blade.php`. Rutas: `tienda.marcas.index` `GET /marcas`, `tienda.marcas.show` `GET /marcas/{marca}`, `tienda.catalogo` `GET /perfumes`, `tienda.buscar` `GET /buscar`. Tests: `tests/Feature/Tienda/MarcasTest.php`, `CatalogoTest.php`, `BusquedaTest.php`, `tests/Unit/TextoTest.php`.

**Interfaces producidas:** `Texto::normalizar(string $s): string` (minúsculas, sin tildes, espacios colapsados). Query params del catálogo: `marca` (slug, múltiple), `genero` (`mujer|hombre|unisex`), `familia` (id de categoría, múltiple), `tamano` (`5|10`), `orden` (`probados|precio_asc|precio_desc|nombre`).

- [ ] **Paso 1: tests que fallan**
  - `TextoTest`: `normalizar('Hermès  Terre D’HERMÈS') === 'hermes terre d’hermes'`.
  - `test_indice_de_marcas_agrupa_a_z_con_cantidad`: ve las letras y "Dior" con su cantidad de perfumes visibles.
  - `test_pagina_de_marca_filtra_por_genero`: `/marcas/chanel?genero=hombre` ve "Bleu de Chanel" y no ve "Coco Mademoiselle"; las pestañas "Todos", "Mujer", "Hombre", "Para compartir" están presentes.
  - `test_marca_inexistente_da_404`.
  - `test_catalogo_combina_filtros_y_ordena` (marca + familia + tamaño + `orden=precio_asc`).
  - `test_catalogo_ignora_genero_invalido` (`genero=niños` → se comporta como "Todos", 200).
  - `test_busqueda_por_perfume_marca_y_nota_sin_tildes`: `q=hermes` encuentra productos de Hermès; `q=vainilla` encuentra los que tienen esa nota; `q=SAUVAGE` encuentra Sauvage.
  - `test_busqueda_sin_resultados_muestra_estado_vacio_con_whatsapp`: ve `No encontramos "savaje"` y un enlace `wa.me`.
- [ ] **Paso 2:** FAIL. **Paso 3:** implementar según pantallas 2, 3 y 9 (la búsqueda compara con `Texto::normalizar` en PHP para que funcione igual en SQLite y MySQL). **Paso 4:** PASS. **Paso 5:** Commit `feat(tienda): marcas, catálogo y búsqueda`.

### Tarea 6: Página del perfume

**Archivos:** Crear `app/Http/Controllers/Tienda/PerfumeController.php` (`show`), vista `tienda/perfume.blade.php`. Ruta `tienda.perfume` `GET /perfumes/{producto}`. Test: `tests/Feature/Tienda/PerfumeTest.php`.

- [ ] **Paso 1: tests que fallan**
  - `test_muestra_dos_decants_con_precio_y_notas`: ve "5 ml", "10 ml", los dos precios con `Moneda::gs`, las notas y "¿Por qué un decant?".
  - `test_tamano_sin_stock_aparece_deshabilitado`: la opción de 10 ml del producto sin stock tiene `disabled` y el texto "Sin stock".
  - `test_producto_inactivo_da_404`.
  - `test_tambien_te_puede_gustar_muestra_cuatro_parecidos`.
  - `test_no_muestra_15ml_ni_frasco`.
- [ ] **Paso 2:** FAIL. **Paso 3:** implementar según pantalla 4 y la versión celular (8): selección con radios accesibles (Alpine para el precio grande), cantidad entre 1 y el stock de la opción elegida, formulario `POST /carrito` (Tarea 7), barra fija inferior en celular. **Paso 4:** PASS + revisión visual. **Paso 5:** Commit `feat(tienda): página del perfume con decants`.

### Tarea 7: Carrito en sesión con recomendaciones

**Archivos:** Crear `app/Services/Carrito.php`, `app/Http/Controllers/Tienda/CarritoController.php` (`show`, `store`, `update`, `destroy`), `components/tienda/carrito-panel.blade.php`, vista `tienda/carrito.blade.php`. Rutas: `tienda.carrito` `GET /carrito`, `tienda.carrito.agregar` `POST /carrito`, `tienda.carrito.actualizar` `PATCH /carrito/{presentacion}`, `tienda.carrito.quitar` `DELETE /carrito/{presentacion}`. Tests: `tests/Feature/Tienda/CarritoTest.php`.

**Interfaces producidas:**
- `Carrito::agregar(ProductoPresentacion $p, int $cantidad): void` (suma si ya está; tope = stock)
- `Carrito::actualizar(int $presentacionId, int $cantidad): void` (0 elimina)
- `Carrito::lineas(): Collection` de `['presentacion' => ProductoPresentacion, 'cantidad' => int, 'subtotal' => int]`
- `Carrito::subtotal(): int`, `Carrito::unidades(): int`, `Carrito::vaciar(): void`, `Carrito::ultimoAgregado(): ?Producto`
- Sesión: clave `carrito` → `[presentacion_id => cantidad]`, clave `carrito_ultimo` → producto_id.

- [ ] **Paso 1: tests que fallan**
  - `test_agrega_y_suma_cantidades_y_muestra_confirmacion`: dos POST de 1 unidad → cantidad 2; redirect con flash; `GET /carrito` ve "Agregaste Sauvage · Decant 10 ml".
  - `test_no_supera_el_stock`: stock 3, pedir 5 → queda 3 y mensaje "Solo quedan 3".
  - `test_no_acepta_cantidades_invalidas`: `cantidad` = 0, -2, `'abc'` → error de validación, carrito sin cambios.
  - `test_no_agrega_presentacion_sin_stock_o_inactiva` (422/redirect con error).
  - `test_actualizar_a_cero_elimina_la_linea`.
  - `test_muestra_recomendados_con_notas_parecidas`: tras agregar Sauvage, ve "Con notas parecidas a Sauvage", 3 perfumes con "% parecido" y sus notas compartidas, y un botón que agrega el decant de 5 ml.
  - `test_carrito_vacio_muestra_estado_vacio`: ve "Tu carrito está vacío" y "Ver los más probados".
  - `test_contador_del_encabezado_muestra_unidades`.
- [ ] **Paso 2:** FAIL. **Paso 3:** implementar según pantallas 5 y 12; el panel lateral se abre (Alpine) tras agregar, con foco atrapado y cierre con Escape. **Paso 4:** PASS. **Paso 5:** Commit `feat(tienda): carrito con recomendados por notas`.

### Tarea 8: Checkout, pedido pendiente y WhatsApp

**Archivos:** Crear `config/tienda.php`, `app/Services/MensajeWhatsapp.php`, `app/Http/Requests/CheckoutRequest.php`, `app/Actions/CrearPedidoDesdeCarrito.php`, `app/Http/Controllers/Tienda/CheckoutController.php` (`show`, `store`), `app/Http/Controllers/Tienda/PedidoController.php` (`show`), vistas `tienda/checkout.blade.php`, `tienda/pedido-enviado.blade.php`. Rutas: `tienda.checkout` `GET|POST /checkout`, `tienda.pedido` `GET /pedido/{numero}`. Agregar a `.env.example`: `TIENDA_WHATSAPP=595900000000`, `TIENDA_ENVIO_ASUNCION=20000`. Tests: `tests/Feature/Tienda/CheckoutTest.php`, `tests/Unit/MensajeWhatsappTest.php`.

**Interfaces producidas:**
- `config('tienda.whatsapp')`, `config('tienda.envio_asuncion')`; métodos de entrega `retiro` (0), `asuncion` (config), `interior` (0)
- `MensajeWhatsapp::texto(Pedido $p): string` (texto exacto de spec §6.5) y `MensajeWhatsapp::url(Pedido $p): string` → `https://wa.me/{config('tienda.whatsapp')}?text=` + `rawurlencode(texto)`
- `CrearPedidoDesdeCarrito::handle(Carrito $c, array $datos, ?Cliente $cliente): Pedido` (transacción; estado `pendiente`; **no** descuenta stock)

- [ ] **Paso 1: tests que fallan**
  - `MensajeWhatsappTest::test_texto_exacto_envio_asuncion`: con el pedido de ejemplo de la spec (Sauvage 10 ml ×1 ₲ 60.000; Good Girl 5 ml ×2 ₲ 64.000; envío ₲ 20.000) el texto es **idéntico** al bloque de spec §6.5 (copiarlo al test tal cual).
  - `test_texto_retiro_e_interior`: líneas `Entrega: Retiro (a coordinar)` y `Envío al interior: a coordinar`; sin línea `Ref.:` si no hay referencia.
  - `test_url_de_whatsapp_codifica_numeral_tildes_y_saltos`: la URL contiene `%23MF-` y `%0A`, no contiene `#` ni espacios literales; `rawurldecode` de la parte `text` es igual al texto.
  - `test_checkout_crea_pedido_pendiente_y_redirige_a_whatsapp`: POST válido → `assertRedirect()` a una URL que empieza en `https://wa.me/595900000000?text=`; existe pedido `pendiente` con `numero`, `subtotal=124000`, `costo_envio=20000`, `total=144000`, 2 detalles con `precio_unitario` y `subtotal`; el carrito queda vacío; el stock **no** cambió.
  - `test_valida_campos_requeridos_segun_metodo`: retiro no exige ciudad/dirección; `asuncion` e `interior` sí.
  - `test_normaliza_celular_paraguayo`: `0981 234 567`, `0981234567` y `+595 981 234 567` se guardan como `0981234567`; `0981 23` y `0181234567` dan error "Revisá el número: deben ser 10 dígitos, ej. 0981 234 567."
  - `test_email_opcional_pero_valido`.
  - `test_checkout_con_carrito_vacio_redirige_al_carrito`.
  - `test_checkout_rechaza_linea_sin_stock_y_no_crea_pedido`: entre agregar y pagar, el stock baja a 0 → vuelve con error que nombra el perfume y tamaño; `Pedido::count() === 0`.
  - `test_cliente_logueado_precarga_datos_y_asocia_pedido`.
  - `test_pagina_pedido_enviado_muestra_numero_y_boton_whatsapp`.
- [ ] **Paso 2:** FAIL. **Paso 3:** implementar según pantallas 6 y 11 (pasos Carrito → Tus datos → WhatsApp; errores junto a cada campo; resumen fijo a la derecha en escritorio). **Paso 4:** PASS. **Paso 5:** Commit `feat(tienda): checkout con pedido pendiente y redirección a WhatsApp`.

### Tarea 9: Termómetro olfativo

**Archivos:** Crear `app/Services/TermometroOlfativo.php`, `app/Services/ClimaAsuncion.php`, `app/Http/Controllers/Tienda/TermometroController.php`, vista `tienda/termometro.blade.php` (+ script Alpine). Ruta `tienda.termometro` `GET /termometro-olfativo`. Tests: `tests/Unit/TermometroOlfativoTest.php`, `tests/Feature/Tienda/TermometroTest.php`.

**Interfaces producidas:**
- `TermometroOlfativo::zona(int $temperatura): array` → `['clave' => 'frio'|'templado'|'calor'|'calor_intenso', 'nombre', 'titulo', 'texto', 'notas_si' => [...], 'notas_no' => [...], 'dosis' => int]` con los valores de spec §6.6
- `TermometroOlfativo::dosis(int $temperatura, bool $noche, bool $humedo): int`
- `TermometroOlfativo::recomendaciones(int $temperatura, int $limite = 3): Collection<Producto>`
- `TermometroOlfativo::zonas(): array` (las 4, para pasarlas al JS como JSON) y `PROMEDIOS_MES = [1=>35,2=>34,3=>33,4=>29,5=>25,6=>22,7=>22,8=>25,9=>27,10=>30,11=>32,12=>34]`
- `ClimaAsuncion::actual(): array` → `['temperatura' => int, 'humedo' => bool, 'fuente' => 'open-meteo'|'promedio']`; `Http::timeout(3)` a `https://api.open-meteo.com/v1/forecast?latitude=-25.28&longitude=-57.63&current=temperature_2m,relative_humidity_2m`; húmedo si humedad ≥ 60 %; `Cache::remember('clima_asuncion', 1800, ...)`.

- [ ] **Paso 1: tests que fallan**
  - `test_bordes_de_zona`: 18→`frio`, 19→`templado`, 26→`templado`, 27→`calor`, 32→`calor`, 33→`calor_intenso`, 10 y 40 válidos.
  - `test_dosis_noche_y_humedad_con_limites`: 10°, noche → 4 (no 5); 36°, húmedo → 1 (no 0); 30°, noche y húmedo → 2.
  - `test_recomendaciones_priorizan_coincidencias_y_excluyen_notas_a_evitar`: a 36° un perfume con limón+menta va antes que uno con solo limón; uno con vainilla no aparece.
  - `test_clima_usa_open_meteo`: `Http::fake` con 31.6 °C y 70 % → `['temperatura'=>32,'humedo'=>true,'fuente'=>'open-meteo']`.
  - `test_si_open_meteo_falla_usa_promedio_del_mes`: `Http::fake` con 500 (y otro con `ConnectionException`) en octubre → `temperatura=30`, `fuente='promedio'`; la página responde 200 sin texto de error.
  - `test_pagina_muestra_termometro_meses_y_recomendaciones`: ve "Cada clima tiene su perfume.", "Usar el clima de hoy", los 12 meses y 3 recomendaciones.
- [ ] **Paso 2:** FAIL. **Paso 3:** implementar según la pantalla "Termómetro olfativo" (el prototipo de la maqueta ya tiene la interacción; la lógica de zonas viene de PHP como JSON, el JS solo pinta; las recomendaciones de las 4 zonas se calculan en el servidor y se envían todas para cambiar sin recargar). Agregar el aviso del termómetro en la franja del panel de marca. **Paso 4:** PASS. **Paso 5:** Commit `feat(tienda): termómetro olfativo`.

### Tarea 10: Ingresar / Crear cuenta (PI-12) y tests de Auth (PI-13)

**Archivos:** Modificar `resources/views/auth/login.blade.php`, `auth/register.blade.php` (usar `<x-tienda.layout>` y el diseño de la pantalla 7 con pestañas), `app/Http/Controllers/AuthController.php` solo si un test lo exige. Tests: `tests/Feature/Auth/LoginTest.php`, `RegistroTest.php`, `AccesoAdminTest.php`.

- [ ] **Paso 1: tests** (varios ya deberían pasar con el código actual; los que fallen guían cambios):
  - `test_login_admin_redirige_al_dashboard`, `test_login_cliente_redirige_a_cliente_home`
  - `test_credenciales_invalidas_vuelven_con_error_y_conservan_email`
  - `test_registro_crea_usuario_cliente_y_perfil_y_autentica`
  - `test_registro_rechaza_email_duplicado_y_contrasena_sin_confirmar`
  - `test_logout_invalida_la_sesion`
  - `test_admin_exige_rol`: invitado → redirect a `login`; cliente → 403; admin → 200 en `/admin/dashboard`
  - `test_vistas_de_auth_usan_el_nuevo_diseno`: login ve las pestañas "Ingresar" y "Crear cuenta" y el texto "Para comprar no hace falta cuenta"; enlace "seguí sin cuenta" a `/`.
- [ ] **Paso 2–4:** rojo → implementar vistas → verde. **Paso 5:** Commit `feat(auth): diseño de ingreso y registro + tests`.

### Tarea 11: Cómo funciona, 404, pulido y plan de pruebas (PI-13)

**Archivos:** Crear vista `tienda/como-funciona.blade.php` (ruta `tienda.como-funciona` `GET /como-funciona`), `resources/views/errors/404.blade.php`; crear `docs/pruebas/plan-de-pruebas-auth-catalogo.md`. Test: `tests/Feature/Tienda/PaginasInformativasTest.php`.

- [ ] **Paso 1: tests que fallan:** `test_como_funciona_explica_decant_tamanos_y_pedido` (ve "Perfumes de lujo,", "5 o 10 ml", "Lo pedís por WhatsApp" y las 4 preguntas frecuentes); `test_404_usa_diseno_de_la_tienda_con_salida` (ruta inexistente → 404 con enlace a inicio).
- [ ] **Paso 2–4:** rojo → implementar → verde.
- [ ] **Paso 5: auditoría sin emojis.** Test `test_vistas_publicas_no_contienen_emojis_ni_simbolos_icono`: recorre `resources/views/tienda`, `components/tienda`, `auth`, `errors` y falla si encuentra caracteres en los rangos de emoji (`\x{1F300}-\x{1FAFF}`, `\x{2600}-\x{27BF}`) o los símbolos `✕ ✓ → ▾`.
- [ ] **Paso 6: plan de pruebas PI-13.** `docs/pruebas/plan-de-pruebas-auth-catalogo.md`: objetivo, alcance (Auth y Catálogo, más carrito/checkout), entorno (`php artisan test`, SQLite en memoria), y una tabla **Caso · Precondición · Pasos · Resultado esperado · Test automatizado** con todos los tests de las Tareas 3, 5, 6, 7, 8 y 10 (nombre de clase::método), más cómo correrlos y el resultado de la última corrida.
- [ ] **Paso 7: revisión visual final.** `npm run build`, `php artisan serve`; comparar cada pantalla con la maqueta a 375 px y 1440 px; correr el detector de diseño si está disponible (`~/.agents/skills/impeccable/scripts/impeccable detect --json resources/views`) y corregir lo que marque.
- [ ] **Paso 8:** `php artisan test` → todo verde; `./vendor/bin/pint`. Commit `feat(tienda): cómo funciona, 404 y plan de pruebas PI-13`.

---

## Fase 2 (plan aparte)

El panel de administración (spec §6.9: dashboard, productos con género/notas, pedidos con ciclo de estados y descuento de stock, inventario, marcas/categorías/tamaños) se planifica en `docs/superpowers/plans/…-fase-2-panel-admin.md` cuando la Fase 1 esté terminada y revisada.
