<p align="center">
  <img src="docs/diseno/logo-readme.png" alt="Mar Fragancias" width="460">
</p>

<p align="center">
  <strong>Perfumes de lujo, en decant.</strong><br>
  Tienda web de decants de 5 ml y 10 ml de perfumes originales · Asunción, Paraguay
</p>

---

## Sobre el proyecto

**Mar Fragancias** vende decants de perfumes originales, sobre todo de casas de lujo, para que cada clienta pueda probar y usar fragancias caras pagando solo lo que va a usar. Hoy la venta se hace a mano por Instagram y WhatsApp. Este sistema centraliza el catálogo, el stock y los pedidos, y cierra cada compra por WhatsApp con el pedido ya armado.

Proyecto de la materia **Programación IV**.

## Funcionalidades

**Tienda pública** (Fase 1, terminada)

- Navegación por **marca**, con panel desplegable por casa (En portada, Mujer, Hombre, Para compartir).
- Catálogo con filtros por marca, género, familia de aroma y tamaño, y **búsqueda** sin tildes con sugerencias.
- Página de cada perfume con selección de **decant de 5 ml o 10 ml**, precio y stock.
- **Carrito** sin cuenta obligatoria, con recomendaciones de perfumes con **notas parecidas**.
- **Checkout** con datos personales y de entrega: el pedido queda guardado como *pendiente* y se abre **WhatsApp** con el mensaje listo para enviar.
- **Termómetro olfativo**: qué perfumes van con cada clima, con la temperatura real de Asunción.
- Ingreso y registro de clientas.

**Panel de administración** (Fase 2, en desarrollo)

- Dashboard con pedidos por confirmar, ventas del mes, decants más vendidos y stock crítico.
- Productos con género, notas y precios por tamaño.
- Pedidos con ciclo de estados (pendiente → confirmado → preparando → enviado → entregado) y descuento automático de stock al confirmar.
- Inventario tipo kardex con cada entrada y salida.
- Marcas, categorías y tamaños.

## Tecnologías

Laravel 13 · PHP 8.3+ · Blade · Tailwind CSS 4 · Alpine.js · Vite · PHPUnit · MySQL 8 (desarrollo) / SQLite (tests) · iconos Phosphor

## Puesta en marcha

Requisitos: PHP 8.3 o superior, Composer, Node.js 20 o superior y MySQL 8 (o SQLite para probar rápido).

```bash
cd src
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configurá la base de datos en `src/.env` y, si querés, los datos de la tienda:

```dotenv
TIENDA_WHATSAPP=595981000000      # número de WhatsApp de la tienda, con código de país
TIENDA_ENVIO_ASUNCION=20000       # costo de envío en Asunción y Gran Asunción (₲)
```

Crear las tablas con datos de ejemplo y levantar la tienda:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

La tienda queda en <http://localhost:8000>. Con Docker: `docker compose up -d` desde la raíz del repositorio (app en el puerto 8000, phpMyAdmin en el 8080).

**Usuarios de ejemplo** (creados por los seeders, solo para desarrollo):

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@marfragancia.com` | `password123` |
| Cliente | `mariapaz@example.com` | `password123` |

## Pruebas

```bash
cd src
php artisan test
```

Los tests usan SQLite en memoria y no necesitan una base de datos configurada. El plan de pruebas de Auth y Catálogo, con cada caso trazado a su test automático, está en [`docs/pruebas/plan-de-pruebas-auth-catalogo.md`](docs/pruebas/plan-de-pruebas-auth-catalogo.md).

## Documentación

| Documento | Ubicación |
|---|---|
| Alcance del proyecto | [`Alcance_Proyecto_Mar_Fragancia.pdf`](Alcance_Proyecto_Mar_Fragancia.pdf) |
| Especificación de diseño | [`docs/superpowers/specs/2026-10-18-tienda-y-admin-design.md`](docs/superpowers/specs/2026-10-18-tienda-y-admin-design.md) |
| Maquetas aprobadas | [`docs/diseno/maquetas/diseno-final.html`](docs/diseno/maquetas/diseno-final.html) (abrir en el navegador) |
| Plan de implementación · Fase 1 | [`docs/superpowers/plans/2026-10-18-fase-1-tienda-publica.md`](docs/superpowers/plans/2026-10-18-fase-1-tienda-publica.md) |
| Plan de pruebas (PI-13) | [`docs/pruebas/plan-de-pruebas-auth-catalogo.md`](docs/pruebas/plan-de-pruebas-auth-catalogo.md) |

## Equipo

| Rol | Integrante |
|---|---|
| Scrum Master / Project Manager | José Fernández |
| Backend & Architect Lead | Matías Romero · Tobías Franco |
| DevOps & Cloud Engineer | Matías Amarilla |
| Frontend & QA Engineer | María Paz Gutiérrez |
