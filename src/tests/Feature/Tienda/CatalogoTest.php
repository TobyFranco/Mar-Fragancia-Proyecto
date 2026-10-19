<?php

namespace Tests\Feature\Tienda;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Tamano;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogo_combina_filtros_y_ordena(): void
    {
        $marca = Marca::factory()->create(['nombre' => 'Chanel']);
        $familia = Categoria::factory()->create(['nombre' => 'Floral']);
        $tamano = Tamano::factory()->create(['nombre' => '10 ml']);
        $caro = Producto::factory()->for($marca)->for($familia, 'categoria')->create(['nombre' => 'Caro']);
        $barato = Producto::factory()->for($marca)->for($familia, 'categoria')->create(['nombre' => 'Barato', 'genero' => 'mujer']);
        ProductoPresentacion::factory()->for($caro)->for($tamano, 'tamano')->create(['precio' => 60000]);
        ProductoPresentacion::factory()->for($barato)->for($tamano, 'tamano')->create(['precio' => 30000]);
        $otraFamilia = Producto::factory()->for($marca)->create(['nombre' => 'Otra familia']);
        ProductoPresentacion::factory()->for($otraFamilia)->for($tamano, 'tamano')->create();
        $otraMarca = Producto::factory()->for($familia, 'categoria')->create(['nombre' => 'Otra marca']);
        ProductoPresentacion::factory()->for($otraMarca)->for($tamano, 'tamano')->create();
        $sinStock = Producto::factory()->for($marca)->for($familia, 'categoria')->create(['nombre' => 'Sin stock']);
        ProductoPresentacion::factory()->for($sinStock)->for($tamano, 'tamano')->create(['stock' => 0]);

        $url = '/perfumes?'.http_build_query(['marca' => ['chanel'], 'familia' => [$familia->id], 'tamano' => '10', 'orden' => 'precio_asc']);
        $respuesta = $this->get($url)->assertOk();

        $this->assertSame([$barato->id, $caro->id], $respuesta->viewData('productos')->modelKeys());
        $desc = $this->get(str_replace('precio_asc', 'precio_desc', $url))->assertOk();
        $this->assertSame([$caro->id, $barato->id], $desc->viewData('productos')->modelKeys());
        $this->get($url.'&genero=mujer')->assertOk()->assertDontSeeText('1 perfumes');
    }

    public function test_catalogo_ignora_genero_invalido(): void
    {
        $this->seed();
        $todos = $this->get('/perfumes')->assertOk()->viewData('productos')->modelKeys();

        $respuesta = $this->get('/perfumes?genero=niños')->assertOk();

        $this->assertSame($todos, $respuesta->viewData('productos')->modelKeys());
    }

    public function test_catalogo_acepta_marcas_multiples_y_orden_nombre(): void
    {
        $this->seed();

        $respuesta = $this->get('/perfumes?marca[]=chanel&marca[]=dior&orden=nombre')->assertOk();

        $this->assertCount(6, $respuesta->viewData('productos'));
        $this->assertSame(['1957', 'Bleu de Chanel', 'Coco Mademoiselle', 'Gris Dior', 'J’adore', 'Sauvage'], $respuesta->viewData('productos')->pluck('nombre')->all());
    }
}
