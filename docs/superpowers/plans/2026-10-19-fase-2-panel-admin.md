# Fase 2 · Panel de administración de Mar Fragancias — Plan de implementación

> **Para agentes:** ejecutar tarea por tarea con TDD estricto (test que falla → código mínimo → test pasa → commit). Los pasos usan casillas; marcalas al completarlas. No avanzar con tests en rojo.

**Objetivo:** rediseñar y completar el panel de administración (pantallas 14–20 de las maquetas): dashboard, productos con decants, pedidos con ciclo de estados y descuento de stock, inventario tipo kardex, marcas, categorías y tamaños.

**Arquitectura:** la lógica de negocio va en dos servicios con transacciones (`Inventario` y `CicloPedido`); los controladores de admin solo validan, llaman al servicio y devuelven vistas. Las vistas usan un layout de admin nuevo como componente Blade (`<x-admin.layout>`), con los tokens de Tailwind ya definidos en la Fase 1. El stock de `producto_presentacion` cambia **únicamente** a través de `Inventario`.

**Stack:** igual que la Fase 1 (Laravel 13, PHP 8.3+, PHPUnit, Tailwind 4, Alpine.js, iconos Phosphor).

**Spec:** `docs/superpowers/specs/2026-10-18-tienda-y-admin-design.md`, sección **6.9** y §7 y §8 (leerlas completas). **Maquetas:** `docs/diseno/maquetas/diseno-final.html`, pantallas 14 a 20.

**Rama:** `feature/panel-admin` (ya creada desde `main`, que incluye la Fase 1). Commit por tarea. **No hacer push.** Comandos relativos a `src/`; PHP en `~/.config/herd-lite/bin`.

## Restricciones globales

- Todo lo de la Fase 1 sigue vigente: solo decants de 5 y 10 ml, **ningún emoji** ni símbolo de texto como icono (Phosphor `light`), tokens de color de `resources/css/app.css`, voseo, montos `₲ 124.000`.
- Rutas bajo `auth` + `rol:administrador` (middleware existente). Mantener los nombres `admin.*` existentes; las URLs del admin siguen usando **id** (no slug), como dejó la Fase 1.
- Estados de pedido exactos: `pendiente`, `confirmado`, `preparando`, `enviado`, `entregado`, `cancelado`.
- Stock crítico = presentación activa con stock **≤ 3**.
- Métricas del mes por **fecha de confirmación** (registro `confirmado` en `pedido_estados`), zona horaria `America/Asuncion`.
- No editar migraciones existentes; no romper la tienda pública ni sus 152 tests.

## Foco de revisión

1. **Doble clic en "Confirmar"** (dos requests seguidos): el stock se descuenta **una sola vez**; la segunda transición falla porque el pedido ya no está `pendiente`. → Tarea 3, `test_confirmar_dos_veces_descuenta_una_sola_vez`.
2. **Salida manual mayor que el stock** o cantidad 0/negativa/texto: se rechaza y el stock no cambia. → Tarea 2, `test_salida_no_deja_stock_negativo` y `test_cantidad_invalida_se_rechaza`.
3. **Formulario de producto que envía `stock` manipulado**: se ignora; el stock solo cambia por movimientos. → Tarea 6, `test_editar_producto_ignora_stock_enviado`.
4. **Cancelar un pedido `enviado` o `entregado`**, o saltar de `pendiente` a `enviado`: se rechaza con mensaje, sin cambios. → Tarea 3, `test_transiciones_invalidas_se_rechazan`.
5. **Eliminar un perfume que ya tiene pedidos o movimientos de inventario**: no se borra (rompería el historial); se ofrece ocultarlo (`activo = false`). → Tarea 6, `test_no_elimina_producto_con_pedidos` y `test_no_elimina_producto_con_movimientos` (regla ampliada por el orquestador).

---

### Tarea 1: Datos del ciclo de pedidos e inventario

**Archivos:** Crear `database/migrations/2026_10_19_000002_create_pedido_estados_table.php`, `..._000003_add_auditoria_to_movimientos_inventario.php`, `app/Models/PedidoEstado.php`. Modificar `app/Models/Pedido.php`, `MovimientoInventario.php`. Test: `tests/Feature/Admin/ModeloPedidosTest.php`.

**Interfaces producidas:**
- `Pedido::ESTADOS` (los 6, en orden) y `Pedido::TRANSICIONES = ['pendiente'=>['confirmado','cancelado'],'confirmado'=>['preparando','cancelado'],'preparando'=>['enviado','cancelado'],'enviado'=>['entregado'],'entregado'=>[],'cancelado'=>[]]`
- `Pedido::puedePasarA(string $estado): bool`, `Pedido::estados(): HasMany` (historial ordenado), `Pedido::fechaConfirmacion(): ?Carbon`
- `Pedido::etiquetaEstado(): string` (`Pendiente`, `Confirmado`, …) y `Pedido::claseInsignia(): string` (color por estado como en la maqueta)
- `pedido_estados`: `id`, `pedido_id` (FK), `estado`, `usuario_id` nullable (FK `usuarios`), `created_at`
- `movimientos_inventario`: + `usuario_id` nullable (FK `usuarios`), `stock_resultante` int nullable (los existentes quedan null)
- Al crear un pedido desde el checkout (`CrearPedidoDesdeCarrito` de la Fase 1) se registra su estado inicial `pendiente` en `pedido_estados`.

- [x] **Paso 1: tests que fallan:** `test_transiciones_permitidas_segun_tabla` (recorre la tabla completa: permitidas → true, el resto → false); `test_pedido_creado_desde_checkout_registra_estado_pendiente`; `test_fecha_confirmacion_sale_del_historial`; `test_etiquetas_de_estado`.
- [x] **Paso 2:** FAIL. **Paso 3:** implementar. **Paso 4:** suite completa PASS. **Paso 5:** Commit `feat(admin): historial de estados de pedido y auditoría de movimientos`.

### Tarea 2: Servicio de inventario

**Archivos:** Crear `app/Services/Inventario.php`, `app/Exceptions/StockInsuficiente.php`. Test: `tests/Feature/Admin/InventarioServicioTest.php`.

**Interfaces producidas:**
- `Inventario::registrar(ProductoPresentacion $p, string $tipo, int $cantidad, string $motivo, ?Usuario $usuario = null): MovimientoInventario` — `$tipo` `entrada|salida`; `$cantidad > 0`; dentro de `DB::transaction` con `lockForUpdate()` sobre la presentación; actualiza `stock`, guarda `stock_resultante`, `usuario_id` y `fecha = now()`. Salida que deja stock < 0 → lanza `StockInsuficiente` (con presentación y disponible) y no cambia nada.
- `Inventario::criticas(): Collection<ProductoPresentacion>` (activas con stock ≤ 3, con producto y tamaño cargados).

- [x] **Paso 1: tests que fallan:** `test_entrada_suma_y_registra_stock_resultante`; `test_salida_resta`; `test_salida_no_deja_stock_negativo` (stock 2, salida 3 → excepción, stock sigue 2, sin movimiento nuevo); `test_cantidad_invalida_se_rechaza` (0 y −1 → `InvalidArgumentException`); `test_tipo_invalido_se_rechaza`; `test_criticas_lista_stock_menor_o_igual_a_tres_solo_activas`.
- [x] **Pasos 2–4:** rojo → verde. **Paso 5:** Commit `feat(admin): servicio de inventario con kardex`.

### Tarea 3: Servicio del ciclo de pedidos

**Archivos:** Crear `app/Services/CicloPedido.php`, `app/Exceptions/TransicionInvalida.php`. Test: `tests/Feature/Admin/CicloPedidoTest.php`.

**Interfaces producidas:**
- `CicloPedido::pasarA(Pedido $pedido, string $estado, Usuario $admin): Pedido` — en una transacción: relee el pedido con `lockForUpdate()`; si `! puedePasarA($estado)` lanza `TransicionInvalida`; al pasar a `confirmado` registra una **salida** por cada detalle con motivo `Venta MF-XXXX` (vía `Inventario::registrar`); al pasar a `cancelado` desde `confirmado` o `preparando` registra **entradas** `Cancelación MF-XXXX`; desde `pendiente` no toca stock. Guarda el estado y su fila en `pedido_estados` con el usuario.
- Si falta stock al confirmar, no cambia nada y la excepción `StockInsuficiente` identifica el perfume y el tamaño.

- [x] **Paso 1: tests que fallan:** `test_confirmar_descuenta_stock_y_registra_salidas`; `test_confirmar_sin_stock_suficiente_falla_sin_cambios` (estado sigue `pendiente`, ningún stock cambia, ningún movimiento); `test_confirmar_dos_veces_descuenta_una_sola_vez`; `test_cancelar_confirmado_devuelve_stock`; `test_cancelar_pendiente_no_toca_stock`; `test_transiciones_invalidas_se_rechazan` (`pendiente→enviado`, `enviado→cancelado`, `entregado→pendiente`); `test_cada_cambio_queda_en_el_historial_con_usuario`.
- [x] **Pasos 2–4:** rojo → verde. **Paso 5:** Commit `feat(admin): ciclo de estados de pedido con descuento de stock`.

**Notas del bloque 1 (Tareas 1–3, 2026-10-19):**
- Verificados RED → GREEN por tarea; suite final del bloque: 191 tests, 1248 assertions. Pint correcto. Se aplicaron las dos migraciones nuevas a la base local con `php artisan migrate`, sin `fresh` ni cambios a migraciones existentes.
- `CrearPedidoDesdeCarrito` vive en `app/Actions`, no en `app/Services`. El historial inicial registra el usuario del cliente autenticado o NULL para invitada; las fechas se escriben con el reloj de la aplicación. No se inventó historial para pedidos anteriores ni stock resultante para movimientos históricos.
- Insignias: se exponen las clases de la maqueta (`b-pend`, `b-conf`, `b-prep`, `b-env`, `b-ent`, `b-can`); su CSS corresponde a la Tarea 4.
- La firma `int $cantidad` se conserva: texto no numérico produce `TypeError`; cero/negativos, tipo inválido y motivo vacío producen `InvalidArgumentException`. La validación de campos HTTP sigue pendiente para Tarea 7.
- CicloPedido relee/bloquea el pedido y procesa presentaciones en orden de id para reducir deadlocks entre pedidos. Se probaron doble confirmación con instancia obsoleta, falta de stock en la segunda línea con rollback integral, cancelación desde preparando, reposición intermedia y fallo al guardar auditoría.
- Las pruebas son SQLite en memoria; los bloqueos concurrentes reales de MySQL quedan por verificar. No se adelantaron las pantallas/controladores de Tareas 4–9.

### Tarea 4: Layout del admin y dashboard

**Archivos:** Crear `resources/views/components/admin/layout.blade.php` (menú lateral de la maqueta: logo en crema, grupos VENTAS · CATÁLOGO · STOCK, contadores), `app/View/Composers/AdminMenuComposer.php`, `app/Services/MetricasAdmin.php`, `app/Http/Controllers/Admin/DashboardController.php`. Modificar `routes/web.php` (`admin.dashboard` → controlador), `resources/views/admin/dashboard.blade.php`. Las vistas admin existentes pasan a usar `<x-admin.layout>` en sus tareas. Test: `tests/Feature/Admin/DashboardTest.php`.

**Interfaces producidas:** `MetricasAdmin::resumen(?Carbon $mes = null): array` → `['pendientes' => int, 'pendiente_mas_viejo' => ?Carbon, 'ventas_mes' => int, 'pedidos_mes' => int, 'decants_mes' => int, 'porcentaje_5ml' => int, 'mas_vendidos' => Collection<['presentacion','unidades']> (top 5), 'criticas' => Collection]`. Cuenta pedidos cuya **confirmación** cayó en el mes y que no están `cancelado`.

- [x] **Paso 1: tests que fallan:** `test_dashboard_requiere_admin` (invitado → login, cliente → 403); `test_menu_muestra_grupos_y_contadores` (ve VENTAS, CATÁLOGO, STOCK, contador de pendientes y de stock crítico); `test_metricas_cuentan_por_fecha_de_confirmacion` (pedido creado el 31/10 y confirmado el 1/11 cuenta en noviembre); `test_metricas_excluyen_cancelados_y_pendientes`; `test_mas_vendidos_top_5_por_unidades`; `test_porcentaje_de_5ml`.
- [x] **Paso 2–4:** rojo → verde, comparando con la pantalla 14 a 1440 px y 768 px (el admin no necesita ser mobile-first, pero no debe romperse en tablet). **Paso 5:** Commit `feat(admin): layout nuevo y dashboard con métricas`.

### Tarea 5: Pedidos y detalle de pedido

**Archivos:** Crear `app/Http/Controllers/Admin/PedidoController.php` (`index`, `show`, `transicion`), vistas `admin/pedidos/index.blade.php`, `admin/pedidos/show.blade.php`. Rutas `admin.pedidos.index` `GET /admin/pedidos`, `admin.pedidos.show` `GET /admin/pedidos/{pedido}`, `admin.pedidos.transicion` `POST /admin/pedidos/{pedido}/estado` (campo `estado`). El `PedidoController` vacío de la raíz de controladores se elimina si nada lo usa. Test: `tests/Feature/Admin/PedidosTest.php`.

- [x] **Paso 1: tests que fallan:** `test_lista_filtra_por_estado_con_contadores`; `test_busca_por_numero_nombre_o_telefono`; `test_detalle_muestra_productos_totales_cliente_e_historial`; `test_detalle_tiene_enlace_whatsapp_a_la_clienta` (`https://wa.me/595981234567` a partir de `0981234567`); `test_boton_confirmar_cambia_estado_y_descuenta_stock`; `test_error_de_stock_se_muestra_con_el_perfume_y_tamano`; `test_transicion_invalida_vuelve_con_error`; `test_solo_se_muestran_botones_de_transiciones_validas`.
- [x] **Pasos 2–4:** rojo → verde, comparando con las pantallas 17 y 18. **Paso 5:** Commit `feat(admin): pedidos y detalle con ciclo de estados`.

### Tarea 6: Productos con decants, género y notas

**Archivos:** Mover a `app/Http/Controllers/Admin/ProductoController.php` (o reescribir el existente conservando las rutas `admin.productos.*`), crear `app/Http/Requests/Admin/ProductoRequest.php`, reescribir vistas `admin/productos/{index,create,edit}.blade.php`. Test: `tests/Feature/Admin/ProductosTest.php` (y adaptar los tests existentes que dependan de las vistas viejas).

- [x] **Paso 1: tests que fallan:**
  - `test_lista_muestra_stock_de_5_y_10_ml_y_marca_criticos`
  - `test_filtros_genero_sin_stock_inactivos_y_busqueda`
  - `test_crear_perfume_con_genero_notas_y_precios` (crea notas nuevas escritas a mano y reutiliza existentes sin duplicar; precios de 5 y 10 ml)
  - `test_stock_inicial_se_registra_como_entrada` (al crear, `stock_inicial` de cada tamaño > 0 genera movimiento `entrada` "Stock inicial")
  - `test_editar_producto_ignora_stock_enviado`
  - `test_validaciones` (nombre, marca, categoría y género requeridos; género fuera de la lista; precio ≥ 0; al menos un tamaño con precio)
  - `test_no_elimina_producto_con_pedidos` (vuelve con mensaje y ofrece ocultarlo); `test_no_elimina_producto_con_movimientos`; `test_elimina_producto_sin_pedidos` (sin movimientos tampoco)
- [x] **Pasos 2–4:** rojo → verde, comparando con las pantallas 15 y 16. **Paso 5:** Commit `feat(admin): productos con género, notas y decants`.

**Notas del bloque 2 (Tareas 4–6):**
- RED → GREEN por tarea. Suite: 246 tests / 1500 assertions; Pint y build correctos. Sin migraciones nuevas ni cambios a las anteriores.
- Métricas: límites del mes de Asunción convertidos a UTC; pruebas del segundo anterior y posterior a ambos límites. Pedidos sin historial de confirmación no se cuentan como ventas.
- Pantallas 14–18 comparadas a 1440 y 768 px. En tablet: KPI en dos columnas, paneles/detalle apilados, foto debajo del formulario y tablas con scroll local; sin overflow de página ni errores de consola. Capturas y logs en `.superpowers/sdd/2026-10-19-fase-2-panel-admin/`. Datos visuales sintéticos en SQLite separada, sin tocar la BD de desarrollo.
- Menú habilita Pedidos en T5; Inventario queda como texto hasta su ruta en T7. Clientes y Ver reporte no se agregaron (fuera de alcance). Se respetan IDs de admin y nombres de rutas existentes.
- El orquestador amplió expresamente la protección de eliminación: pedidos **o movimientos** bloquean el borrado y ofrecen ocultar; solo sin ambos se elimina. Test `test_no_elimina_producto_con_movimientos` verificado RED → GREEN; se conserva kardex.
- Notas: selección múltiple existente más campo de nuevas notas separadas por coma/salto, reutilizadas sin duplicar mediante normalización. Tamaños permitidos por nombre 5 ml/10 ml. Precio cero válido. Si se vacía precio de una presentación existente, se desactiva conservando precio/stock/historial; stock inicial positivo requiere precio. No se modifica stock al editar, incluso si se envía `stock_inicial` manipulado.
- Foto: validación, almacenamiento y conservación al editar; una excepción en la transacción limpia la nueva subida. Eliminación comprueba relaciones bajo bloqueo antes de borrar nada.
- Paginación compartida y mensajes de validación nuevos en español; no se cambió el locale global heredado. Búsqueda normalizada en PHP como Fase1 para equivalencia SQLite/MySQL; implica filtrar candidatos en memoria al buscar.
- Revisión independiente del bloque: sin hallazgos críticos/importantes. Dos menores diferidos: buscar el texto literal `0` omite el filtro (por truthiness de PHP); al deseleccionar todas las notas y fallar otra validación, el formulario puede restaurar las notas persistidas. Corregir con regresiones en el próximo bloque si el orquestador lo incorpora.
- Se mantiene el contrato heredado de precios con hasta dos decimales. La revisión señaló que la tienda redondea al mostrar y el carrito trunca al calcular; no se cambió esta regla heredada ni se restringió a enteros sin decisión del orquestador. Antes de cargar precios fraccionarios hay que unificar ese criterio.
- Pendiente: concurrencia real con MySQL y tareas 7–9. Los controles nativos de archivo usan el idioma del navegador. El detector visual solo señaló la ilustración mini-vial pública ya aprobada; no hubo hallazgos nuevos.

### Tarea 7: Inventario (kardex)

**Archivos:** Crear `app/Http/Controllers/Admin/InventarioController.php` (`index`, `create`, `store`), vistas `admin/inventario/{index,create}.blade.php`. Rutas `admin.inventario.index` `GET /admin/inventario`, `admin.inventario.create`, `admin.inventario.store`. Test: `tests/Feature/Admin/InventarioTest.php`.

- [x] **Paso 1: tests que fallan:** `test_kardex_muestra_movimientos_con_stock_resultante_y_usuario` ("Sistema" cuando `usuario_id` es null); `test_filtros_entradas_salidas_criticos_y_perfume`; `test_registrar_movimiento_manual` (motivo requerido); `test_movimiento_manual_que_deja_stock_negativo_se_rechaza`.
- [x] **Pasos 2–4:** rojo → verde, comparando con la pantalla 19. **Paso 5:** Commit `feat(admin): inventario tipo kardex`.

### Tarea 8: Marcas, categorías y tamaños

**Archivos:** Modificar `MarcaController`, `CategoriaController`, `TamanoController` y sus vistas (pasar a `<x-admin.layout>`), `routes/web.php` (`tamanos` solo `index`; borrar las vistas `tamanos/create|edit`). Test: `tests/Feature/Admin/CatalogoAdminTest.php`.

- [x] **Paso 1: tests que fallan:** `test_marcas_muestran_cantidad_de_perfumes`; `test_marca_gestiona_destacada_orden_menu_e_imagen` (y el menú de la tienda refleja el cambio); `test_no_permite_mas_de_ocho_marcas_destacadas`; `test_no_elimina_marca_o_categoria_con_perfumes`; `test_elimina_marca_sin_perfumes`; `test_tamanos_es_solo_lectura` (`index` 200; `create`, `store`, `edit`, `update`, `destroy` ya no existen → 404/405).
- [x] **Pasos 2–4:** rojo → verde, comparando con la pantalla 20. **Paso 5:** Commit `feat(admin): marcas, categorías y tamaños`.

### Tarea 9: Cierre de la Fase 2

- [ ] **Pendiente del orquestador:** configurar `APP_TIMEZONE` / `config(app.timezone)` como `America/Asuncion` (también `.env.example`) y probar que una fecha persistida se muestra en hora de Asunción en detalle de pedido y kardex. Conservar interpretación de fechas históricas para evitar desplazarlas dos veces.

- [ ] **Paso 1:** cambiar `Recomendador::masProbados` para contar por **fecha de confirmación** (como pide la spec) con su test `test_mas_probados_usa_fecha_de_confirmacion`.
- [ ] **Paso 2:** extender el test de auditoría sin emojis a `resources/views/admin` y `components/admin`.
- [ ] **Paso 3:** agregar a `docs/pruebas/` un `plan-de-pruebas-panel-admin.md` con la misma estructura del de PI-13 (caso · precondición · pasos · resultado esperado · test automatizado) para todos los tests de las Tareas 1–8.
- [ ] **Paso 4:** revisión visual de las pantallas 14–20 contra la maqueta; `npm run build`; `./vendor/bin/pint --dirty`; `php artisan test` → todo verde. Si es posible, correr migraciones y seeders contra **MySQL** (docker-compose) y anotar el resultado.
- [ ] **Paso 5:** Commit `chore(admin): cierre de Fase 2` y responder con el informe final (commits, tests, desvíos, pendientes).
