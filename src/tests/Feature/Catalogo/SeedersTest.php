<?php

namespace Tests\Feature\Catalogo;

use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Tamano;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_crea_solo_tamanos_5_y_10_ml(): void
    {
        $this->seed();

        $this->assertSame(['10 ml', '5 ml'], Tamano::pluck('nombre')->sort()->values()->all());
    }

    public function test_seed_tiene_ocho_marcas_del_menu_en_orden(): void
    {
        $this->seed();

        $this->assertSame(['Chanel', 'Dior', 'Tom Ford', 'Creed', 'Guerlain', 'Hermès', 'Yves Saint Laurent', 'Maison Francis Kurkdjian'], Marca::delMenu()->pluck('nombre')->all());
        foreach (['Carolina Herrera', 'Versace', 'Paco Rabanne', 'Parfums de Marly', 'Xerjoff', 'Lattafa', 'Armaf'] as $nombre) {
            $this->assertDatabaseHas('marcas', ['nombre' => $nombre, 'destacada' => false]);
        }
    }

    public function test_cada_producto_visible_tiene_genero_notas_y_dos_decants(): void
    {
        $this->seed();
        $productos = Producto::visibles()->with(['notas', 'categoria', 'presentaciones.tamano'])->get();

        $this->assertGreaterThanOrEqual(24, $productos->count());
        $this->assertSame(Producto::count(), $productos->count());
        foreach ($productos as $producto) {
            $this->assertContains($producto->genero, ['mujer', 'hombre', 'unisex']);
            $this->assertGreaterThanOrEqual(3, $producto->notas->count(), $producto->nombre);
            $this->assertNotNull($producto->categoria);
            $this->assertSame(['10 ml', '5 ml'], $producto->presentaciones->pluck('tamano.nombre')->sort()->values()->all());
            $cinco = $producto->presentacionPorTamano('5 ml');
            $diez = $producto->presentacionPorTamano('10 ml');
            $this->assertGreaterThanOrEqual(15000, (int) $cinco->precio);
            $this->assertLessThanOrEqual(65000, (int) $cinco->precio);
            $this->assertEqualsWithDelta((float) $cinco->precio * 1.7, (float) $diez->precio, 1);
            $this->assertTrue($cinco->disponible());
        }
        $this->assertTrue(ProductoPresentacion::whereHas('tamano', fn ($q) => $q->where('nombre', '10 ml'))->where('stock', 0)->where('activo', true)->exists());
    }

    public function test_marcas_destacadas_tienen_perfumes_para_cada_genero(): void
    {
        $this->seed();
        $marcas = Marca::delMenu()->get();

        $this->assertCount(8, $marcas);
        foreach ($marcas as $marca) {
            $this->assertSame(['hombre', 'mujer', 'unisex'], $marca->productos()->visibles()->distinct()->orderBy('genero')->pluck('genero')->all(), $marca->nombre);
        }
    }

    public function test_seed_conserva_roles_y_usuarios(): void
    {
        $this->seed();

        $this->assertSame(['administrador', 'cliente'], Rol::orderBy('nombre')->pluck('nombre')->all());
        $this->assertSame(['admin@marfragancia.com', 'mariapaz@example.com', 'tobias@example.com'], Usuario::orderBy('id')->pluck('email')->all());
        $this->assertSame('administrador', Usuario::where('email', 'admin@marfragancia.com')->first()->rol->nombre);
        $this->assertSame(2, Usuario::whereHas('cliente')->count());
    }
}
