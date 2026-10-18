<?php

namespace Tests\Feature\Tienda;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginasInformativasTest extends TestCase
{
    use RefreshDatabase;

    public function test_como_funciona_explica_decant_tamanos_y_pedido(): void
    {
        $this->get('/como-funciona')->assertOk()->assertSeeText(['Perfumes de lujo,', '5 o 10 ml', 'Lo pedís por WhatsApp', '¿Los perfumes son originales?', '¿Cuánto tarda el envío?', '¿Cómo pago?', '¿Puedo retirar en persona?'])
            ->assertSee('href="'.route('tienda.catalogo').'"', false)->assertSee('<details', false)->assertDontSeeText(['15 ml', '24 a 48']);
    }

    public function test_404_usa_diseno_de_la_tienda_con_salida(): void
    {
        $this->get('/pagina-inexistente')->assertNotFound()->assertSeeText(['No encontramos esta página', 'Volver al inicio', 'Ver perfumes'])->assertSee('href="'.route('tienda.inicio').'"', false)->assertSee('images/logo-mf.png', false);
    }
}
