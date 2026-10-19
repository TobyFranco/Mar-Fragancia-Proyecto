<?php

namespace Tests\Unit;

use App\Models\DetallePedido;
use App\Models\Marca;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Tamano;
use App\Services\MensajeWhatsapp;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class MensajeWhatsappTest extends TestCase
{
    public function test_texto_exacto_envio_asuncion(): void
    {
        $esperado = <<<'TEXTO'
¡Hola Mar Fragancias! Quiero hacer este pedido (#MF-0042):

• Sauvage (Dior) · Decant 10 ml × 1 · ₲ 60.000
• Good Girl (Carolina Herrera) · Decant 5 ml × 2 · ₲ 64.000

Subtotal: ₲ 124.000
Envío Asunción: ₲ 20.000
Total: ₲ 144.000

Nombre: María Paz Gutiérrez
Entrega: Av. Cacique Lambaré 1234, Centro, Lambaré
Ref.: Portón negro, frente a la farmacia
TEXTO;
        $this->assertSame($esperado, (new MensajeWhatsapp)->texto($this->pedido()));
    }

    public function test_texto_retiro_e_interior(): void
    {
        $p = $this->pedido();
        $p->metodo_entrega = 'retiro';
        $p->referencia = null;
        $p->costo_envio = 0;
        $p->total = 124000;
        $texto = (new MensajeWhatsapp)->texto($p);
        $this->assertStringContainsString("Envío: ₲ 0\nTotal: ₲ 124.000", $texto);
        $this->assertStringContainsString('Entrega: Retiro (a coordinar)', $texto);
        $this->assertStringNotContainsString('Ref.:', $texto);
        $p->metodo_entrega = 'interior';
        $texto = (new MensajeWhatsapp)->texto($p);
        $this->assertStringContainsString('Envío al interior: a coordinar', $texto);
        $this->assertStringContainsString('Entrega: Av. Cacique Lambaré 1234, Centro, Lambaré', $texto);
    }

    public function test_url_de_whatsapp_codifica_numeral_tildes_y_saltos(): void
    {
        config(['tienda.whatsapp' => '595900000000']);
        $p = $this->pedido();
        $p->nombre_contacto = 'María Paz & Luz';
        $mensaje = new MensajeWhatsapp;
        $url = $mensaje->url($p);
        $this->assertStringStartsWith('https://wa.me/595900000000?text=', $url);
        foreach (['%23MF-0042', '%0A', '%26', 'Mar%C3%ADa'] as $fragmento) {
            $this->assertStringContainsString($fragmento, $url);
        }
        $this->assertStringNotContainsString('#', $url);
        $this->assertStringNotContainsString(' ', $url);
        $this->assertNull(parse_url($url, PHP_URL_FRAGMENT));
        $this->assertSame($mensaje->texto($p), rawurldecode(substr($url, strpos($url, '?text=') + 6)));
    }

    private function pedido(): Pedido
    {
        $p = new Pedido(['subtotal' => 124000, 'costo_envio' => 20000, 'total' => 144000, 'metodo_entrega' => 'asuncion', 'nombre_contacto' => 'María Paz Gutiérrez', 'direccion' => 'Av. Cacique Lambaré 1234', 'barrio' => 'Centro', 'ciudad' => 'Lambaré', 'referencia' => 'Portón negro, frente a la farmacia']);
        $p->numero = 'MF-0042';
        $detalles = new Collection;
        foreach ([['Sauvage', 'Dior', '10 ml', 1, 60000], ['Good Girl', 'Carolina Herrera', '5 ml', 2, 64000]] as [$nombre, $marca, $tamano, $cantidad, $subtotal]) {
            $producto = (new Producto(['nombre' => $nombre]))->setRelation('marca', new Marca(['nombre' => $marca]));
            $presentacion = (new ProductoPresentacion)->setRelation('producto', $producto)->setRelation('tamano', new Tamano(['nombre' => $tamano]));
            $detalles->push((new DetallePedido(['cantidad' => $cantidad, 'subtotal' => $subtotal]))->setRelation('presentacion', $presentacion));
        }

        return $p->setRelation('detalles', $detalles);
    }
}
