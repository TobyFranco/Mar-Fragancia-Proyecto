<?php

namespace Tests\Feature\Tienda;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusquedaTest extends TestCase
{
    use RefreshDatabase;

    public function test_busqueda_por_perfume_marca_y_nota_sin_tildes(): void
    {
        $this->seed();

        $marca = $this->get('/buscar?q=hermes')->assertOk();
        $nota = $this->get('/buscar?q=vainilla')->assertOk();
        $perfume = $this->get('/buscar?q=SAUVAGE')->assertOk();

        $this->assertSame(8, $marca->viewData('productos')->count());
        $this->assertTrue($marca->viewData('productos')->pluck('nombre')->contains('Terre d’Hermès'));
        $this->assertTrue($nota->viewData('productos')->pluck('nombre')->contains('Black Opium'));
        $this->assertSame(['Sauvage', 'Sauvage Elixir'], $perfume->viewData('productos')->pluck('nombre')->sort()->values()->all());
    }

    public function test_busqueda_sin_resultados_muestra_estado_vacio_con_whatsapp(): void
    {
        $this->get('/buscar?q=savaje')->assertOk()->assertSeeText('No encontramos "savaje"', false)->assertSee('https://wa.me/');
    }

    public function test_busqueda_no_incluye_inactivos_y_escapa_la_consulta(): void
    {
        $this->seed();
        Producto::where('nombre', 'Sauvage')->update(['activo' => false]);

        $this->assertSame(['Sauvage Elixir'], $this->get('/buscar?q=sauvage')->assertOk()->viewData('productos')->pluck('nombre')->all());
        $this->get('/buscar?'.http_build_query(['q' => '<script>alert(1)</script>']))->assertOk()->assertDontSee('<script>', false)->assertSee('<script>alert(1)</script>');
    }

    public function test_busqueda_vacia_o_parametro_array_no_provoca_error(): void
    {
        $this->get('/buscar')->assertOk()->assertSee('Buscá por perfume, marca o nota');
        $this->get('/buscar?q[]=texto')->assertOk();
    }
}
