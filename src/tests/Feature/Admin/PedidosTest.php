<?php

namespace Tests\Feature\Admin;

use App\Models\Pedido;
use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PedidosTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_filtra_por_estado_con_contadores(): void
    {
        $a = $this->pedido();
        $b = $this->pedido('confirmado');

        $this->actingAs($this->admin())->get('/admin/pedidos?estado=pendiente')->assertOk()
            ->assertSeeText($a->numero)->assertDontSeeText($b->numero)
            ->assertViewHas('contadores', fn ($c) => $c['pendiente'] === 1 && $c['confirmado'] === 1);
    }

    #[TestWith(['MF-0001'])]
    #[TestWith(['María'])]
    #[TestWith(['0981234567'])]
    public function test_busca_por_numero_nombre_o_telefono(string $q): void
    {
        $a = $this->pedido();
        $b = $this->pedido();
        $b->update(['nombre_contacto' => 'Otra persona', 'telefono' => '0971000000']);

        $this->actingAs($this->admin())->get('/admin/pedidos?q='.urlencode($q))->assertOk()
            ->assertSeeText($a->numero)->assertDontSeeText($b->numero);
    }

    public function test_detalle_muestra_productos_totales_cliente_e_historial(): void
    {
        $p = $this->pedido();

        $this->actingAs($this->admin())->get('/admin/pedidos/'.$p->id)->assertOk()->assertSeeText([
            $p->numero, 'María Paz', '0981234567', 'Historial', 'Pendiente', '₲ 60.000', '₲ 20.000', '₲ 80.000', 'Retiro',
            $p->detalles->first()->presentacion->producto->nombre,
        ]);
    }

    public function test_detalle_tiene_enlace_whatsapp_a_la_clienta(): void
    {
        $p = $this->pedido();

        $this->actingAs($this->admin())->get('/admin/pedidos/'.$p->id)->assertOk()->assertSee('href="https://wa.me/595981234567"', false);
    }

    public function test_boton_confirmar_cambia_estado_y_descuenta_stock(): void
    {
        $p = $this->pedido();
        $presentacion = $p->detalles->first()->presentacion;
        $this->actingAs($this->admin())->post('/admin/pedidos/'.$p->id.'/estado', ['estado' => 'confirmado'])->assertRedirect('/admin/pedidos/'.$p->id)->assertSessionHasNoErrors();

        $this->assertSame('confirmado', $p->fresh()->estado);
        $this->assertSame(8, $presentacion->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->post('/admin/pedidos/'.$p->id.'/estado', ['estado' => 'confirmado'])->assertSessionHasErrors('estado');
        $this->assertSame(8, $presentacion->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 1);
    }

    public function test_error_de_stock_se_muestra_con_el_perfume_y_tamano(): void
    {
        $p = $this->pedido();
        $pres = $p->detalles->first()->presentacion;
        $pres->update(['stock' => 1]);

        $respuesta = $this->actingAs($this->admin())->post('/admin/pedidos/'.$p->id.'/estado', ['estado' => 'confirmado'])->assertRedirect('/admin/pedidos/'.$p->id)
            ->assertSessionHasErrors('estado');
        $cookie = config('session.cookie');
        $this->withCookie($cookie, $respuesta->getCookie($cookie)->getValue())->get('/admin/pedidos/'.$p->id)->assertOk()->assertSeeText([$pres->producto->nombre, $pres->tamano->nombre, 'no tiene stock suficiente']);
        $this->assertSame('pendiente', $p->fresh()->estado);
        $this->assertSame(1, $pres->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    #[TestWith(['pendiente', 'enviado'])]
    #[TestWith(['enviado', 'cancelado'])]
    #[TestWith(['entregado', 'cancelado'])]
    public function test_transicion_invalida_vuelve_con_error(string $origen, string $destino): void
    {
        $p = $this->pedido($origen);

        $this->actingAs($this->admin())->post('/admin/pedidos/'.$p->id.'/estado', ['estado' => $destino])->assertRedirect('/admin/pedidos/'.$p->id)->assertSessionHasErrors('estado');
        $this->assertSame($origen, $p->fresh()->estado);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertDatabaseCount('pedido_estados', 1);
    }

    #[TestWith(['pendiente', ['confirmado', 'cancelado']])]
    #[TestWith(['confirmado', ['preparando', 'cancelado']])]
    #[TestWith(['preparando', ['enviado', 'cancelado']])]
    #[TestWith(['enviado', ['entregado']])]
    #[TestWith(['entregado', []])]
    #[TestWith(['cancelado', []])]
    public function test_solo_se_muestran_botones_de_transiciones_validas(string $estado, array $validos): void
    {
        $p = $this->pedido($estado);
        $html = $this->actingAs($this->admin())->get('/admin/pedidos/'.$p->id)->assertOk()->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $valores = [];
        foreach ((new \DOMXPath($dom))->query('//input[@name="estado"]') as $input) {
            $valores[] = $input->getAttribute('value');
        }
        $this->assertSame($validos, $valores);
    }

    public function test_rutas_exigen_admin(): void
    {
        $p = $this->pedido();
        $this->get('/admin/pedidos')->assertRedirect('/login');
        $this->get('/admin/pedidos/'.$p->id)->assertRedirect('/login');
        $this->post('/admin/pedidos/'.$p->id.'/estado', ['estado' => 'confirmado'])->assertRedirect('/login');
        $this->actingAs($this->admin('cliente'));
        $this->get('/admin/pedidos')->assertForbidden();
        $this->get('/admin/pedidos/'.$p->id)->assertForbidden();
        $this->post('/admin/pedidos/'.$p->id.'/estado', ['estado' => 'confirmado'])->assertForbidden();
        $this->assertSame('pendiente', $p->fresh()->estado);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_datos_historicos_nulos_y_texto_escapado(): void
    {
        $p = $this->pedido();
        $p->update(['nombre_contacto' => '<script>malo()</script>', 'telefono' => null, 'metodo_entrega' => null]);

        $this->actingAs($this->admin())->get('/admin/pedidos/'.$p->id)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>malo()', false)->assertDontSee('href="https://wa.me/', false);
    }

    #[TestWith([[]])]
    #[TestWith([['estado' => ['confirmado']]])]
    #[TestWith([['estado' => 'otro']])]
    public function test_valida_estado_sin_cambios(array $datos): void
    {
        $p = $this->pedido();
        $this->actingAs($this->admin())->from('/admin/pedidos/'.$p->id)->post('/admin/pedidos/'.$p->id.'/estado', $datos)->assertRedirect('/admin/pedidos/'.$p->id)->assertSessionHasErrors(['estado' => 'Elegí un estado válido.']);
        $this->assertSame('pendiente', $p->fresh()->estado);
    }

    private function pedido(string $estado = 'pendiente'): Pedido
    {
        $p = Pedido::create(['fecha' => now(), 'estado' => $estado, 'total' => 80000, 'subtotal' => 60000, 'costo_envio' => 20000, 'nombre_contacto' => 'María Paz', 'telefono' => '0981234567', 'metodo_entrega' => 'retiro']);
        $pres = ProductoPresentacion::factory()->create();
        $p->detalles()->create(['presentacion_id' => $pres->id, 'cantidad' => 2, 'precio_unitario' => 30000, 'subtotal' => 60000]);
        $p->estados()->create(['estado' => $estado, 'created_at' => now()]);

        return $p;
    }

    private function admin(string $rol = 'administrador'): Usuario
    {
        return Usuario::create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id, 'nombre' => 'Paz', 'email' => $rol.'@example.com', 'password' => 'no-se-usa']);
    }
}
