# PI-13 · Plan de pruebas de Auth y Catálogo

Fecha: 2026-10-19. Proyecto: Mar Fragancias, Fase 1. Rama: `feature/tienda-publica`.

Fuente de verdad: [especificación](../superpowers/specs/2026-10-18-tienda-y-admin-design.md) y [plan de implementación](../superpowers/plans/2026-10-18-fase-1-tienda-publica.md). Referencia visual: [maquetas aprobadas](../diseno/maquetas/diseno-final.html).

## Objetivo y alcance

Verificar que una visitante pueda encontrar decants de 5 y 10 ml por marca, género o notas, elegir una presentación disponible, armar un carrito y generar un pedido pendiente con mensaje de WhatsApp correcto. Verificar ingreso, registro, cierre de sesión y acceso por rol, conservando las rutas existentes.

Las tablas trazan todos los métodos de tests de las Tareas 3, 5, 6, 7, 8 y 10. Cada fila corresponde a un método; las matrices `TestWith` se explicitan en Pasos y se ejecutan completas. Se incluyen casos adicionales implementados para entradas manipuladas, stock cambiante y privacidad del recibo. La suite completa también cubre modelos, seeders, inicio, termómetro, resaltado, páginas informativas y auditoría de vistas.

Fuera de esta verificación: MySQL, concurrencia real entre procesos, teléfono físico, entrega efectiva de mensajes y datos comerciales definitivos. No se prueba el ciclo administrativo de confirmación/descuento de stock: pertenece a Fase 2. Crear un pedido pendiente no reserva ni descuenta inventario.

## Entorno y ejecución

Entorno verificado: PHP 8.5.0, Laravel 13.29.0, PHPUnit 12.5.34, Node 24.22 y npm 11. `src/phpunit.xml` usa SQLite `:memory:`, sesión/cache en `array`, cola `sync` y correo `array`. Los tests de BD usan `RefreshDatabase` y crean sus propios datos con factories o seeders; no requieren la BD de desarrollo. Los tests de mensaje construyen modelos sin persistirlos; los tests de texto son unitarios puros. No hay llamadas a WhatsApp: se verifica el destino del redirect y la codificación. Open-Meteo usa HTTP fake en tests de clima.

Desde la raíz del repositorio:

```bash
export PATH="$HOME/.config/herd-lite/bin:$PATH"
cd src
php artisan test
```

Subconjuntos y caso individual:

```bash
php artisan test tests/Feature/Auth
php artisan test tests/Feature/Catalogo tests/Feature/Tienda tests/Unit
php artisan test --filter=test_url_de_whatsapp_codifica_numeral_tildes_y_saltos
vendor/bin/pint --dirty --format agent
npm run build
```

Los nombres de clase se resuelven así: `RecomendadorTest` en `Tests\Feature\Catalogo`; `MarcasTest`, `CatalogoTest`, `BusquedaTest`, `PerfumeTest`, `CarritoTest` y `CheckoutTest` en `Tests\Feature\Tienda`; `LoginTest`, `RegistroTest` y `AccesoAdminTest` en `Tests\Feature\Auth`; `TextoTest` y `MensajeWhatsappTest` en `Tests\Unit`. Cada archivo está en `src/tests/` bajo esa misma ruta.

## Tarea 3 · Recomendaciones

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| REC-01 Jaccard | Notas A={bergamota,pimienta,ambroxan,lavanda}; B={bergamota,pimienta,ámbar} | Calcular parecido; repetir sin notas | 40%; sin notas, 0% | `RecomendadorTest::test_parecido_es_jaccard` |
| REC-02 Orden y elegibilidad | Base, iguales, empates, inactivo, agotado y presentación deshabilitada | Pedir parecidos con límites 10 y 2 | Excluye base/no disponibles; 100,50,50%; desempata por nombre; informa notas compartidas y respeta límite | `RecomendadorTest::test_parecidos_ordena_por_porcentaje_y_nombre_y_excluye_base_inactivos_y_sin_stock` |
| REC-03 Sin coincidencias | Base bergamota, candidato vainilla | Pedir recomendaciones | Colección vacía | `RecomendadorTest::test_parecidos_sin_notas_en_comun_no_aparecen` |
| REC-04 Más probados | Fecha fija; ventas en distintos estados/fechas, destacado y agotado | Pedir 4 más probados; incluir venta de hace 90 días, excluir de 120 días | Suma confirmado/preparando/enviado/entregado; ignora pendiente/cancelado/agotado; completa destacados y alfabético | `RecomendadorTest::test_mas_probados_usa_ventas_confirmadas_90_dias_y_completa_con_destacados` |

## Tarea 5 · Marcas, catálogo y búsqueda

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| CAT-01 Índice A–Z | Seed; Sauvage inactivo | GET `/marcas` | Agrupa por inicial, ancla D y cantidad Dior=2; singular correcto | `MarcasTest::test_indice_de_marcas_agrupa_a_z_con_cantidad` |
| CAT-02 Marca y género | Seed | GET `/marcas/chanel?genero=hombre` | Solo Bleu de Chanel en resultados; filtros visibles | `MarcasTest::test_pagina_de_marca_filtra_por_genero` |
| CAT-03 Marca ausente | Sin esa marca | GET `/marcas/no-existe` | 404 | `MarcasTest::test_marca_inexistente_da_404` |
| CAT-04 Filtros combinados | Misma marca/familia/tamaño, precios 30.000 y 60.000; distractores y agotado | GET `/perfumes` con marca, familia, tamaño 10 y precio asc/desc; sumar género mujer | Resultados combinados correctos, excluye agotado; invierte orden de precios y mantiene singular | `CatalogoTest::test_catalogo_combina_filtros_y_ordena` |
| CAT-05 Género inválido | Seed | Comparar listado general con `genero=niños` | Ignora género inválido sin error ni pérdida de productos | `CatalogoTest::test_catalogo_ignora_genero_invalido` |
| CAT-06 Multimarca | Seed | GET con `marca[]=chanel`, `marca[]=dior`, `orden=nombre` | Seis productos de esas marcas ordenados por nombre | `CatalogoTest::test_catalogo_acepta_marcas_multiples_y_orden_nombre` |
| BUS-01 Tres campos normalizados | Seed | GET `/buscar?q=hermes`, `vainilla`, `SAUVAGE` | Encuentra marca sin tilde, nota y perfume sin depender de mayúsculas | `BusquedaTest::test_busqueda_por_perfume_marca_y_nota_sin_tildes` |
| BUS-02 Sin resultados | Sin coincidencia | GET `/buscar?q=savaje` | Estado vacío con consulta y enlace a WhatsApp | `BusquedaTest::test_busqueda_sin_resultados_muestra_estado_vacio_con_whatsapp` |
| BUS-03 Exclusión y XSS | Sauvage inactivo | Buscar Sauvage y luego consulta con etiqueta script | Excluye inactivo; consulta escapada, sin script ejecutable | `BusquedaTest::test_busqueda_no_incluye_inactivos_y_escapa_la_consulta` |
| BUS-04 Consulta vacía/manipulada | Sin requisitos | GET `/buscar` y `?q[]=texto` | 200; orientación para buscar sin error 500 | `BusquedaTest::test_busqueda_vacia_o_parametro_array_no_provoca_error` |
| BUS-05 Normalización | Cadenas con tildes, mayúsculas, tab y espacios repetidos | Normalizar Hermès y VAINILLA/LIMÓN | Minúsculas sin tildes, espacios únicos; conserva apóstrofo tipográfico | `TextoTest::test_normaliza_tildes_mayusculas_y_espacios_sin_cambiar_apostrofos` |

## Tarea 6 · Perfume

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| PER-01 Detalle | Seed | GET `/perfumes/sauvage` | 5/10 ml, precios 35.000/59.500, notas y formulario hacia carrito | `PerfumeTest::test_muestra_dos_decants_con_precio_y_notas` |
| PER-02 Agotado | Seed con 10 ml agotado | Abrir Sauvage e inspeccionar radio | 10 ml deshabilitado y aviso Sin stock | `PerfumeTest::test_tamano_sin_stock_aparece_deshabilitado` |
| PER-03 Inactivo | Producto inactivo | GET de su slug | 404 | `PerfumeTest::test_producto_inactivo_da_404` |
| PER-04 Parecidos | Base y 5 candidatos con nota común y stock | Abrir base | Cuatro recomendaciones, desempate alfabético | `PerfumeTest::test_tambien_te_puede_gustar_muestra_cuatro_parecidos` |
| PER-05 Solo decants permitidos | Seed y presentación 15 ml a 999.999 agregada | Abrir Sauvage | No ofrece 15 ml, su precio ni frasco completo | `PerfumeTest::test_no_muestra_15ml_ni_frasco` |

## Tarea 7 · Carrito

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| CAR-01 Agregar/sumar | Sauvage 10 ml a 60.000 disponible | POST `/carrito` dos veces cantidad 1; GET carrito | Sesión=2, confirmación/panel, 2 unidades, subtotal120.000; no crea pedido | `CarritoTest::test_agrega_y_suma_cantidades_y_muestra_confirmacion` |
| CAR-02 Tope | Stock=3 | Agregar 5 y luego 2 | Cantidad3 y aviso de stock | `CarritoTest::test_no_supera_el_stock` |
| CAR-03 Cantidades manipuladas | Una unidad en sesión | POST con 0, -2, abc, 1.5 y array | Error cantidad; sesión intacta | `CarritoTest::test_no_acepta_cantidades_invalidas` |
| CAR-04 Presentación no disponible | Presentación stock0 o inactiva | Intentar agregar una unidad en ambas variantes | Error carrito y sesión sin línea nueva | `CarritoTest::test_no_agrega_presentacion_sin_stock_o_inactiva` |
| CAR-05 Producto/tamaño inválido | Presentación válida | Desactivar producto e intentar; reactivar y cambiar a 15 ml | Ambos rechazados sin agregar | `CarritoTest::test_no_agrega_producto_inactivo_ni_tamano_fuera_de_catalogo` |
| CAR-06 Cantidad extrema | Stock3, sesión2 | Agregar `PHP_INT_MAX`; PATCH cantidad9 | Limita a3 sin overflow y avisa | `CarritoTest::test_cantidad_muy_grande_y_actualizacion_respetan_stock` |
| CAR-07 Actualizar y quitar | Una unidad en sesión | PATCH cantidad2, -1 y 0 | Actualiza a2; rechaza negativo sin modificar; cero elimina | `CarritoTest::test_actualizar_a_cero_elimina_la_linea` |
| CAR-08 ID eliminado | Sesión con ID999 sin BD | DELETE `/carrito/999` | Elimina línea sin depender de existencia actual | `CarritoTest::test_elimina_sin_depender_de_que_la_presentacion_siga_existiendo` |
| CAR-09 Recomendación contextual | Sauvage y 4 candidatos con bergamota | Agregar Sauvage y GET carrito | Tres parecidos, nota, porcentaje100 y tamaño5 ml | `CarritoTest::test_muestra_recomendados_con_notas_parecidas` |
| CAR-10 Singular | Una unidad | GET carrito | Subtotal (1 unidad) | `CarritoTest::test_subtotal_de_una_unidad_usa_singular` |
| CAR-11 Vacío | Sin líneas | GET carrito | Estado vacío y salida a más probados | `CarritoTest::test_carrito_vacio_muestra_estado_vacio` |
| CAR-12 Contador | Tres unidades en sesión | GET inicio | Encabezado anuncia Carrito, 3 unidades | `CarritoTest::test_contador_del_encabezado_muestra_unidades` |

## Tarea 8 · Checkout y WhatsApp

Fixture base: Sauvage 10 ml ×1 a60.000 y Good Girl 5 ml ×2 a32.000, stock5 cada presentación, subtotal124.000. Datos válidos de nombre/celular/ciudad/dirección y referencia. Envío Asunción20.000. Datos sintéticos, no datos de una clienta real.

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| PED-01 Crear una vez | Fixture base, invitada | POST `/checkout` válido con total=1, estado confirmado y cliente_id999 manipulados; repetir POST | Redirige wa.me; MF-0001 pendiente, cliente NULL, subtotal124.000/envío20.000/total144.000 y dos detalles correctos; ignora datos manipulados; vacía carrito; stock5 intacto y cero movimientos; repetición no duplica pedido | `CheckoutTest::test_checkout_crea_pedido_pendiente_y_redirige_a_whatsapp` |
| PED-02 Entrega condicional | Fixture base | En retiro/asuncion/interior, vaciar ciudad/dirección | Retiro válido, envío0 y datos de entrega NULL; otros dos métodos dan errores sin pedido | `CheckoutTest::test_valida_campos_requeridos_segun_metodo` |
| PED-03 Normalizar celular | Fixture base | Enviar `0981 234 567`, `0981234567`, `+595 981 234 567` | Guarda `0981234567` en los tres casos | `CheckoutTest::test_normaliza_celular_paraguayo` |
| PED-04 Rechazar celular | Fixture base | Enviar `0981 23`, `0181234567`, letras+celular y array | Error de 10 dígitos en español; no crea pedido | `CheckoutTest::test_rechaza_celular_invalido` |
| PED-05 Email opcional | Fixture base | Enviar NULL, email válido y mal-email | Acepta primeros dos y guarda valor; rechaza tercero sin pedido | `CheckoutTest::test_email_opcional_pero_valido` |
| PED-06 Vacío | Sin líneas | GET y POST `/checkout` | Redirige carrito, cero pedidos | `CheckoutTest::test_checkout_con_carrito_vacio_redirige_al_carrito` |
| PED-07 Stock cambiante | Fixture base ya agregado | Cambiar Good Girl a stock0, stock1 o presentación inactiva; POST | Error con perfume y tamaño; conserva carrito; cero pedidos/detalles y stock de Sauvage intacto | `CheckoutTest::test_checkout_rechaza_linea_sin_stock_y_no_crea_pedido` |
| PED-08 Baja entre pasos | Fixture base | Desactivar producto, POST; eliminar presentación, POST | Rechaza ambos, no crea pedido y mantiene sesión para corregir | `CheckoutTest::test_checkout_rechaza_producto_desactivado_o_linea_eliminada` |
| PED-09 Recuperar línea eliminada | Fixture base con Good Girl eliminado | GET carrito, DELETE línea ausente, POST checkout | Control para quitar línea; permite pedido restante con subtotal60.000 | `CheckoutTest::test_linea_eliminada_se_puede_quitar_desde_la_pagina_del_carrito` |
| PED-10 Precios actuales | Fixture base | Cambiar Sauvage a70.000 y config envío25.000; GET y POST checkout | Muestra y guarda subtotal134.000/envío25.000/total159.000 | `CheckoutTest::test_totales_usan_precio_y_costo_configurado_del_momento` |
| PED-11 Interior | Fixture base | POST método interior | Guarda costo0 y total124.000, a coordinar | `CheckoutTest::test_envio_al_interior_se_guarda_sin_costo` |
| PED-12 Cuenta | Cliente autenticado con perfil | GET checkout y POST válido | Precarga nombre/email/celular/dirección y asocia su cliente_id | `CheckoutTest::test_cliente_logueado_precarga_datos_y_asocia_pedido` |
| PED-13 Recibo propio | Pedido recién creado por la sesión | GET `/pedido/{numero}` y carrito vacío | Número, total144.000, enlace reabrir WhatsApp; carrito conserva enlace al último pedido | `CheckoutTest::test_pagina_pedido_enviado_muestra_numero_y_boton_whatsapp` |
| PED-14 Privacidad | Pedido creado ajeno a sesión | GET de su número secuencial | 404 sin exponer datos personales | `CheckoutTest::test_pedido_de_otra_sesion_no_expone_datos_por_numero_secuencial` |
| PED-15 Validación general | Fixture base | Nombre vacío, método otro, referencia256 caracteres | Errores de tres campos, carrito intacto, sin pedido | `CheckoutTest::test_checkout_valida_nombre_metodo_y_largos_sin_modificar_carrito` |
| PED-16 Arrays en campos | Fixture base | Manipular cada uno: nombre_contacto, telefono, cedula, email, ciudad, barrio, direccion, referencia, metodo_entrega; seguir redirect con cookie | Error junto al campo, formulario200, Asunción seleccionada como fallback, cero pedidos | `CheckoutTest::test_campos_manipulados_como_array_muestran_error_sin_romper_formulario` |
| WA-01 Mensaje exacto | Pedido sintético MF-0042 de spec §6.5 | Generar texto para Asunción | Coincide literalmente: dos líneas de detalle, subtotal/envío/total, nombre, dirección y referencia | `MensajeWhatsappTest::test_texto_exacto_envio_asuncion` |
| WA-02 Variantes entrega | Mismo pedido, referencia NULL | Generar para retiro e interior | Retiro a coordinar, envío0; interior a coordinar; omite Ref. ausente | `MensajeWhatsappTest::test_texto_retiro_e_interior` |
| WA-03 URL íntegra | Nombre con tilde y &; número con # | Generar URL, inspeccionar fragmento y decodificar text | `%23MF-0042`, `%0A`, `%26` y tilde codificados; sin #/espacios/fragmento literal; roundtrip conserva texto | `MensajeWhatsappTest::test_url_de_whatsapp_codifica_numeral_tildes_y_saltos` |

## Tarea 10 · Auth y roles

| Caso | Precondición | Pasos | Resultado esperado | Test automatizado |
| --- | --- | --- | --- | --- |
| AUTH-01 Admin | Usuario administrador con hash válido | POST `/login` con contraseña correcta | Autenticado y redirect a admin.dashboard | `LoginTest::test_login_admin_redirige_al_dashboard` |
| AUTH-02 Cliente | Usuario cliente con hash válido | POST `/login` correcto | Autenticado y redirect a cliente.home | `LoginTest::test_login_cliente_redirige_a_cliente_home` |
| AUTH-03 Contraseña errónea | Usuario existente | POST password incorrecta desde login | Credenciales incorrectas, conserva email; nunca contraseña; sigue invitada | `LoginTest::test_credenciales_invalidas_vuelven_con_error_y_conservan_email` |
| AUTH-04 Logout | Autenticada con dato privado en sesión | POST `/logout`; GET dashboard | Invitada, dato de sesión eliminado y redirect a login | `LoginTest::test_logout_invalida_la_sesion` |
| AUTH-05 Diseño y compra invitada | Sin sesión | GET `/login` y `/register` | Pestañas, explicación de cuenta opcional y enlace a inicio | `LoginTest::test_vistas_de_auth_usan_el_nuevo_diseno` |
| AUTH-06 Login manipulado | Usuario existente | Enviar array en email o password; seguir redirect con cookie | Errores, formulario200 con alerta y sin autenticación | `LoginTest::test_login_rechaza_arrays_y_permite_volver_al_formulario` |
| AUTH-07 Registro | Rol cliente, email nuevo | POST `/register` válido, intentando rol_id999 | Usuario con hash, rol cliente, perfil con teléfono/dirección, autenticado y redirect cliente.home | `RegistroTest::test_registro_crea_usuario_cliente_y_perfil_y_autentica` |
| AUTH-08 Registro inválido | Email existente | Registrar mismo email y confirmación distinta | Errores email/password, no crea usuario/perfil adicional y sigue invitada | `RegistroTest::test_registro_rechaza_email_duplicado_y_contrasena_sin_confirmar` |
| AUTH-09 Arrays registro | Rol cliente | Array en nombre, email, password, telefono o direccion; password array confirmado también; volver con cookie | Error por campo, formulario200 con alerta y cero usuarios/perfiles | `RegistroTest::test_registro_rechaza_arrays_sin_romper_la_vista` |
| AUTH-10 Límite de BD | Rol cliente | Email sintácticamente válido mayor a150 caracteres | Error email antes de persistir | `RegistroTest::test_registro_rechaza_email_mayor_que_la_columna_existente` |
| AUTH-11 Acceso admin | Variantes invitada, cliente y administrador | GET dashboard/productos/marcas/categorias/tamanos admin en cada variante | Invitada redirect login; cliente403; administrador200 en las cinco rutas | `AccesoAdminTest::test_admin_exige_rol` |

## Evidencia y cierre

Corrida final de Fase 1 (2026-10-19): `php artisan test` → **150 tests aprobados, 1012 assertions, 0 fallos**, 6,50 s. `vendor/bin/pint --dirty --format agent` y `npm run build` correctos. Once recorridos Firefox verdes (diez normales y uno con movimiento reducido), a375/1440 px, sin errores de consola. Detalle de comparación y decisiones en Tarea 11e del plan.

Auditoría: `AuditoriaVistasTest::test_vistas_publicas_no_contienen_emojis_ni_simbolos_icono` recorre las vistas públicas recursivamente. Se probó su fallo con una vista temporal no enrutada que contenía seis caracteres prohibidos; detectó las seis líneas. La fixture se retiró antes del verde y no se versionó.

Pruebas de navegador sin dependencias nuevas: `src/tests/Browser/tienda.mjs`, Firefox headless con perfil aislado, escritorio1440 px y móvil375 px. Los modos `task4` a `task10` y `task11a-estilos`/`task11a-busqueda` cubren navegación, filtros, selección, panel/foco/Escape, carrito, checkout y errores, termómetro, Auth, CTA móvil único y búsqueda incremental con Enter. Se agregan Cómo funciona/404 en `task11b`. Requieren un servidor con BD SQLite de demostración separada y Firefox con WebDriver BiDi; no apuntarlos a datos reales. Los overrides son `TIENDA_URL`, `FIREFOX_BIDI_URL` y `TIENDA_CAPTURAS`. El perfil de movimiento reducido usa `ui.prefersReducedMotion=1` y `TIENDA_REDUCED_MOTION=1`.

Pendientes antes de publicar: ejecutar sobre MySQL en un entorno aislado; probar en teléfono físico teclado, tacto, foco y apertura de WhatsApp; confirmar número comercial, tarifa de envío, políticas/plazos de entrega/retiro/pago, enlaces sociales, fotos, precios, notas y stock reales. Los seeders y el número `595900000000` son demostrativos. No se enviaron mensajes ni se hizo push.
