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

- [x] **Paso 1: tests que fallan**
  - `test_seed_crea_solo_tamanos_5_y_10_ml`: tras `$this->seed()`, `Tamano::pluck('nombre')->sort()->values()->all() === ['10 ml','5 ml']` y no existe ningún tamaño con `15` ni `frasco`.
  - `test_seed_tiene_ocho_marcas_del_menu_en_orden`: `Marca::delMenu()->pluck('nombre')->all() === ['Chanel','Dior','Tom Ford','Creed','Guerlain','Hermès','Yves Saint Laurent','Maison Francis Kurkdjian']`.
  - `test_cada_producto_visible_tiene_genero_notas_y_dos_decants`: cada `Producto::visibles()` tiene ≥ 3 notas y presentaciones de 5 y 10 ml con precio > 0.
- [x] **Paso 2:** correr → FAIL.
- [x] **Paso 3:** Seeders: ≥ 24 perfumes de ejemplo repartidos entre las 8 marcas del menú y otras (Carolina Herrera, Versace, Paco Rabanne, Parfums de Marly, Xerjoff, Lattafa, Armaf), con género, familia (categoría existente), notas reales del perfume y precios de ejemplo (5 ml entre ₲ 15.000 y ₲ 65.000; 10 ml ≈ 1,7×). Al menos uno por marca destacada en cada género cuando aplique; uno con 10 ml en stock 0 (para ver el estado deshabilitado). Mantener usuarios y roles existentes.
- [x] **Paso 4:** `php artisan test` → PASS. `php artisan migrate:fresh --seed` (con MySQL o SQLite local) sin errores.
- [x] **Paso 5:** Commit `feat(seed): marcas de lujo, notas y decants de ejemplo`.

**Registro Tarea 2 (2026-10-18):** RED inicial: el rol cliente faltante impedía todos los seeds; tras agregarlo (autorizado), fallaron menú vacío, catálogo sin presentaciones y cobertura de género. Tamaños y usuarios ya estaban correctos y sus tests pasaron sin cambios adicionales. GREEN final: 17 tests, 421 assertions. Datos: 15 marcas, 8 destacadas en orden, 32 perfumes, 64 presentaciones, precios 5 ml entre 15.000 y 65.000 y 10 ml = 1,7×; Sauvage de 10 ml con stock 0. Cada marca destacada tiene Mujer, Hombre y Para compartir. Se conservan usuarios y administrador; se agrega el rol cliente faltante.

Verificación: `php artisan migrate:fresh --seed --force --no-interaction` correcto sobre una SQLite temporal dedicada en `.superpowers/sdd/2026-10-18-fase-1-tienda-publica/task2.sqlite`, configurada con `DB_CONNECTION=sqlite` y `DB_DATABASE` absoluto; no se sobrescribió la base local. `vendor/bin/pint --dirty --format agent` correcto. Las 35 rutas existentes conservan método, URL pública, nombre y controlador; los parámetros admin incluyen ahora binding explícito `:id`. No se modificó ninguna migración anterior.

Notas de ejecución: revisión por el programador en este bloque; el orquestador conserva la revisión de cada bloque. MySQL no fue ejecutado en esta máquina. La maqueta aprobada se abrió y revisó en Firefox con un perfil temporal; no se modificaron vistas en estas tareas. Se detiene la ejecución antes de Tarea 3.

Fuentes oficiales consultadas para las notas de muestra: [Chanel 1957](https://www.chanel.com/us/fragrance/p/122160/1957-eau-de-parfum-amber-musky-floral/), [Tom Ford Noir Extreme](https://www.tomfordbeauty.com/products/noir-extreme-eau-de-parfum), [Creed Silver Mountain Water](https://www.creedfragrance.com/c/the-house-of-creed/the-book/silver-mountain-water/), [Guerlain Oud Essentiel](https://www.guerlain.com/es/es-es/p/oud-essentiel-eau-de-parfum-P013225.html), [YSL Tuxedo](https://www.yslbeauty.com/int/fragrance/unisex-fragrances/tuxedo-eau-de-parfum/WW-51373YSL.html), [MFK masculin Pluriel](https://www.franciskurkdjian.com/eu-en/p/masculin-pluriel-eau-de-toilette-RA132121.html).

Riesgos previos detectados al instalar dependencias: `composer audit` informa 4 avisos en `laravel/framework`, `league/commonmark` (2) y `symfony/http-foundation`. Las versiones de esos tres paquetes coinciden con `main`; no se actualizaron fuera del alcance del plan. Referencias: [Laravel](https://github.com/advisories/GHSA-jh5r-qr3c-85q8), [CommonMark HTML](https://github.com/advisories/GHSA-97jj-33gv-5xf9), [CommonMark DoS](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8), [HttpFoundation](https://symfony.com/cve-2026-108082).

### Tarea 3: Recomendaciones (parecido por notas y "más probados")

**Archivos:** Crear `app/Services/Recomendador.php`. Test: `tests/Unit/RecomendadorTest.php` (con `RefreshDatabase`, en `tests/Feature/Catalogo/` si necesita base).

**Interfaces producidas:**
- `Recomendador::parecido(Producto $a, Producto $b): int` → porcentaje 0–100 (Jaccard, redondeo a entero)
- `Recomendador::parecidos(Producto $base, int $limite): Collection` de arrays `['producto' => Producto, 'porcentaje' => int, 'compartidas' => Collection<string>]`
- `Recomendador::masProbados(int $limite): Collection<Producto>`

- [x] **Paso 1: tests que fallan**
  - `test_parecido_es_jaccard`: A {bergamota, pimienta, ambroxan, lavanda}, B {bergamota, pimienta, ámbar} → 2/5 → `40`.
  - `test_parecidos_ordena_por_porcentaje_y_nombre_y_excluye_base_inactivos_y_sin_stock`.
  - `test_parecidos_sin_notas_en_comun_no_aparecen` (porcentaje 0 se excluye).
  - `test_mas_probados_usa_ventas_confirmadas_90_dias_y_completa_con_destacados`: pedidos `confirmado` recientes cuentan, `pendiente`/`cancelado` y de hace 120 días no; si hay menos que `$limite`, completa con `destacado` y luego por nombre.
- [x] **Paso 2:** FAIL. **Paso 3:** implementar (el estado "confirmado o posterior" = `confirmado|preparando|enviado|entregado`; la fecha de venta en Fase 1 es `pedidos.fecha`, en Fase 2 pasará a la de confirmación). **Paso 4:** PASS. **Paso 5:** Commit `feat: recomendador por notas y más probados`.

**Registro Tarea 3:** RED: 4 tests por servicio inexistente. GREEN: 21 tests, 429 assertions. Parecido Jaccard (incluido conjunto vacío), orden por porcentaje/nombre, límite y exclusión de inactivos/sin stock; ventas confirmadas o posteriores de 90 días (borde incluido), completadas con destacados y nombre. Ventas agrupadas en SQL por producto; fecha `pedidos.fecha`, según el plan.

### Tarea 4: Layout público, encabezado con panel de marca e inicio

**Archivos:**
- Crear: `resources/views/components/tienda/layout.blade.php`, `components/tienda/encabezado.blade.php`, `components/tienda/panel-marca.blade.php`, `components/tienda/pie.blade.php`, `components/tienda/tarjeta-perfume.blade.php`, `resources/views/tienda/inicio.blade.php`, `app/Http/Controllers/Tienda/InicioController.php`, `app/View/Composers/EncabezadoComposer.php`, `app/Support/Moneda.php`
- Modificar: `routes/web.php` (`GET /` → `tienda.inicio`), `resources/css/app.css` (tokens `@theme`, fuentes), `tests/Feature/ExampleTest.php` (si deja de aplicar)
- Test: `tests/Feature/Tienda/InicioTest.php`, `tests/Unit/MonedaTest.php`

**Interfaces producidas:** `Moneda::gs(int|float $monto): string` → `'₲ 124.000'`; componente `<x-tienda.layout title="...">`; `<x-tienda.tarjeta-perfume :producto="$p" />` (marca, nombre, chips "5ml"/"10ml" y "Decant desde ₲ X"; un tamaño sin stock con tachado).

- [x] **Paso 1: tests que fallan**
  - `MonedaTest`: `gs(124000) === '₲ 124.000'`, `gs(0) === '₲ 0'`, `gs(1500000) === '₲ 1.500.000'`.
  - `test_inicio_muestra_marcas_del_menu_en_orden_y_enlaces_informativos`: `assertSeeInOrder(['Chanel','Dior','Tom Ford', ...,'Todas las marcas','Termómetro olfativo','Cómo funciona'])`.
  - `test_inicio_muestra_hero_pasos_y_mas_probados`: ve "Perfumes de lujo", "en decant.", "Elegí tu perfume", "Elegí 5 o 10 ml", "Pedí por WhatsApp", "Los más probados" y 4 tarjetas.
  - `test_inicio_no_muestra_15ml_frasco_ni_bloques_descartados`: `assertDontSee('15 ml')`, `assertDontSee('15ml')`, `assertDontSee('Frasco completo')`, `assertDontSee('Para ella')`.
  - `test_panel_de_marca_lista_perfumes_por_genero`: el HTML del panel de Chanel contiene columnas "En portada", "Mujer", "Hombre", "Para compartir" con nombres de perfumes de esa marca y enlaces `/marcas/chanel?genero=mujer`.
- [x] **Paso 2:** FAIL. **Paso 3:** implementar siguiendo la pantalla "1 · Inicio" y "Menú · Panel de cada marca" de la maqueta (panel con Alpine: abre con hover y con foco de teclado, cierra con Escape y con el botón X; en celular, botón de menú con la lista de marcas). Imagen del panel: `marcas.imagen` o un placeholder neutro. **Paso 4:** PASS + revisar visualmente en `php artisan serve` contra la maqueta a 375 px y 1440 px. **Paso 5:** Commit `feat(tienda): layout, encabezado con panel de marca e inicio`.

**Registro Tarea 4:** RED: inicio 404, Moneda y componentes inexistentes. GREEN: 28 tests, 462 assertions; build y Pint correctos. Fonts Montserrat/Inter/IBM Plex Mono se sirven localmente por Vite (se ajustó `vite.config.js`). El smoke test vuelve a `/`, con RefreshDatabase. Composer específico del encabezado carga marcas/productos sin consultas desde las vistas. Imágenes sin datos usan placeholders neutros.

Comparación Firefox real headless con perfil temporal, 1440 y 375 px: inicio, panel de marca en escritorio y lista de marcas móvil. Hero, paleta, fuentes, botellas geométricas, pasos y tarjetas comparados con las secciones s1/n1 de la maqueta; escritorio conserva la composición, móvil apila hero y pasos y usa dos columnas. La maqueta s1/n1 mantiene un layout fijo al reducirse a 375 px; la adaptación móvil aplica la spec. Datos de productos/precios provienen del seeder y ranking, no de los ejemplos estáticos. El pie omite número y horarios sin confirmar y amplía enlaces a objetivos táctiles de 44 px. No se agrega «Para regalar» de la maqueta porque la spec pide solo las cuatro columnas del panel.

Prueba reproducible sin dependencias nuevas: con Artisan en puerto 8013 y Firefox `--headless --no-remote --profile <temporal> --remote-debugging-port 9223`, ejecutar `node tests/Browser/tienda.mjs task4` desde `src/`. Se puede configurar `TIENDA_URL`, `FIREFOX_BIDI_URL` y `TIENDA_CAPTURAS`. Verifica Alpine, foco, cierre con botón/Escape, cuenta/lupa y menú móvil, sin overflow horizontal. Dos fallos observados y corregidos RED → GREEN: reapertura al devolver foco y doble lupa móvil. Capturas/refs: `.superpowers/sdd/2026-10-18-fase-1-tienda-publica/{inicio-1440,inicio-375,panel-marca-1440,menu-marcas-375,maqueta-inicio-1440,maqueta-inicio-375,maqueta-panel-marca-1440,maqueta-panel-marca-375}.png`. No se probó hardware móvil.

URLs de tareas siguientes se enlazan sin crear rutas prematuras. Única repetición del color fuera de CSS: `meta theme-color`, que requiere un color literal. Servidor visual usa SQLite temporal con datos del bloque anterior y `SESSION_DRIVER=file` (no hay tabla sessions en las migraciones originales).

### Tarea 5: Marcas, catálogo y búsqueda

**Archivos:** Crear `app/Http/Controllers/Tienda/MarcaController.php` (`index`, `show`), `CatalogoController.php` (`index`), `BusquedaController.php` (`index`), `app/Support/Texto.php`, vistas `tienda/marcas/index.blade.php`, `tienda/marcas/show.blade.php`, `tienda/catalogo.blade.php`, `tienda/busqueda.blade.php`. Rutas: `tienda.marcas.index` `GET /marcas`, `tienda.marcas.show` `GET /marcas/{marca}`, `tienda.catalogo` `GET /perfumes`, `tienda.buscar` `GET /buscar`. Tests: `tests/Feature/Tienda/MarcasTest.php`, `CatalogoTest.php`, `BusquedaTest.php`, `tests/Unit/TextoTest.php`.

**Interfaces producidas:** `Texto::normalizar(string $s): string` (minúsculas, sin tildes, espacios colapsados). Query params del catálogo: `marca` (slug, múltiple), `genero` (`mujer|hombre|unisex`), `familia` (id de categoría, múltiple), `tamano` (`5|10`), `orden` (`probados|precio_asc|precio_desc|nombre`).

- [x] **Paso 1: tests que fallan**
  - `TextoTest`: `normalizar('Hermès  Terre D’HERMÈS') === 'hermes terre d’hermes'`.
  - `test_indice_de_marcas_agrupa_a_z_con_cantidad`: ve las letras y "Dior" con su cantidad de perfumes visibles.
  - `test_pagina_de_marca_filtra_por_genero`: `/marcas/chanel?genero=hombre` ve "Bleu de Chanel" y no ve "Coco Mademoiselle"; las pestañas "Todos", "Mujer", "Hombre", "Para compartir" están presentes.
  - `test_marca_inexistente_da_404`.
  - `test_catalogo_combina_filtros_y_ordena` (marca + familia + tamaño + `orden=precio_asc`).
  - `test_catalogo_ignora_genero_invalido` (`genero=niños` → se comporta como "Todos", 200).
  - `test_busqueda_por_perfume_marca_y_nota_sin_tildes`: `q=hermes` encuentra productos de Hermès; `q=vainilla` encuentra los que tienen esa nota; `q=SAUVAGE` encuentra Sauvage.
  - `test_busqueda_sin_resultados_muestra_estado_vacio_con_whatsapp`: ve `No encontramos "savaje"` y un enlace `wa.me`.
- [x] **Paso 2:** FAIL. **Paso 3:** implementar según pantallas 2, 3 y 9 (la búsqueda compara con `Texto::normalizar` en PHP para que funcione igual en SQLite y MySQL). **Paso 4:** PASS. **Paso 5:** Commit `feat(tienda): marcas, catálogo y búsqueda`.

**Registro Tarea 5:** RED: rutas públicas 404 y Texto inexistente; 404 de marca desconocida ya pasaba y no se forzó un fallo artificial. GREEN: 39 tests, 512 assertions. Marcas A–Z con conteos de productos visibles; marca por slug con pestañas; catálogo con marcas/familias múltiples (parámetros `marca[]`/`familia[]`, aceptando también un valor), género, tamaño y los cuatro órdenes. El precio ordena por el mismo «desde» de la tarjeta, mínimo de presentaciones activas válidas; el filtro de tamaño requiere presentación activa con stock. Género inválido se ignora, como indica el plan. La búsqueda normaliza letras latinas, conserva el apóstrofo curvo y colapsa espacios en PHP, sin depender de la collation. Se probaron consultas vacías, `q` como array y escaping del texto.

Ajuste de test respecto del texto literal del plan: las exclusiones por género se comprueban en `#resultados-perfumes` y en los productos enviados a la vista; un `assertDontSee` sobre toda la página fallaría por los nombres legítimos de los paneles ocultos del encabezado. La búsqueda usa GET/Enter según la interfaz del plan; no incluye autocomplete incremental ni resaltado de coincidencias de la maqueta. La URL de WhatsApp usa `config('tienda.whatsapp', '595900000000')`; el fallback es el placeholder aprobado del plan, pendiente del número real/configuración en Tarea 8.

Comparación visual adicional en Firefox a 1440/375 px: índice A–Z, marca Chanel, catálogo Mujer, catálogo filtrado por Chanel, búsqueda «sau» y búsqueda vacía «savaje», contra pantallas 2/3/9 y la composición de marca descrita en spec (no hay una pantalla 3b separada en el HTML). Escritorio: filtros laterales y tres tarjetas por fila; móvil: filtros plegables y dos tarjetas. Se conservan jerarquía, tipografías y paleta; conteos, catálogo y precios son los datos del seeder. Las referencias se capturan como viewport para no ensanchar la imagen por el overflow de las maquetas fijas a 375 px; la implementación se captura completa. Capturas en `.superpowers/sdd/2026-10-18-fase-1-tienda-publica/`: `{marcas,marca-chanel,catalogo,catalogo-filtrado,busqueda,busqueda-vacia}-{1440,375}.png` y referencias `maqueta-{marcas,catalogo,busqueda}-{1440,375}.png`. Se vuelve a correr `node tests/Browser/tienda.mjs task4` y `task5`, ambos correctos, incluyendo columnas, filtros móviles, ausencia de overflow y contraste de textos de panel/filtros.

Fallos encontrados y corregidos con RED → GREEN: CSS compartido imponía cuatro columnas al catálogo; singular/plural de conteos; el salvia sobre fondo salvia daba 4,35:1. Ese texto usa ahora navy sobre sage-bg (14,31:1), manteniendo los tokens aprobados. El detector Impeccable se ejecutó una vez sin hallazgos (`[]`); comprobación adicional sin emojis/símbolos-icono en las vistas nuevas. Pint, build y suite completa correctos. Se conservan las 35 rutas previas y se agregan 5 rutas públicas en el bloque; migraciones intactas. MySQL y hardware móvil siguen sin verificarse. Se detiene antes de Tarea 6; revisión del bloque a cargo del orquestador.

### Tarea 6: Página del perfume

**Archivos:** Crear `app/Http/Controllers/Tienda/PerfumeController.php` (`show`), vista `tienda/perfume.blade.php`. Ruta `tienda.perfume` `GET /perfumes/{producto}`. Test: `tests/Feature/Tienda/PerfumeTest.php`.

- [x] **Paso 1: tests que fallan**
  - `test_muestra_dos_decants_con_precio_y_notas`: ve "5 ml", "10 ml", los dos precios con `Moneda::gs`, las notas y "¿Por qué un decant?".
  - `test_tamano_sin_stock_aparece_deshabilitado`: la opción de 10 ml del producto sin stock tiene `disabled` y el texto "Sin stock".
  - `test_producto_inactivo_da_404`.
  - `test_tambien_te_puede_gustar_muestra_cuatro_parecidos`.
  - `test_no_muestra_15ml_ni_frasco`.
- [x] **Paso 2:** FAIL. **Paso 3:** implementar según pantalla 4 y la versión celular (8): selección con radios accesibles (Alpine para el precio grande), cantidad entre 1 y el stock de la opción elegida, formulario `POST /carrito` (Tarea 7), barra fija inferior en celular. **Paso 4:** PASS + revisión visual. **Paso 5:** Commit `feat(tienda): página del perfume con decants`.

**Registro Tarea 6:** RED: cuatro casos fallan por ruta inexistente; el caso negativo 404 se conserva y se verifica junto con la ruta activa en GREEN. Suite: 44 tests / 525 assertions. Pint y build correctos. Firefox `node tests/Browser/tienda.mjs task6`: selección cambia precio, cantidad se limita al stock y barra fija solo móvil; sin desborde a 375/1440. Capturas `perfume-{375,1440}.png`, referencias pantallas 4/8 en `.superpowers/sdd/2026-10-18-fase-1-tienda-publica/`. Comparación inspeccionada: dos columnas desktop, galería encima del detalle móvil, dos radios y barra inferior; recomendaciones 4/2 columnas. Datos/precios reales del seeder (Sauvage 10 ml sin stock) difieren del ejemplo; se elige el primer tamaño disponible (5 ml). No se simulan miniaturas de fotos inexistentes. Se conserva el encabezado/pie compartido. POST preparado con URL final, implementación en Tarea 7. Un valor esperado del harness usaba precio de maqueta para Bleu; corregido según seeder (40.000 × 1,7 = 68.000), sin cambio productivo.

### Tarea 7: Carrito en sesión con recomendaciones

**Archivos:** Crear `app/Services/Carrito.php`, `app/Http/Controllers/Tienda/CarritoController.php` (`show`, `store`, `update`, `destroy`), `components/tienda/carrito-panel.blade.php`, vista `tienda/carrito.blade.php`. Rutas: `tienda.carrito` `GET /carrito`, `tienda.carrito.agregar` `POST /carrito`, `tienda.carrito.actualizar` `PATCH /carrito/{presentacion}`, `tienda.carrito.quitar` `DELETE /carrito/{presentacion}`. Tests: `tests/Feature/Tienda/CarritoTest.php`.

**Interfaces producidas:**
- `Carrito::agregar(ProductoPresentacion $p, int $cantidad): void` (suma si ya está; tope = stock)
- `Carrito::actualizar(int $presentacionId, int $cantidad): void` (0 elimina)
- `Carrito::lineas(): Collection` de `['presentacion' => ProductoPresentacion, 'cantidad' => int, 'subtotal' => int]`
- `Carrito::subtotal(): int`, `Carrito::unidades(): int`, `Carrito::vaciar(): void`, `Carrito::ultimoAgregado(): ?Producto`
- Sesión: clave `carrito` → `[presentacion_id => cantidad]`, clave `carrito_ultimo` → producto_id.

- [x] **Paso 1: tests que fallan**
  - `test_agrega_y_suma_cantidades_y_muestra_confirmacion`: dos POST de 1 unidad → cantidad 2; redirect con flash; `GET /carrito` ve "Agregaste Sauvage · Decant 10 ml".
  - `test_no_supera_el_stock`: stock 3, pedir 5 → queda 3 y mensaje "Solo quedan 3".
  - `test_no_acepta_cantidades_invalidas`: `cantidad` = 0, -2, `'abc'` → error de validación, carrito sin cambios.
  - `test_no_agrega_presentacion_sin_stock_o_inactiva` (422/redirect con error).
  - `test_actualizar_a_cero_elimina_la_linea`.
  - `test_muestra_recomendados_con_notas_parecidas`: tras agregar Sauvage, ve "Con notas parecidas a Sauvage", 3 perfumes con "% parecido" y sus notas compartidas, y un botón que agrega el decant de 5 ml.
  - `test_carrito_vacio_muestra_estado_vacio`: ve "Tu carrito está vacío" y "Ver los más probados".
  - `test_contador_del_encabezado_muestra_unidades`.
- [x] **Paso 2:** FAIL. **Paso 3:** implementar según pantallas 5 y 12; el panel lateral se abre (Alpine) tras agregar, con foco atrapado y cierre con Escape. **Paso 4:** PASS. **Paso 5:** Commit `feat(tienda): carrito con recomendados por notas`.

**Registro Tarea 7:** RED por rutas/servicio inexistentes; GREEN 17 casos nuevos (incluye matrices), suite completa 61 tests / 588 assertions. Cantidades enteras positivas al agregar, cero elimina al actualizar; negativos, fracciones, texto y arrays rechazados sin cambios. Cantidades mayores al stock se limitan y avisan; suma protegida incluso con `PHP_INT_MAX`. Se rechazan producto inactivo, presentación inactiva/sin stock y tamaños ajenos a 5/10 ml. Quitar funciona aunque la presentación haya desaparecido.

Panel nativo `<dialog>` + Alpine, sin dependencias nuevas. Firefox mostró que el último Tab podía ir a la barra del navegador: foco circular explícito, Escape y devolución al carrito comprobados. Otro RED de navegador detectó que los botones de cantidad enviaban el valor previo al repintado de Alpine: envío ahora en `$nextTick`. Harness `task7` verde a 375/1440; capturas `carrito-panel-*`, `carrito-*`, `carrito-vacio-*` y `maqueta-carrito-*`, inspeccionadas. Ancho desktop corregido a los 380 px de la maqueta; móvil ocupa el ancho completo. Se agregan botones Actualizar/Quitar con objetivos de 44 px, aumentando el alto respecto de la maqueta; recomendaciones usan nombres, porcentaje, notas y botón sin repetir miniaturas decorativas. Datos/porcentajes corresponden al algoritmo, no a cifras ilustrativas. Corrección RED→GREEN de singular `1 unidad`. Enlace checkout preparado para Tarea 8.

### Tarea 8: Checkout, pedido pendiente y WhatsApp

**Archivos:** Crear `config/tienda.php`, `app/Services/MensajeWhatsapp.php`, `app/Http/Requests/CheckoutRequest.php`, `app/Actions/CrearPedidoDesdeCarrito.php`, `app/Http/Controllers/Tienda/CheckoutController.php` (`show`, `store`), `app/Http/Controllers/Tienda/PedidoController.php` (`show`), vistas `tienda/checkout.blade.php`, `tienda/pedido-enviado.blade.php`. Rutas: `tienda.checkout` `GET|POST /checkout`, `tienda.pedido` `GET /pedido/{numero}`. Agregar a `.env.example`: `TIENDA_WHATSAPP=595900000000`, `TIENDA_ENVIO_ASUNCION=20000`. Tests: `tests/Feature/Tienda/CheckoutTest.php`, `tests/Unit/MensajeWhatsappTest.php`.

**Interfaces producidas:**
- `config('tienda.whatsapp')`, `config('tienda.envio_asuncion')`; métodos de entrega `retiro` (0), `asuncion` (config), `interior` (0)
- `MensajeWhatsapp::texto(Pedido $p): string` (texto exacto de spec §6.5) y `MensajeWhatsapp::url(Pedido $p): string` → `https://wa.me/{config('tienda.whatsapp')}?text=` + `rawurlencode(texto)`
- `CrearPedidoDesdeCarrito::handle(Carrito $c, array $datos, ?Cliente $cliente): Pedido` (transacción; estado `pendiente`; **no** descuenta stock)

- [x] **Paso 1: tests que fallan**
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
- [x] **Paso 2:** FAIL. **Paso 3:** implementar según pantallas 6 y 11 (pasos Carrito → Tus datos → WhatsApp; errores junto a cada campo; resumen fijo a la derecha en escritorio). **Paso 4:** PASS. **Paso 5:** Commit `feat(tienda): checkout con pedido pendiente y redirección a WhatsApp`.

**Registro Tarea 8:** RED registrado en `task8-red.log` (servicio y rutas ausentes); GREEN de los casos del plan y ampliaciones. Suite completa final: 99 tests / 813 assertions. Mensaje de Asunción comparado literalmente con §6.5; URL con `%23MF-`, `%26`, tildes y `%0A`, sin fragmento; retiro usa `Envío: ₲ 0`, interior `Envío al interior: a coordinar`, referencia solo si existe. Celular: se eliminan espacios Unicode y se convierte `+595` a `0`; se rechazan letras, arrays y números incompletos. Barrio opcional (la spec solo exige ciudad y dirección); retiro guarda dirección/ciudad/barrio/referencia NULL. Asunción preseleccionada como en la maqueta, con tarifa visible.

La acción vuelve a consultar presentaciones bajo transacción y `lockForUpdate`, toma precios actuales, verifica producto/presentación/tamaño/cantidad/stock y crea pedido pendiente y detalles antes de vaciar sesión; no registra movimientos ni descuenta stock. POST de checkout bloquea la sesión para evitar doble envío simultáneo; se comprueba también reenvío secuencial con carrito vacío. Pruebas agregadas: precios/costo cambiados, presentación eliminada, producto desactivado, payload con total/estado/cliente manipulados, stock insuficiente para la cantidad (no solo stock cero), y eliminación recuperable desde carrito de una presentación desaparecida. Este último caso falló primero por faltar un control para quitar la línea invisible (`task8-eliminada-red.log`). Se mantienen las 40 rutas previas y se agregan 8 en Tareas 6–8; ninguna migración modificada.

**Decisiones técnicas:** el recibo `/pedido/{numero}` exige la sesión que hizo el pedido o su cliente autenticado: evita enumerar números secuenciales y exponer el mensaje con datos personales. Una invitada que pierda esa sesión no podrá abrir el recibo desde otro dispositivo; el pedido sigue guardado. La sesión conserva enlace al último pedido, visible en carrito vacío, para recuperar WhatsApp sin volver a crear un pedido. Se agregó test de acceso ajeno 404. Se mantiene el encabezado/pie compartido según spec §6.1 aunque las maquetas 6/11 usan encabezado reducido. No se incluye la burbuja ilustrativa de WhatsApp de pantalla 6 (el mensaje exacto se genera al guardar y se prueba en PHP); no se publica el plazo ilustrativo 24–48 h porque no fue confirmado por la tienda. Número y tarifa siguen configurables con los placeholders aprobados.

**Comparación visual:** Firefox headless con perfil temporal propio; harness `task8` a 375/1440 comprueba tres métodos, resumen actualizado, campos de envío deshabilitados para retiro, error de celular junto al campo y datos conservados, recuperación del recibo y URL codificada. Capturas `checkout-*`, `checkout-error-*`, `pedido-enviado-*` y referencias `maqueta-checkout-*`/`maqueta-pedido-enviado-*` en `.superpowers/sdd/2026-10-18-fase-1-tienda-publica/`, inspeccionadas. Desktop dos columnas con resumen sticky; móvil formulario y resumen apilados, sin desborde. La prueba guarda pedidos solo en SQLite de demostración y usa `fetch` con redirección manual: no conecta con WhatsApp ni envía mensajes. El verde WA original falló contraste ≥4,5 (`task8-browser.log` inicial): el token derivado `wa-action` mezcla 85 % WA con navy; el check corregido pasa. Detector Impeccable ejecutado una vez: `[]`. Pint/build correctos; harness de detalle y carrito repetido tras integración, verde. No se verificó MySQL ni un teléfono físico. Menú subrayado y autocomplete se mantienen anotados exclusivamente para Tarea 11.

**Revisión de código del bloque:** revisión independiente de solo lectura encontró un P2: arrays rechazados por validación seguían en `old()` y rompían `value` con `htmlspecialchars(array)`. Matriz de nueve campos RED→GREEN: solo se repueblan escalares, método inválido vuelve a Asunción y se ve el error. El test conserva explícitamente la cookie al seguir el redirect (la serialización JSON de sesión y los reinicios de sesión del helper HTTP requieren mantenerla para verificar también el error visible). Sin otros bloqueos encontrados; revisión humana del bloque a cargo del orquestador. Pint y suite final 99/99, 813 assertions; regresión Firefox checkout 375/1440 verde después del arreglo. Se detiene antes de Tarea 9, sin push.

### Tarea 9: Termómetro olfativo

**Archivos:** Crear `app/Services/TermometroOlfativo.php`, `app/Services/ClimaAsuncion.php`, `app/Http/Controllers/Tienda/TermometroController.php`, vista `tienda/termometro.blade.php` (+ script Alpine). Ruta `tienda.termometro` `GET /termometro-olfativo`. Tests: `tests/Unit/TermometroOlfativoTest.php`, `tests/Feature/Tienda/TermometroTest.php`.

**Interfaces producidas:**
- `TermometroOlfativo::zona(int $temperatura): array` → `['clave' => 'frio'|'templado'|'calor'|'calor_intenso', 'nombre', 'titulo', 'texto', 'notas_si' => [...], 'notas_no' => [...], 'dosis' => int]` con los valores de spec §6.6
- `TermometroOlfativo::dosis(int $temperatura, bool $noche, bool $humedo): int`
- `TermometroOlfativo::recomendaciones(int $temperatura, int $limite = 3): Collection<Producto>`
- `TermometroOlfativo::zonas(): array` (las 4, para pasarlas al JS como JSON) y `PROMEDIOS_MES = [1=>35,2=>34,3=>33,4=>29,5=>25,6=>22,7=>22,8=>25,9=>27,10=>30,11=>32,12=>34]`
- `ClimaAsuncion::actual(): array` → `['temperatura' => int, 'humedo' => bool, 'fuente' => 'open-meteo'|'promedio']`; `Http::timeout(3)` a `https://api.open-meteo.com/v1/forecast?latitude=-25.28&longitude=-57.63&current=temperature_2m,relative_humidity_2m`; húmedo si humedad ≥ 60 %; `Cache::remember('clima_asuncion', 1800, ...)`.

- [x] **Paso 1: tests que fallan**
  - `test_bordes_de_zona`: 18→`frio`, 19→`templado`, 26→`templado`, 27→`calor`, 32→`calor`, 33→`calor_intenso`, 10 y 40 válidos.
  - `test_dosis_noche_y_humedad_con_limites`: 10°, noche → 4 (no 5); 36°, húmedo → 1 (no 0); 30°, noche y húmedo → 2.
  - `test_recomendaciones_priorizan_coincidencias_y_excluyen_notas_a_evitar`: a 36° un perfume con limón+menta va antes que uno con solo limón; uno con vainilla no aparece.
  - `test_clima_usa_open_meteo`: `Http::fake` con 31.6 °C y 70 % → `['temperatura'=>32,'humedo'=>true,'fuente'=>'open-meteo']`.
  - `test_si_open_meteo_falla_usa_promedio_del_mes`: `Http::fake` con 500 (y otro con `ConnectionException`) en octubre → `temperatura=30`, `fuente='promedio'`; la página responde 200 sin texto de error.
  - `test_pagina_muestra_termometro_meses_y_recomendaciones`: ve "Cada clima tiene su perfume.", "Usar el clima de hoy", los 12 meses y 3 recomendaciones.
- [x] **Paso 2:** FAIL. **Paso 3:** implementar según la pantalla "Termómetro olfativo" (el prototipo de la maqueta ya tiene la interacción; la lógica de zonas viene de PHP como JSON, el JS solo pinta; las recomendaciones de las 4 zonas se calculan en el servidor y se envían todas para cambiar sin recargar). Agregar el aviso del termómetro en la franja del panel de marca. **Paso 4:** PASS. **Paso 5:** Commit `feat(tienda): termómetro olfativo`.

**Registro Tarea 9:** RED por servicios/ruta ausentes; GREEN 20 casos nuevos, suite 119 tests / 847 assertions. Bordes, orden/límites de dosis, coincidencias y exclusiones, activo/stock, consulta Open-Meteo, caché y vencimiento, HTTP500/conexión/datos incompletos cubiertos. Otro RED detectó mes UTC equivocado al inicio de noviembre; fallback usa America/Asuncion. Timeout de conexión y respuesta 3 s, sin retries. Fallback cacheado 30 min y humedad seca por defecto al no disponer de datos; se etiqueta como promedio del mes sin error visible. La temperatura del control se limita a 10–40, conservando lectura real en el encabezado.

Alpine solo selecciona zona/dosis/textos precalculados en PHP para 31 temperaturas y cuatro combinaciones día/noche/seco/húmedo. Recomendaciones de las cuatro zonas enviadas juntas; orden por coincidencias y nombre. Comparación normalizada exacta de nombres de notas: no se inventa intensidad ni se transforma `oud muy intenso` o `vainilla pesada` en otra nota; el catálogo no tiene intensidad. Se conserva el texto de la maqueta. Si la tienda necesita equivalencias cualitativas, habrá que definirlas explícitamente.

Firefox `task9` verde 375/1440: bordes, dosis, meses y restaurar clima. RED visual de meses sin colores corregido con tonos por zona; gradiente del termómetro, tarjetas blancas, franja anual y recomendaciones comparadas con pantalla n2. Capturas `termometro-*`/`maqueta-termometro-*` en workspace SDD, inspeccionadas; desktop dos columnas, móvil apilado y meses en 4 columnas, sin desborde. Meses con fondos suaves y texto navy para contraste; muestra productos/precios reales y clima actual en lugar de ejemplo36°. Aviso en panel de marca ya existía: conectado a ruta nombrada. Pint y build correctos. Pendientes Tarea11 intactos.

### Tarea 10: Ingresar / Crear cuenta (PI-12) y tests de Auth (PI-13)

**Archivos:** Modificar `resources/views/auth/login.blade.php`, `auth/register.blade.php` (usar `<x-tienda.layout>` y el diseño de la pantalla 7 con pestañas), `app/Http/Controllers/AuthController.php` solo si un test lo exige. Tests: `tests/Feature/Auth/LoginTest.php`, `RegistroTest.php`, `AccesoAdminTest.php`.

- [x] **Paso 1: tests** (varios ya deberían pasar con el código actual; los que fallen guían cambios):
  - `test_login_admin_redirige_al_dashboard`, `test_login_cliente_redirige_a_cliente_home`
  - `test_credenciales_invalidas_vuelven_con_error_y_conservan_email`
  - `test_registro_crea_usuario_cliente_y_perfil_y_autentica`
  - `test_registro_rechaza_email_duplicado_y_contrasena_sin_confirmar`
  - `test_logout_invalida_la_sesion`
  - `test_admin_exige_rol`: invitado → redirect a `login`; cliente → 403; admin → 200 en `/admin/dashboard`
  - `test_vistas_de_auth_usan_el_nuevo_diseno`: login ve las pestañas "Ingresar" y "Crear cuenta" y el texto "Para comprar no hace falta cuenta"; enlace "seguí sin cuenta" a `/`.
- [x] **Paso 2–4:** rojo → implementar vistas → verde. **Paso 5:** Commit `feat(auth): diseño de ingreso y registro + tests`.

**Registro Tarea 10:** RED inicial: 19 casos, 9 ya verdes (comportamiento Auth existente) y 10 rojos por diseño/entrada inválida; test adicional dentro de la matriz reproduce contraseña array de seis elementos confirmada causando500. GREEN 19/19, suite final 138 tests / 971 assertions. Login admin/cliente, error con email conservado, registro usuario+rol cliente+perfil+hash/autenticación, email duplicado/confirmación, logout con sesión invalidada y acceso invitado/cliente/admin a las cinco páginas admin cubiertos. Matrices de arrays verifican redirect y retorno al formulario con cookie conservada; vistas repueblan solo escalares y nunca contraseñas.

AuthController modificado solo por defectos demostrados: contraseña debe ser string en login/registro (evita500 en hasher); email de registro max150 corresponde a la columna existente (SQLite no imponía el límite). Mensajes en español. Sin cambio de política de longitud mínima de contraseña (6) ni redirecciones. Dos componentes pequeños comparten marco y campos de ambas vistas. Se conservan celular/dirección opcionales para perfil. Se omiten Recordarme y recuperación de contraseña del dibujo: no existen implementación, columna remember_token ni rutas de recuperación, y no son parte de los comportamientos Auth de la spec/plan. Texto no promete Mis pedidos (fuera de esta fase).

Firefox `task10` verde a 375/1440: dos columnas desktop/apilado móvil, pestaña/ruta activa, login inválido y registro con confirmación incorrecta, valores conservados y contraseñas vacías. Capturas `login-*`, `login-error-*`, `registro-*`, `registro-error-*`, referencias `maqueta-auth-*` en workspace SDD, inspeccionadas. Encabezado/pie compartido según spec, formulario con ancho máximo y controles44px; registro extiende la composición de pantalla7 con campos existentes. Sin desborde ni usuarios creados por prueba visual. Pint/build correctos; detector del bloque ejecutado una vez: `[]`. 48 rutas previas conservadas y 1 nueva (termómetro); ninguna migración modificada. MySQL, teléfono físico y concurrencia real fuera de estas verificaciones. Revisión del bloque a cargo del orquestador; se detiene antes de Tarea11. No push.

### Tarea 11: Cómo funciona, 404, pulido y plan de pruebas (PI-13)

**Retoques indicados por el orquestador (punto 11a):**
- [x] Marca activa del menú: subrayado como en la maqueta; contorno solo para `:focus-visible`.
- [x] Perfume a 375 px: ocultar el botón Agregar normal y dejar solo la barra fija, como pantalla 8.
- [x] Termómetro: fondos por zona frío #eef3fb, templado #f2f6f1, calor #fff7e8, calor intenso #fff1e8; transición suave y reduced motion.
- [x] Búsqueda: sugerencias mientras se escribe (perfumes, marcas y notas), con coincidencia resaltada como en pantalla 9; conservar Enter a resultados.

**Orden y commits autorizados para cerrar la tarea:** (a) cuatro retoques, (b) Cómo funciona/404, (c) auditoría sin emojis, (d) documento PI-13, (e) revisión visual/Pint/suite completa. Un commit al terminar cada punto; si se interrumpe, WIP explícito y estado exacto aquí.

- [x] **11a:** cuatro retoques completos, tests PHP y Firefox normales/reduced motion.
- [x] **11b:** Cómo funciona y 404 con tests.
- [x] **11c:** auditoría sin emojis.
- [ ] **11d:** documento PI-13.
- [ ] **11e:** revisión visual, Pint y suite final.

**Registro 11c:** Auditoría recursiva de las cuatro carpetas públicas, con archivo y línea de cada hallazgo. Prueba negativa: fixture Blade temporal no enrutada con seis caracteres prohibidos (emoji, símbolo meteorológico y cuatro símbolos de ícono); detectó las seis líneas en RED y se eliminó en finally. Las vistas reales ya cumplían la regla: sin cambios artificiales de interfaz. GREEN de suite completa: 150 tests / 1012 assertions; Pint correcto. Evidencia RED/GREEN en workspace SDD.

**Registro 11b:** RED de ambas páginas, GREEN de explicación/FAQ y404 con logo/salidas. FAQ con details nativo y respuestas basadas en spec: plazos y pago coordinados por WhatsApp, sin inventar días ni métodos de pago. Layout permite omitir panel de carrito en404 (ruta inexistente no inicializa sesión web). Revisión visual de ambas pantallas en punto11e.

**Registro 11a:** RED PHPUnit (marca activa/resaltado/fragmento ausentes) y Firefox (doble CTA, marca sin subrayado, tonos incorrectos y transición ausente); GREEN 147 tests / 1002 assertions. Marca activa por ruta de marca/perfume o filtro único; abierto/activo subrayado y contorno reservado a focus-visible. Un solo CTA visible por viewport. Cuatro colores exactos y transición200ms; perfil Firefox propio con ui.prefersReducedMotion=1 verifica duración0 y matchMedia activo. Búsqueda incremental con debounce200ms, AbortController/versionado y fragmento HTML de la misma ruta; Enter conserva GET completo. Resaltado sin tildes conserva grafemas/espacios y escapa HTML antes de agregar mark. RED de navegador detectó que $el era el input al disparar @input: corregido con x-ref explícito al formulario. Sugerencias de marcas/notas/perfumes y Enter verificadas a375/1440; capturas busqueda-viva-* inspeccionadas. Sin dependencias nuevas.

**Archivos:** Crear vista `tienda/como-funciona.blade.php` (ruta `tienda.como-funciona` `GET /como-funciona`), `resources/views/errors/404.blade.php`; crear `docs/pruebas/plan-de-pruebas-auth-catalogo.md`. Test: `tests/Feature/Tienda/PaginasInformativasTest.php`.

- [x] **Paso 1: tests que fallan:** `test_como_funciona_explica_decant_tamanos_y_pedido` (ve "Perfumes de lujo,", "5 o 10 ml", "Lo pedís por WhatsApp" y las 4 preguntas frecuentes); `test_404_usa_diseno_de_la_tienda_con_salida` (ruta inexistente → 404 con enlace a inicio).
- [x] **Paso 2–4:** rojo → implementar → verde.
- [x] **Paso 5: auditoría sin emojis.** Test `test_vistas_publicas_no_contienen_emojis_ni_simbolos_icono`: recorre `resources/views/tienda`, `components/tienda`, `auth`, `errors` y falla si encuentra caracteres en los rangos de emoji (`\x{1F300}-\x{1FAFF}`, `\x{2600}-\x{27BF}`) o los símbolos `✕ ✓ → ▾`.
- [ ] **Paso 6: plan de pruebas PI-13.** `docs/pruebas/plan-de-pruebas-auth-catalogo.md`: objetivo, alcance (Auth y Catálogo, más carrito/checkout), entorno (`php artisan test`, SQLite en memoria), y una tabla **Caso · Precondición · Pasos · Resultado esperado · Test automatizado** con todos los tests de las Tareas 3, 5, 6, 7, 8 y 10 (nombre de clase::método), más cómo correrlos y el resultado de la última corrida.
- [ ] **Paso 7: revisión visual final.** `npm run build`, `php artisan serve`; comparar cada pantalla con la maqueta a 375 px y 1440 px; correr el detector de diseño si está disponible (`~/.agents/skills/impeccable/scripts/impeccable detect --json resources/views`) y corregir lo que marque.
- [ ] **Paso 8:** `php artisan test` → todo verde; `./vendor/bin/pint`. Commit `feat(tienda): cómo funciona, 404 y plan de pruebas PI-13`.

---

## Fase 2 (plan aparte)

El panel de administración (spec §6.9: dashboard, productos con género/notas, pedidos con ciclo de estados y descuento de stock, inventario, marcas/categorías/tamaños) se planifica en `docs/superpowers/plans/…-fase-2-panel-admin.md` cuando la Fase 1 esté terminada y revisada.
