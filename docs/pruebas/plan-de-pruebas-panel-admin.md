# Panel de administración · Plan de pruebas de Fase 2

Fecha: 2026-10-19. Rama: `feature/panel-admin`.

Fuentes: [spec §6.9/7/8](../superpowers/specs/2026-10-18-tienda-y-admin-design.md), [plan](../superpowers/plans/2026-10-19-fase-2-panel-admin.md), [maquetas 14–20](../diseno/maquetas/diseno-final.html).

## Objetivo y entorno

Verificar administración exclusiva por rol, métricas por confirmación, edición de catálogo sin manipular stock, ciclo transaccional, kardex conservado y catálogos. La regla aprobada por el orquestador bloquea eliminación de perfumes con pedidos **o movimientos**. Tamaños solo 5/10 ml. Cada fila traza un método; todas sus variantes `TestWith` se ejecutan. Archivos en `src/tests/Feature/Admin`, namespace `Tests\Feature\Admin`.

PHP8.5, Laravel 13.29, PHPUnit 12.5.34. SQLite `:memory:`, `RefreshDatabase`, datos propios por test, sesiones/cache array. Sin servicios externos ni WhatsApp real. Subidas con `Storage::fake` cuando corresponde. Reloj controlado en pruebas temporales. App `America/Asuncion`; fechas de negocio (pedido.fecha, historial.created_at, movimiento.fecha/created_at) se almacenan y leen como UTC y se convierten al mostrarlas. No se alteran migraciones ni fechas históricas. Otras marcas de tiempo técnicas siguen configuración Laravel.

```bash
export PATH="$HOME/.config/herd-lite/bin:$PATH"
cd src
php artisan test
php artisan test tests/Feature/Admin
php artisan test --filter=test_confirmar_dos_veces_descuenta_una_sola_vez
vendor/bin/pint --dirty --format agent
npm run build
node tests/Browser/admin.mjs task7
node tests/Browser/admin.mjs task8
```

La inspección visual usa Firefox BiDi existente con perfil temporal independiente, puerto 9225 y servidor dedicado 8014/SQLite sintética. El script admite `FIREFOX_BIDI_URL`, `TIENDA_URL` y `TIENDA_CAPTURAS`; no crea sesiones en la BD de desarrollo. Capturas 1440/768 y originales de maqueta en `.superpowers/sdd/2026-10-19-fase-2-panel-admin/`.

## Matriz de casos

### 1 · Datos del ciclo

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| ADM-01 | Pedido en cada uno de los seis estados | Consultar todos los destinos, incluidos mismo estado y desconocido | Solo permite la tabla de transiciones de la spec | `ModeloPedidosTest::test_transiciones_permitidas_segun_tabla` |
| ADM-02 | Carrito con stock; invitada y cliente autenticada | POST checkout y consultar historial | Una fila pendiente, usuario correspondiente o NULL; no descuenta stock | `ModeloPedidosTest::test_pedido_creado_desde_checkout_registra_estado_pendiente` |
| ADM-03 | Pedido sin confirmación y luego historial fuera de orden de inserción | Consultar fechaConfirmacion e historial | NULL sin confirmación; luego 1/11 09:00 UTC; orden cronológico | `ModeloPedidosTest::test_fecha_confirmacion_sale_del_historial` |
| ADM-04 | Cada estado permitido | Obtener etiqueta e insignia | Pendiente/Confirmado/Preparando/Enviado/Entregado/Cancelado y clase de maqueta | `ModeloPedidosTest::test_etiquetas_de_estado` |
| ADM-05 | Movimiento histórico sin auditoría y movimiento nuevo | Leer usuario y stock resultante | Histórico NULL; nuevo stock 12 y usuario asociado | `ModeloPedidosTest::test_movimientos_admiten_auditoria_nullable_y_usuario` |

### 2 · Servicio Inventario

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| ADM-06 | Stock 2, administrador y reloj congelado | Registrar entrada 4 | Stock 6, movimiento entrada 4, usuario, fecha UTC y stock_resultante 6 | `InventarioServicioTest::test_entrada_suma_y_registra_stock_resultante` |
| ADM-07 | Stock 2 | Registrar salida 2 sin usuario | Stock 0; salida 2; usuario NULL | `InventarioServicioTest::test_salida_resta` |
| ADM-08 | Stock 2 | Intentar salida 3 | StockInsuficiente con perfume/tamaño/disponible 2; sin cambios ni movimientos | `InventarioServicioTest::test_salida_no_deja_stock_negativo` |
| ADM-09 | Stock 2; cantidades 0 y −1 | Intentar entradas | InvalidArgumentException; stock 2 y cero movimientos | `InventarioServicioTest::test_cantidad_invalida_se_rechaza` |
| ADM-10 | Stock 2; cantidad texto | Intentar salida | TypeError; stock 2 y cero movimientos | `InventarioServicioTest::test_cantidad_texto_se_rechaza_sin_cambios` |
| ADM-11 | Stock 10; tipo ajuste | Registrar movimiento | InvalidArgumentException; sin cambios | `InventarioServicioTest::test_tipo_invalido_se_rechaza` |
| ADM-12 | Motivo vacío o espacios | Registrar entrada 1 | InvalidArgumentException; sin cambios | `InventarioServicioTest::test_motivo_vacio_se_rechaza` |
| ADM-13 | Presentaciones activas con stock 0/3/4 e inactiva con 2 | Consultar críticas | Solo activas 0/3, producto y tamaño cargados | `InventarioServicioTest::test_criticas_lista_stock_menor_o_igual_a_tres_solo_activas` |
| ADM-14 | Instancia stock 10, BD actualizada a 2 | Registrar entrada 1 usando instancia vieja | Relee BD; stock 3 y auditoría 3 | `InventarioServicioTest::test_relee_stock_persistido_antes_de_registrar` |
| ADM-15 | Stock 2; usuario inexistente | Registrar entrada 1 | Fallo FK revierte stock y movimiento | `InventarioServicioTest::test_error_al_guardar_movimiento_revierte_el_stock` |

### 3 · CicloPedido

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| ADM-16 | Pedido pendiente con cantidades 2/3 y stock 10 en dos presentaciones | Confirmar | Confirmado; stocks 8/7, dos salidas Venta MF, usuario/historial; total sin cambios | `CicloPedidoTest::test_confirmar_descuenta_stock_y_registra_salidas` |
| ADM-17 | Primera línea suficiente; segunda stock 2 necesita 3 | Confirmar | Excepción identifica segunda; revierte también primera, estado e historial | `CicloPedidoTest::test_confirmar_sin_stock_suficiente_falla_sin_cambios` |
| ADM-18 | Dos instancias del mismo pendiente | Confirmar dos veces con instancia obsoleta | Segunda falla; stocks 8/7, solo dos salidas y una confirmación | `CicloPedidoTest::test_confirmar_dos_veces_descuenta_una_sola_vez` |
| ADM-19 | Confirmado o preparando; reposición intermedia 4 | Cancelar | Stocks 14/10; entradas de devolución con motivo Cancelación MF; conserva reposición | `CicloPedidoTest::test_cancelar_confirmado_devuelve_stock` |
| ADM-20 | Pedido pendiente | Cancelar | Cancelado e historial; no modifica stock ni movimientos | `CicloPedidoTest::test_cancelar_pendiente_no_toca_stock` |
| ADM-21 | Pendiente/enviado/entregado/confirmado/cancelado | Intentar saltos, retrocesos, cancelación tardía y estado desconocido | TransicionInvalida, sin cambios | `CicloPedidoTest::test_transiciones_invalidas_se_rechazan` |
| ADM-22 | Pedido creado 31/10; reloj avanza a 1/11 Asunción | Avanzar confirmado, preparando, enviado, entregado | Un registro por cambio con usuario y fecha UTC; solo confirmación descuenta | `CicloPedidoTest::test_cada_cambio_queda_en_el_historial_con_usuario` |
| ADM-23 | Dos pedidos comparten presentación | Confirmar primero y luego segundo que necesita 9 | Segundo falla con disponible 8; no sobrevende | `CicloPedidoTest::test_otro_pedido_consume_stock_antes_de_confirmar` |
| ADM-24 | Pedido confirmado; usuario inexistente | Pasar a preparando | Fallo de historial revierte estado; sin movimientos | `CicloPedidoTest::test_fallo_al_guardar_historial_revierte_estado` |

### 4 · Dashboard

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| ADM-25 | Invitado y usuario cliente | GET dashboard | Invitado 302 login; cliente 403 | `DashboardTest::test_dashboard_requiere_admin` |
| ADM-26 | Un pendiente y una presentación crítica; admin | GET dashboard | Grupos VENTAS/CATÁLOGO/STOCK y ambos contadores 1 | `DashboardTest::test_menu_muestra_grupos_y_contadores` |
| ADM-27 | Creación y confirmación en meses distintos; segundos antes/después de límites UTC | Consultar noviembre en Asunción | Cuenta confirmaciones locales del mes: dos pedidos,400000 | `DashboardTest::test_metricas_cuentan_por_fecha_de_confirmacion` |
| ADM-28 | Pendientes, cancelados, confirmado sin historia y cuatro estados vendidos | Consultar mes actual | Solo cuatro ventas 10000; pendiente 1 con fecha del más antiguo | `DashboardTest::test_metricas_excluyen_cancelados_y_pendientes` |
| ADM-29 | Seis presentaciones con unidades 1,3,2,5,4,6 | Consultar top | Cinco filas, unidades 6,5,4,3,2 y presentación correspondiente | `DashboardTest::test_mas_vendidos_top_5_por_unidades` |
| ADM-30 | Sin ventas; luego 3 unidades 5 ml y 2 unidades 10 ml | Consultar resumen | Sin ventas 0%; luego 5 unidades y 60% de 5 ml | `DashboardTest::test_porcentaje_de_5ml` |

### 5 · Pedidos

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| ADM-31 | Un pendiente y un confirmado; admin | Filtrar listado por pendiente | Solo pendiente; contadores globales 1/1 | `PedidosTest::test_lista_filtra_por_estado_con_contadores` |
| ADM-32 | Dos pedidos de contactos distintos | Buscar por MF-0001, María o 0981234567 | Solo pedido coincidente | `PedidosTest::test_busca_por_numero_nombre_o_telefono` |
| ADM-33 | Pedido con detalle, subtotal 60000, envío 20000 y contacto | GET detalle por id | Productos/tamaño/cantidades/precios, total 80000, cliente e historial | `PedidosTest::test_detalle_muestra_productos_totales_cliente_e_historial` |
| ADM-34 | Contacto 0981234567 | GET detalle | Enlace wa.me/595981234567; no envía mensajes | `PedidosTest::test_detalle_tiene_enlace_whatsapp_a_la_clienta` |
| ADM-35 | Pendiente con stock 10, cantidad 2 | POST confirmar dos veces | Primero 302, stock 8; segundo error sin segunda salida | `PedidosTest::test_boton_confirmar_cambia_estado_y_descuenta_stock` |
| ADM-36 | Pedido requiere 2 y stock 1 | POST confirmar y seguir con cookie de sesión | Error visible identifica perfume/tamaño; pendiente, stock 1, sin movimientos | `PedidosTest::test_error_de_stock_se_muestra_con_el_perfume_y_tamano` |
| ADM-37 | Pendiente/enviado/entregado | Intentar enviar pendiente o cancelar enviados/entregados | Redirige detalle con error; sin cambios | `PedidosTest::test_transicion_invalida_vuelve_con_error` |
| ADM-38 | Pedido en cada estado | Inspeccionar botones del detalle | Solo transiciones válidas; sin botones en terminales | `PedidosTest::test_solo_se_muestran_botones_de_transiciones_validas` |
| ADM-39 | Invitado y cliente | GET listado/detalle y POST estado | 302 login/403; pedido y stock intactos | `PedidosTest::test_rutas_exigen_admin` |
| ADM-40 | Contacto con script; teléfono/entregaNULL | GET detalle | Texto escapado; sin enlace WhatsApp inválido; no 500 | `PedidosTest::test_datos_historicos_nulos_y_texto_escapado` |
| ADM-41 | Estado ausente, array o desconocido | POST estado | Error en español; pendiente sin cambios | `PedidosTest::test_valida_estado_sin_cambios` |

### 6 · Productos

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| ADM-42 | Perfume mujer con stock 3/8 en 5/10 ml | GET listado | Marca/género/ambos stocks; crítico resaltado; editar por id | `ProductosTest::test_lista_muestra_stock_de_5_y_10_ml_y_marca_criticos` |
| ADM-43 | Perfumes con géneros, agotado e inactivo; nombre Único | Filtrar cada género/sin-stock/inactivos y buscar unico | Solo coincidencia; búsqueda sin tildes | `ProductosTest::test_filtros_genero_sin_stock_inactivos_y_busqueda` |
| ADM-44 | Nota Vainilla existente; nuevas vainilla/Bergamota repetida | Crear perfume mujer destacado, precios 0/60000 | Género y flags guardados; dos notas sin duplicados; precio 0 válido | `ProductosTest::test_crear_perfume_con_genero_notas_y_precios` |
| ADM-45 | Stock inicial 7 y stock manipulado 999 | Crear perfume | Stock 7 vía entrada Stock inicial 7; ignora stock 999 | `ProductosTest::test_stock_inicial_se_registra_como_entrada` |
| ADM-46 | Stock existente 9; payload stock/stock_inicial 999 | Editar precio a 0 | Stock 9, cero movimientos; formulario muestra stock sin campo editable | `ProductosTest::test_editar_producto_ignora_stock_enviado` |
| ADM-47 | Falta nombre/marca/categoría/género; género otro o presentaciones vacías | POST creación | Errores correspondientes; sin productos/movimientos | `ProductosTest::test_validaciones` |
| ADM-48 | Precio negativo o texto | POST creación | Error precio; ningún producto | `ProductosTest::test_precio_invalido_se_rechaza` |
| ADM-49 | Todos los precios vacíos; luego tamaño 15 ml manipulado | Crear perfume | Rechazo por falta de precio o tamaño fuera de 5/10 | `ProductosTest::test_al_menos_un_tamano_con_precio_y_solo_5_o_10ml` |
| ADM-50 | Perfume con detalle de pedido | DELETE | Bloquea; conserva perfume/presentación/pedido y ofrece ocultar | `ProductosTest::test_no_elimina_producto_con_pedidos` |
| ADM-51 | Perfume con movimiento, sin pedidos | DELETE | Bloquea con mensaje; conserva kardex y stock 11; ofrece ocultar | `ProductosTest::test_no_elimina_producto_con_movimientos` |
| ADM-52 | Perfume sin pedidos ni movimientos, con notas/presentación | DELETE | Elimina producto y dependencias; no elimina notas compartidas | `ProductosTest::test_elimina_producto_sin_pedidos` |
| ADM-53 | Foto JPG válida; almacenamiento fake | Crear y editar sin reemplazar foto | Archivo existe; edición conserva su ruta | `ProductosTest::test_foto_se_guarda_y_se_conserva_al_editar` |
| ADM-54 | Usuario cliente | Crear, editar y eliminar | 403; producto conservado | `ProductosTest::test_cliente_no_puede_crear_editar_ni_eliminar` |
| ADM-55 | Archivo de texto como foto | POST creación | Error imagen en español; ningún producto | `ProductosTest::test_foto_invalida_muestra_error_en_espanol` |
| ADM-56 | Stock inicial 7 en fila sin precio; otra fila con precio | POST creación | Error en esa fila; no descarta stock silenciosamente ni guarda nada | `ProductosTest::test_stock_inicial_sin_precio_se_rechaza_sin_perder_datos` |
| ADM-57 | 16 perfumes; admin | GET listado | Paginación española, sin Showing | `ProductosTest::test_paginacion_en_espanol` |

### 7 · Kardex HTTP

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| ADM-58 | Entrada 3 por Sistema y salida 1 por Paz | GET kardex | Motivos, usuario y stocks después 13/12 visibles | `InventarioTest::test_kardex_muestra_movimientos_con_stock_resultante_y_usuario` |
| ADM-59 | Entrada crítica y salida normal de otro perfume | Filtrar entradas/salidas/críticos y producto | Solo movimientos coincidentes, filtros combinables | `InventarioTest::test_filtros_entradas_salidas_criticos_y_perfume` |
| ADM-60 | Admin; stock 2 | GET formulario y POST entrada 4 con motivo | 302 índice, stock 6 y auditoría del admin | `InventarioTest::test_registrar_movimiento_manual` |
| ADM-61 | Stock 2 | POST salida 3 | Error cantidad; stock 2; sin movimiento | `InventarioTest::test_movimiento_manual_que_deja_stock_negativo_se_rechaza` |
| ADM-62 | Cantidad 0/−1/texto/fracción, motivo vacío o tipo inválido | POST movimiento | Error de validación; stock 10 y cero movimientos | `InventarioTest::test_valida_campos_sin_modificar_stock` |
| ADM-63 | Invitado y cliente | Abrir kardex/formulario o enviar movimiento | 302 login o 403 | `InventarioTest::test_exige_admin_y_conserva_auditoria_historica_nullable` |

### 8 · Marcas, categorías y tamaños

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| ADM-64 | Marca con dos perfumes | GET marcas | Cantidad 2 y nuevo layout | `CatalogoAdminTest::test_marcas_muestran_cantidad_de_perfumes` |
| ADM-65 | Marca no destacada; foto válida | Editar nombre, destacada, orden 1 e imagen; abrir tienda | Modelo y archivo guardados; menú público refleja cambios | `CatalogoAdminTest::test_marca_gestiona_destacada_orden_menu_e_imagen` |
| ADM-66 | Ocho marcas destacadas | Crear novena; destacar otra; editar una ya destacada | Primeras dos operaciones rechazan; editar existente permite; máximo 8 | `CatalogoAdminTest::test_no_permite_mas_de_ocho_marcas_destacadas` |
| ADM-67 | Marca y categoría usadas por perfume | DELETE ambas | Error; conserva ambas | `CatalogoAdminTest::test_no_elimina_marca_o_categoria_con_perfumes` |
| ADM-68 | Marca y categoría sin perfumes | DELETE ambas | Elimina y redirige a listado | `CatalogoAdminTest::test_elimina_marca_sin_perfumes` |
| ADM-69 | Tamaños 5/10/15; admin | GET índice y probar antiguas rutas de escritura | Solo 5/10 visibles; create/edit/PUT/DELETE404, POST405; no mutación | `CatalogoAdminTest::test_tamanos_es_solo_lectura` |
| ADM-70 | Admin; formularios nuevos | Crear/editar categoría; probar nombre vacío y orden negativo | Formularios nuevo layout; guardado válido y validaciones | `CatalogoAdminTest::test_formularios_y_categoria_validan_y_guardan` |
| ADM-71 | Invitado y cliente | GET listas y POST marcas/categorías | 302 login o 403 | `CatalogoAdminTest::test_rutas_exigen_administrador` |

### 9 · Fechas y cierre

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| ADM-72 | Aplicación arranca desde config/.env | Leer reloj y configuración | America/Asuncion en ambos | `FechasLocalesTest::test_aplicacion_usa_reloj_de_asuncion` |
| ADM-73 | AppAsunción; fechas persistidas 04:26 UTC | GET detalle y kardex | 19/10 01:26 local; BD conserva 04:26 UTC | `FechasLocalesTest::test_fecha_guardada_se_muestra_en_asuncion_en_pedido_y_kardex` |
| ADM-74 | Reloj 01:26 Asunción; presentación y pedido nuevos | Registrar movimiento y estado inicial; releer | Persiste 04:26 UTC; conserva instante tras ida/vuelta | `FechasLocalesTest::test_nuevas_fechas_se_persisten_en_utc_y_conservan_el_instante` |

### Ranking público y auditoría (T9)

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| CIE-01 | Venta creada hace 200 días, confirmada hoy; otra creada ayer, confirmada hace 100 días | Consultar más probados | Gana confirmación reciente; ventana 90 días, sin pendientes/cancelados | `Tests\Feature\Catalogo\RecomendadorTest::test_mas_probados_usa_fecha_de_confirmacion` |
| CIE-02 | Vistas públicas y admin | Escanear fuentes; durante RED se introdujo y retiró fixture U+1 F600 | Detecta fixture; sin emojis/símbolos-icono en fuentes finales | `Tests\Feature\Tienda\AuditoriaVistasTest::test_vistas_publicas_no_contienen_emojis_ni_simbolos_icono` |

## Resultado y límites

Suite de cierre: 269 tests, 1630 assertions, cero fallos. Pint/build verificados; comparación visual 14–20 a 1440/768, sin overflow raíz ni errores consola. Tablet apila paneles y usa scroll local en tablas. Controles nativos de archivo usan idioma del navegador.

MySQL no ejecutado: pdo_mysql disponible, pero no Docker, cliente/servidor MySQL ni puertos 3306/3307 escuchando. Pendientes: migraciones/seeders y dos conexiones concurrentes sobre MySQL; teléfono físico/WhatsApp real; datos comerciales/fotos definitivos. No se hizo push.

Menores heredados del bloque 2: búsqueda literal 0 omitida; notas deseleccionadas pueden restaurarse tras otra validación fallida. Precios fraccionarios mantienen contrato heredado: mostrar redondea y carrito trunca, requiere criterio unificado antes de usarlos. No se introdujo una nueva regla monetaria.

Revisión final independiente: sin hallazgos críticos/importantes. Menor adicional diferido: entrada extrema puede exceder `2147483647` como stock resultante; MySQL estricto rechazaría la escritura con error de servidor y rollback. Falta una validación explícita del resultado bajo bloqueo. No se verificó sobre MySQL.
