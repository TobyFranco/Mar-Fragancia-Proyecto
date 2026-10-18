<?php

namespace Tests\Feature\Tienda;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarcasTest extends TestCase
{
    use RefreshDatabase;

    public function test_indice_de_marcas_agrupa_a_z_con_cantidad(): void
    {
        $this->seed();
        Producto::where('nombre', 'Sauvage')->update(['activo' => false]);

        $respuesta = $this->get('/marcas')->assertOk()->assertSee(['Marcas', 'Dior', '2 perfumes', '#letra-D'], false)->assertDontSeeText('1 perfumes');

        $this->assertTrue($respuesta->viewData('grupos')->has('D'));
        $this->assertSame(2, $respuesta->viewData('grupos')['D']->firstWhere('nombre', 'Dior')->productos_count);
    }

    public function test_pagina_de_marca_filtra_por_genero(): void
    {
        $this->seed();

        $respuesta = $this->get('/marcas/chanel?genero=hombre')->assertOk()->assertSee(['Todos', 'Mujer', 'Hombre', 'Para compartir'])->assertDontSeeText('1 perfumes');

        $this->assertSame(['Bleu de Chanel'], $respuesta->viewData('productos')->pluck('nombre')->all());
        $this->assertStringNotContainsString('Coco Mademoiselle', $this->resultados($respuesta->getContent()));
        $this->assertStringContainsString('Bleu de Chanel', $this->resultados($respuesta->getContent()));
    }

    public function test_marca_inexistente_da_404(): void
    {
        $this->get('/marcas/no-existe')->assertNotFound();
    }

    private function resultados(string $html): string
    {
        preg_match('/<section[^>]*id="resultados-perfumes".*?<\/section>/s', $html, $resultado);

        return $resultado[0] ?? '';
    }
}
