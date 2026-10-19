<?php

namespace Tests\Feature\Tienda;

use App\Models\Nota;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Tamano;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_dos_decants_con_precio_y_notas(): void
    {
        $this->seed();
        $this->get('/perfumes/sauvage')->assertOk()
            ->assertSeeText(['5 ml', '10 ml', '₲ 35.000', '₲ 59.500', 'bergamota', '¿Por qué un decant?'])
            ->assertSee('action="'.url('/carrito').'"', false);
    }

    public function test_tamano_sin_stock_aparece_deshabilitado(): void
    {
        $this->seed();
        $html = $this->get('/perfumes/sauvage')->assertOk()->assertSeeText('Sin stock')->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//input[@type="radio" and @data-tamano="10 ml" and @disabled]')->length);
    }

    public function test_producto_inactivo_da_404(): void
    {
        $producto = Producto::factory()->create(['activo' => false]);
        $this->get('/perfumes/'.$producto->slug)->assertNotFound();
    }

    public function test_tambien_te_puede_gustar_muestra_cuatro_parecidos(): void
    {
        $base = Producto::factory()->create();
        $nota = Nota::factory()->create();
        $base->notas()->attach($nota);
        foreach (range(1, 5) as $n) {
            $producto = Producto::factory()->create(['nombre' => 'Parecido '.$n]);
            $producto->notas()->attach($nota);
            ProductoPresentacion::factory()->for($producto, 'producto')->create();
        }

        $respuesta = $this->get('/perfumes/'.$base->slug)->assertOk()->assertSeeText('También te puede gustar');
        $this->assertCount(4, $respuesta->viewData('parecidos'));
        $this->assertSame(['Parecido 1', 'Parecido 2', 'Parecido 3', 'Parecido 4'], $respuesta->viewData('parecidos')->pluck('producto.nombre')->all());
    }

    public function test_no_muestra_15ml_ni_frasco(): void
    {
        $this->seed();
        $base = Producto::where('slug', 'sauvage')->firstOrFail();
        ProductoPresentacion::factory()->for($base, 'producto')->for(Tamano::factory()->create(['nombre' => '15 ml']), 'tamano')->create(['precio' => 999999]);

        $this->get('/perfumes/sauvage')->assertOk()->assertDontSeeText(['15 ml', '999.999', 'Frasco completo']);
    }
}
