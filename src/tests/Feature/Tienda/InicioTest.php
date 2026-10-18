<?php

namespace Tests\Feature\Tienda;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class InicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_inicio_muestra_marcas_del_menu_en_orden_y_enlaces_informativos(): void
    {
        $this->seed();

        $this->get('/')->assertOk()->assertSeeInOrder(['Chanel', 'Dior', 'Tom Ford', 'Creed', 'Guerlain', 'Hermès', 'Yves Saint Laurent', 'Maison Francis Kurkdjian', 'Todas las marcas', 'Termómetro olfativo', 'Cómo funciona']);
    }

    public function test_inicio_muestra_hero_pasos_y_mas_probados(): void
    {
        $this->seed();

        $respuesta = $this->get('/')->assertOk()->assertSee(['Perfumes de lujo,', 'en decant.', 'Elegí tu perfume', 'Elegí 5 o 10 ml', 'Pedí por WhatsApp', 'Los más probados']);

        $this->assertSame(4, substr_count($respuesta->getContent(), '<article'));
    }

    public function test_inicio_no_muestra_15ml_frasco_ni_bloques_descartados(): void
    {
        $this->seed();

        $this->get('/')->assertOk()->assertDontSee(['15 ml', '15ml', 'Frasco completo', 'Para ella']);
    }

    public function test_panel_de_marca_lista_perfumes_por_genero(): void
    {
        $this->seed();

        $html = $this->get('/')->assertOk()->getContent();
        preg_match('/<section[^>]*id="panel-chanel".*?<\/section>/s', $html, $panel);

        $this->assertNotEmpty($panel);
        foreach (['En portada', 'Mujer', 'Hombre', 'Para compartir', 'Coco Mademoiselle', 'Bleu de Chanel', '/marcas/chanel?genero=mujer'] as $texto) {
            $this->assertStringContainsString($texto, $panel[0]);
        }
        $this->assertStringNotContainsString('Sauvage', $panel[0]);
    }

    public function test_tarjeta_tacha_tamano_sin_stock_y_muestra_precio_desde(): void
    {
        $this->seed();
        $producto = Producto::with(['marca', 'presentaciones.tamano'])->where('nombre', 'Sauvage')->firstOrFail();

        $html = Blade::render('<x-tienda.tarjeta-perfume :producto="$producto" />', compact('producto'));

        $this->assertStringContainsString('₲ 35.000', $html);
        $this->assertStringContainsString('aria-label="10ml sin stock"', $html);
        $this->assertStringContainsString('line-through', $html);
    }

    public function test_encabezado_muestra_unidades_del_carrito(): void
    {
        $this->seed();

        $this->withSession(['carrito' => [12 => 2, 15 => 1]])->get('/')->assertOk()->assertSee('Carrito, 3 unidades');
    }
}
