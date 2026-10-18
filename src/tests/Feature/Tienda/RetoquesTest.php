<?php

namespace Tests\Feature\Tienda;

use App\Models\Producto;
use App\Models\ProductoPresentacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetoquesTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_identifica_marca_activa_en_marca_y_perfume(): void
    {
        $this->seed();
        foreach (['/marcas/chanel', '/perfumes/bleu-de-chanel', '/perfumes?marca=chanel'] as $ruta) {
            $html = $this->get($ruta)->assertOk()->getContent();
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $this->assertSame(1, (new \DOMXPath($dom))->query('//a[contains(@class,"brand-link") and @data-activa="true" and contains(@href,"/marcas/chanel")]')->length);
        }
    }

    public function test_sugerencias_agrupan_perfumes_marcas_y_notas_con_resaltado(): void
    {
        $this->seed();
        $this->get('/buscar?q=hermes&sugerencias=1')->assertOk()->assertDontSee('<header', false)->assertSee('<mark>Hermès</mark>', false)->assertSeeText(['Perfumes (3)', 'Terre d’Hermès']);
        $this->get('/buscar?q=vainilla&sugerencias=1')->assertOk()->assertSee('<mark>vainilla</mark>', false)->assertSeeText('Black Opium');
        $this->get('/buscar?q=sau&sugerencias=1')->assertOk()->assertSee('<mark>Sau</mark>vage', false);
        $this->get('/buscar?q=sau')->assertOk()->assertSee('<header', false)->assertSeeText('Sauvage');
    }

    public function test_sugerencias_escapan_contenido_y_aceptan_consulta_vacia_o_array(): void
    {
        $p = Producto::factory()->create(['nombre' => '<img src=x onerror=alert(1)>']);
        ProductoPresentacion::factory()->for($p, 'producto')->create();
        $this->get('/buscar?q=img&sugerencias=1')->assertOk()->assertDontSee('<img src=x onerror=', false)->assertSee('&lt;<mark>img</mark>', false);
        $this->get('/buscar?q[]=x&sugerencias=1')->assertOk()->assertSeeText('Buscá por perfume, marca o nota.');
        $this->get('/buscar?q=&sugerencias=1')->assertOk()->assertDontSee('<mark>', false);
    }
}
