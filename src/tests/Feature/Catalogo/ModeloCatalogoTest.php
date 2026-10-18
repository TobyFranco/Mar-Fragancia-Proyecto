<?php

namespace Tests\Feature\Catalogo;

use App\Models\Marca;
use App\Models\Nota;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Tamano;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModeloCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_producto_tiene_genero_slug_y_notas(): void
    {
        $producto = Producto::factory()->create(['nombre' => 'Coco Mademoiselle', 'genero' => 'mujer']);
        $notas = Nota::factory()->count(2)->create();

        $producto->notas()->attach($notas);

        $this->assertSame('Mujer', $producto->etiquetaGenero());
        $this->assertSame('coco-mademoiselle', $producto->slug);
        $this->assertCount(2, $producto->fresh()->notas);
        $this->assertTrue($notas->first()->productos->contains($producto));
    }

    public function test_unisex_se_muestra_como_para_compartir(): void
    {
        $producto = Producto::factory()->create(['genero' => 'unisex']);

        $this->assertSame('Para compartir', $producto->etiquetaGenero());
        $this->assertSame('Hombre', Producto::factory()->make(['genero' => 'hombre'])->etiquetaGenero());
        $this->assertSame(['mujer', 'hombre', 'unisex'], Producto::GENEROS);
    }

    public function test_rechaza_genero_fuera_de_los_valores_del_catalogo(): void
    {
        $this->expectException(ValidationException::class);

        Producto::factory()->create(['genero' => 'invalido']);
    }

    public function test_visibles_excluye_inactivos_y_sin_presentaciones_activas(): void
    {
        $visible = Producto::factory()->create();
        $inactivo = Producto::factory()->create(['activo' => false]);
        $sinPresentaciones = Producto::factory()->create();
        $presentacionInactiva = Producto::factory()->create();
        $tamanoInvalido = Producto::factory()->create();
        $cinco = Tamano::factory()->create(['nombre' => '5 ml']);
        $diez = Tamano::factory()->create(['nombre' => '10 ml']);
        $quince = Tamano::factory()->create(['nombre' => '15 ml']);
        ProductoPresentacion::factory()->for($visible)->for($cinco, 'tamano')->create(['stock' => 0]);
        ProductoPresentacion::factory()->for($inactivo)->for($diez, 'tamano')->create();
        ProductoPresentacion::factory()->for($presentacionInactiva)->for($cinco, 'tamano')->create(['activo' => false]);
        ProductoPresentacion::factory()->for($tamanoInvalido)->for($quince, 'tamano')->create();

        $this->assertSame([$visible->id], Producto::visibles()->pluck('id')->all());
    }

    public function test_marcas_del_menu_son_las_destacadas_en_orden_y_maximo_ocho(): void
    {
        foreach ([10, 4, 8, 1, 9, 3, 6, 2, 7, 5] as $orden) {
            Marca::factory()->create(['destacada' => true, 'orden_menu' => $orden]);
        }
        Marca::factory()->create(['destacada' => false, 'orden_menu' => 0]);

        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], Marca::delMenu()->pluck('orden_menu')->all());
    }

    public function test_presentacion_por_tamano_y_disponibilidad(): void
    {
        $producto = Producto::factory()->create();
        $cinco = ProductoPresentacion::factory()->for($producto)->for(Tamano::factory()->create(['nombre' => '5 ml']), 'tamano')->create(['stock' => 3]);
        $diez = ProductoPresentacion::factory()->for($producto)->for(Tamano::factory()->create(['nombre' => '10 ml']), 'tamano')->create(['stock' => 0]);

        $this->assertTrue($producto->presentacionPorTamano('5 ml')->is($cinco));
        $this->assertTrue($producto->presentacionPorTamano('10 ml')->is($diez));
        $this->assertNull($producto->presentacionPorTamano('15 ml'));
        $this->assertTrue($cinco->disponible());
        $this->assertFalse($diez->disponible());
        $cinco->update(['activo' => false]);
        $this->assertFalse($cinco->fresh()->disponible());
    }

    public function test_slugs_se_generan_sin_colisiones_y_resuelven_modelos(): void
    {
        $marca = Marca::factory()->create(['nombre' => 'Hermès']);
        $otraMarca = Marca::factory()->create(['nombre' => 'Hermes']);
        $producto = Producto::factory()->create(['nombre' => 'Terre d’Hermès']);
        $otroProducto = Producto::factory()->create(['nombre' => 'Terre d’Hermès']);

        $this->assertSame('hermes', $marca->slug);
        $this->assertSame('hermes-2', $otraMarca->slug);
        $this->assertSame('terre-dhermes', $producto->slug);
        $this->assertSame('terre-dhermes-2', $otroProducto->slug);
        $this->assertTrue((new Marca)->resolveRouteBinding('hermes')->is($marca));
        $this->assertTrue((new Producto)->resolveRouteBinding('terre-dhermes')->is($producto));
    }

    public function test_pedido_sin_cliente_genera_numero(): void
    {
        $pedido = Pedido::create([
            'cliente_id' => null,
            'fecha' => '2026-10-18 12:00:00',
            'estado' => 'pendiente',
            'nombre_contacto' => 'María Paz Gutiérrez',
            'telefono' => '0981234567',
            'metodo_entrega' => 'retiro',
            'subtotal' => 60000,
            'costo_envio' => 0,
            'total' => 60000,
        ]);
        $otro = Pedido::create(['cliente_id' => null, 'fecha' => now(), 'estado' => 'pendiente', 'total' => 0]);

        $this->assertSame('MF-0001', $pedido->numero);
        $this->assertSame('MF-0002', $otro->numero);
        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'cliente_id' => null, 'numero' => 'MF-0001', 'subtotal' => 60000]);
    }

    public function test_admin_conserva_enlaces_y_binding_por_id(): void
    {
        $rol = Rol::create(['nombre' => 'administrador']);
        $admin = Usuario::create(['rol_id' => $rol->id, 'nombre' => 'Admin', 'email' => 'admin@example.test', 'password' => 'unused']);
        $marca = Marca::factory()->create();
        $producto = Producto::factory()->for($marca)->create();

        $this->actingAs($admin)->get('/admin/marcas/'.$marca->id.'/edit')->assertOk();
        $this->get('/admin/productos/'.$producto->id.'/edit')->assertOk();
        $this->assertSame(url('/admin/marcas/'.$marca->id.'/edit'), route('admin.marcas.edit', $marca));
        $this->assertSame(url('/admin/productos/'.$producto->id.'/edit'), route('admin.productos.edit', $producto));
    }

    public function test_migracion_completa_datos_existentes(): void
    {
        config(['database.connections.catalogo_legacy' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('catalogo_legacy');

        try {
            foreach (glob(database_path('migrations/*.php')) as $path) {
                if (basename($path) < '2026_10_18') {
                    (require $path)->up();
                }
            }
            $marca = DB::table('marcas')->insertGetId(['nombre' => 'Hermès']);
            DB::table('marcas')->insert(['nombre' => 'Hermes']);
            $categoria = DB::table('categorias')->insertGetId(['nombre' => 'Amaderada']);
            foreach (['Élan', 'Elan', 'Elan-2'] as $nombre) {
                DB::table('productos')->insert(['nombre' => $nombre, 'marca_id' => $marca, 'categoria_id' => $categoria]);
            }
            $rol = DB::table('roles')->insertGetId(['nombre' => 'cliente']);
            $usuario = DB::table('usuarios')->insertGetId(['rol_id' => $rol, 'nombre' => 'Cliente', 'email' => 'legacy@example.test', 'password' => 'unused']);
            $cliente = DB::table('clientes')->insertGetId(['usuario_id' => $usuario]);
            DB::table('pedidos')->insert(['id' => 42, 'cliente_id' => $cliente, 'fecha' => '2026-10-01 12:00:00', 'total' => 60000]);

            foreach (['000001_add_catalogo_fields', '000002_create_notas_tables', '000003_add_checkout_fields_to_pedidos'] as $migration) {
                $path = database_path('migrations/2026_10_18_'.$migration.'.php');
                $this->assertFileExists($path);
                (require $path)->up();
            }

            $this->assertSame(['unisex', 'unisex', 'unisex'], DB::table('productos')->pluck('genero')->all());
            $this->assertSame(['elan', 'elan-2', 'elan-2-2'], DB::table('productos')->orderBy('id')->pluck('slug')->all());
            $this->assertSame(['hermes', 'hermes-2'], DB::table('marcas')->orderBy('id')->pluck('slug')->all());
            $this->assertSame('MF-0042', DB::table('pedidos')->value('numero'));
            $this->assertSame(60000, (int) DB::table('pedidos')->value('total'));
            $this->assertSame(60000, (int) DB::table('pedidos')->value('subtotal'));
            $this->assertSame(0, (int) DB::table('pedidos')->value('costo_envio'));
            $this->assertNull(DB::table('pedidos')->value('nombre_contacto'));
            $this->assertNull(DB::table('pedidos')->value('metodo_entrega'));
        } finally {
            DB::setDefaultConnection('sqlite');
            DB::purge('catalogo_legacy');
        }
    }
}
