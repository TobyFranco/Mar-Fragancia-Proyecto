# Modelo entidad-relación de Mar Fragancias

Fuente: 20 migraciones aplicadas e introspección MySQL 8.0.46 de la misma base que el [diccionario](diccionario-de-datos.md). Se representan las 14 tablas de negocio y sus 15 FK reales; las 8 tablas operativas se explican aparte en el diccionario. No se dibuja password_reset_tokens porque no existe.

## Nivel conceptual

Entidades y relaciones sin atributos; se conservan las asociaciones explícitas para que se pueda trazar cada entidad al esquema.

```mermaid
erDiagram
    categorias
    clientes
    detalle_pedidos
    marcas
    movimientos_inventario
    nota_producto
    notas
    pedido_estados
    pedidos
    producto_presentacion
    productos
    roles
    tamanos
    usuarios
    usuarios ||..o| clientes : "identifica"
    pedidos ||..o{ detalle_pedidos : "contiene"
    producto_presentacion ||..o{ detalle_pedidos : "se vende en"
    producto_presentacion ||..o{ movimientos_inventario : "registra"
    usuarios |o..o{ movimientos_inventario : "audita"
    notas ||--o{ nota_producto : "participa"
    productos ||--o{ nota_producto : "asocia"
    pedidos ||..o{ pedido_estados : "registra"
    usuarios |o..o{ pedido_estados : "documenta"
    clientes |o..o{ pedidos : "realiza"
    productos ||..o{ producto_presentacion : "ofrece"
    tamanos ||..o{ producto_presentacion : "dimensiona"
    categorias ||..o{ productos : "clasifica"
    marcas ||..o{ productos : "agrupa"
    roles ||..o{ usuarios : "autoriza"
```

## Nivel lógico

Los tipos de las etiquetas son familias MySQL; el comentario de cada atributo conserva el tipo exacto (longitud, precisión, unsigned o enum) y su nulabilidad. PK indica clave primaria, FK clave foránea y UK una unicidad individual. nota_producto tiene una sola PK compuesta (producto_id, nota_id), no dos claves independientes. Producto_presentacion tiene PK id, sin UNIQUE(producto_id,tamano_id).

```mermaid
erDiagram
    direction LR
    categorias {
        bigint id PK "bigint unsigned; NOT NULL"
        varchar nombre "varchar(100); NOT NULL"
        timestamp created_at "timestamp; NULL"
        timestamp updated_at "timestamp; NULL"
    }
    clientes {
        bigint id PK "bigint unsigned; NOT NULL"
        bigint usuario_id FK,UK "bigint unsigned; NOT NULL"
        varchar telefono "varchar(30); NULL"
        varchar direccion "varchar(255); NULL"
        timestamp created_at "timestamp; NULL"
        timestamp updated_at "timestamp; NULL"
    }
    detalle_pedidos {
        bigint id PK "bigint unsigned; NOT NULL"
        bigint pedido_id FK "bigint unsigned; NOT NULL"
        bigint presentacion_id FK "bigint unsigned; NOT NULL"
        int cantidad "int; NOT NULL"
        decimal precio_unitario "decimal(10,2); NOT NULL"
        decimal subtotal "decimal(10,2); NOT NULL"
        timestamp created_at "timestamp; NOT NULL"
    }
    marcas {
        bigint id PK "bigint unsigned; NOT NULL"
        varchar nombre "varchar(100); NOT NULL"
        timestamp created_at "timestamp; NULL"
        timestamp updated_at "timestamp; NULL"
        varchar slug UK "varchar(255); NOT NULL"
        tinyint destacada "tinyint(1); NOT NULL"
        int orden_menu "int; NULL"
        varchar imagen "varchar(255); NULL"
    }
    movimientos_inventario {
        bigint id PK "bigint unsigned; NOT NULL"
        bigint presentacion_id FK "bigint unsigned; NOT NULL"
        varchar tipo "varchar(20); NOT NULL"
        int cantidad "int; NOT NULL"
        datetime fecha "datetime; NOT NULL"
        varchar motivo "varchar(255); NULL"
        timestamp created_at "timestamp; NOT NULL"
        bigint usuario_id FK "bigint unsigned; NULL"
        int stock_resultante "int; NULL"
    }
    nota_producto {
        bigint producto_id PK,FK "bigint unsigned; NOT NULL"
        bigint nota_id PK,FK "bigint unsigned; NOT NULL"
    }
    notas {
        bigint id PK "bigint unsigned; NOT NULL"
        varchar nombre UK "varchar(255); NOT NULL"
    }
    pedido_estados {
        bigint id PK "bigint unsigned; NOT NULL"
        bigint pedido_id FK "bigint unsigned; NOT NULL"
        varchar estado "varchar(30); NOT NULL"
        bigint usuario_id FK "bigint unsigned; NULL"
        timestamp created_at "timestamp; NOT NULL"
    }
    pedidos {
        bigint id PK "bigint unsigned; NOT NULL"
        bigint cliente_id FK "bigint unsigned; NULL"
        datetime fecha "datetime; NOT NULL"
        varchar estado "varchar(30); NOT NULL"
        decimal total "decimal(10,2); NOT NULL"
        varchar observacion "varchar(255); NULL"
        timestamp created_at "timestamp; NULL"
        timestamp updated_at "timestamp; NULL"
        varchar numero UK "varchar(255); NULL"
        varchar nombre_contacto "varchar(255); NULL"
        varchar telefono "varchar(30); NULL"
        varchar cedula "varchar(255); NULL"
        varchar email "varchar(255); NULL"
        enum metodo_entrega "enum('retiro','asuncion','interior'); NULL"
        varchar ciudad "varchar(255); NULL"
        varchar barrio "varchar(255); NULL"
        varchar direccion "varchar(255); NULL"
        varchar referencia "varchar(255); NULL"
        decimal subtotal "decimal(10,2); NOT NULL"
        decimal costo_envio "decimal(10,2); NOT NULL"
    }
    producto_presentacion {
        bigint id PK "bigint unsigned; NOT NULL"
        bigint producto_id FK "bigint unsigned; NOT NULL"
        bigint tamano_id FK "bigint unsigned; NOT NULL"
        decimal precio "decimal(10,2); NOT NULL"
        int stock "int; NOT NULL"
        tinyint activo "tinyint(1); NOT NULL"
        timestamp created_at "timestamp; NULL"
        timestamp updated_at "timestamp; NULL"
    }
    productos {
        bigint id PK "bigint unsigned; NOT NULL"
        bigint marca_id FK "bigint unsigned; NOT NULL"
        bigint categoria_id FK "bigint unsigned; NOT NULL"
        varchar nombre "varchar(150); NOT NULL"
        text descripcion "text; NULL"
        varchar imagen "varchar(255); NULL"
        tinyint activo "tinyint(1); NOT NULL"
        timestamp created_at "timestamp; NULL"
        timestamp updated_at "timestamp; NULL"
        varchar genero "varchar(10); NOT NULL"
        varchar slug UK "varchar(255); NOT NULL"
        tinyint destacado "tinyint(1); NOT NULL"
    }
    roles {
        bigint id PK "bigint unsigned; NOT NULL"
        varchar nombre "varchar(50); NOT NULL"
        timestamp created_at "timestamp; NULL"
        timestamp updated_at "timestamp; NULL"
    }
    tamanos {
        bigint id PK "bigint unsigned; NOT NULL"
        varchar nombre "varchar(20); NOT NULL"
        varchar descripcion "varchar(100); NULL"
        timestamp created_at "timestamp; NULL"
        timestamp updated_at "timestamp; NULL"
    }
    usuarios {
        bigint id PK "bigint unsigned; NOT NULL"
        bigint rol_id FK "bigint unsigned; NOT NULL"
        varchar nombre "varchar(100); NOT NULL"
        varchar email UK "varchar(150); NOT NULL"
        varchar password "varchar(255); NOT NULL"
        timestamp created_at "timestamp; NULL"
        timestamp updated_at "timestamp; NULL"
    }

    usuarios ||..o| clientes : "identifica"
    pedidos ||..o{ detalle_pedidos : "contiene"
    producto_presentacion ||..o{ detalle_pedidos : "se vende en"
    producto_presentacion ||..o{ movimientos_inventario : "registra"
    usuarios |o..o{ movimientos_inventario : "audita"
    notas ||--o{ nota_producto : "participa"
    productos ||--o{ nota_producto : "asocia"
    pedidos ||..o{ pedido_estados : "registra"
    usuarios |o..o{ pedido_estados : "documenta"
    clientes |o..o{ pedidos : "realiza"
    productos ||..o{ producto_presentacion : "ofrece"
    tamanos ||..o{ producto_presentacion : "dimensiona"
    categorias ||..o{ productos : "clasifica"
    marcas ||..o{ productos : "agrupa"
    roles ||..o{ usuarios : "autoriza"
```

## Lectura de las cardinalidades

- `||`: exactamente uno; `|o`/`o|`: cero o uno; `o{`: cero o muchos. Se representan posibilidades permitidas por las FK y sus índices, no conteos de los datos de muestra.
- Cada cliente exige un usuario y cada usuario admite cero o un cliente (usuario_id UNIQUE). Cada usuario exige un rol; un rol puede tener cero o muchos usuarios.
- Cada pedido puede no tener cliente (compra invitada); un cliente puede tener cero o muchos pedidos. No es una relación obligatoria del lado del pedido.
- pedido_estados y movimientos_inventario pueden no tener usuario auditor por sus FK nullable. Cada fila sí exige el pedido o la presentación correspondiente.
- Cada presentación exige producto y tamaño; ambos pueden tener cero o muchas presentaciones. La base NO garantiza que el par producto/tamaño sea único.
- Cada ítem exige pedido y presentación; la base permite un pedido sin ítems, aunque el checkout no lo genera. Del mismo modo, los pedidos históricos pueden no tener historial.
- Producto y nota son muchos a muchos mediante nota_producto; cada fila de la asociación exige ambos. Solo esas dos FK tienen ON DELETE CASCADE. Las otras 13 FK tienen ON DELETE/UPDATE NO ACTION. Ver nombres exactos en el diccionario.
- Línea sólida: FK que participa de la PK compuesta de nota_producto (relación identificante). Línea discontinua: relación no identificante en tablas con PK propia id.

Las relaciones polimórficas de personal_access_tokens y sessions.user_id no son FK SQL y no se añaden al DER de negocio. En particular, no se inventa una tabla users: la cuenta real está en usuarios.

## Versión para presentación

[DER lógico en SVG](der-logico.svg), exportado desde el bloque lógico anterior. SVG permite ampliar sin perder legibilidad; el diagrama completo es grande y no sustituye la lectura por entidades del diccionario.

### Exportación reproducible

El archivo SVG se generó con Mermaid CLI **11.12.0** y Node **24.22.0**. Extraer el contenido del segundo bloque Mermaid (nivel lógico) a `/tmp/mar-der-logico.mmd` y ejecutar:

```bash
PATH="$HOME/.local/bin:$PATH" npx --yes @mermaid-js/mermaid-cli@11.12.0 \
  -i /tmp/mar-der-logico.mmd -o docs/datos/der-logico.svg -b white
```

La ruta `~/.local/node/bin` indicada en el plan no existe en esta máquina; se usó el Node instalado en `~/.local/bin` (ejecutable real en `~/.local/lib/nodejs/node-v24.22.0-linux-x64/bin/node`). Chromium no pudo arrancar con sandbox por la política de namespaces del host. Para esta exportación de un archivo local se utilizó un JSON temporal de Puppeteer con `{"args":["--no-sandbox"]}` y la opción `-p /tmp/mar-mermaid-puppeteer.json`; no se cambiaron políticas del sistema ni el runtime de la aplicación. El SVG sí fue exportado, no es una captura ni un diagrama inventado.
