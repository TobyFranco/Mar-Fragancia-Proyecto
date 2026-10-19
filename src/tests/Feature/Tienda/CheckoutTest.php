<?php

namespace Tests\Feature\Tienda;

use App\Models\Cliente;
use App\Models\Marca;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Tamano;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_crea_pedido_pendiente_y_redirige_a_whatsapp(): void
    {
        [$a, $b] = $this->carrito();
        $respuesta = $this->post('/checkout', [...$this->datos(), 'total' => 1, 'estado' => 'confirmado', 'cliente_id' => 999]);
        $respuesta->assertRedirect()->assertSessionMissing('carrito')->assertSessionMissing('carrito_ultimo');
        $this->assertStringStartsWith('https://wa.me/595900000000?text=', $respuesta->headers->get('Location'));
        $p = Pedido::with('detalles')->sole();
        $this->assertSame('MF-0001', $p->numero);
        $this->assertDatabaseHas('pedidos', ['id' => $p->id, 'estado' => 'pendiente', 'cliente_id' => null, 'subtotal' => 124000, 'costo_envio' => 20000, 'total' => 144000]);
        $this->assertCount(2, $p->detalles);
        $this->assertDatabaseHas('detalle_pedidos', ['pedido_id' => $p->id, 'presentacion_id' => $a->id, 'cantidad' => 1, 'precio_unitario' => 60000, 'subtotal' => 60000]);
        $this->assertDatabaseHas('detalle_pedidos', ['pedido_id' => $p->id, 'presentacion_id' => $b->id, 'cantidad' => 2, 'precio_unitario' => 32000, 'subtotal' => 64000]);
        $this->assertSame(5, $a->fresh()->stock);
        $this->assertSame(5, $b->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->post('/checkout', $this->datos())->assertRedirect('/carrito');
        $this->assertDatabaseCount('pedidos', 1);
    }

    #[TestWith(['retiro', false])]
    #[TestWith(['asuncion', true])]
    #[TestWith(['interior', true])]
    public function test_valida_campos_requeridos_segun_metodo(string $metodo, bool $requiereDireccion): void
    {
        $this->carrito();
        $datos = [...$this->datos(), 'metodo_entrega' => $metodo, 'ciudad' => '', 'direccion' => ''];
        $respuesta = $this->from('/checkout')->post('/checkout', $datos);
        if ($requiereDireccion) {
            $respuesta->assertRedirect('/checkout')->assertSessionHasErrors(['ciudad', 'direccion']);
            $this->assertDatabaseCount('pedidos', 0);
        } else {
            $respuesta->assertSessionHasNoErrors();
            $this->assertDatabaseHas('pedidos', ['metodo_entrega' => 'retiro', 'costo_envio' => 0, 'ciudad' => null, 'direccion' => null, 'barrio' => null, 'referencia' => null]);
        }
    }

    #[TestWith(['0981 234 567'])]
    #[TestWith(['0981234567'])]
    #[TestWith(['+595 981 234 567'])]
    public function test_normaliza_celular_paraguayo(string $telefono): void
    {
        $this->carrito();
        $this->post('/checkout', [...$this->datos(), 'telefono' => $telefono])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pedidos', ['telefono' => '0981234567']);
    }

    #[TestWith(['0981 23'])]
    #[TestWith(['0181234567'])]
    #[TestWith(['abc0981234567'])]
    #[TestWith([['0981234567']])]
    public function test_rechaza_celular_invalido(mixed $telefono): void
    {
        $this->carrito();
        $this->from('/checkout')->post('/checkout', [...$this->datos(), 'telefono' => $telefono])->assertRedirect('/checkout')
            ->assertSessionHasErrors(['telefono' => 'Revisá el número: deben ser 10 dígitos, ej. 0981 234 567.']);
        $this->assertDatabaseCount('pedidos', 0);
    }

    #[TestWith([null, true])]
    #[TestWith(['paz@example.com', true])]
    #[TestWith(['mal-email', false])]
    public function test_email_opcional_pero_valido(?string $email, bool $valido): void
    {
        $this->carrito();
        $respuesta = $this->post('/checkout', [...$this->datos(), 'email' => $email]);
        if ($valido) {
            $respuesta->assertSessionHasNoErrors();
            $this->assertDatabaseHas('pedidos', ['email' => $email]);
        } else {
            $respuesta->assertSessionHasErrors('email');
            $this->assertDatabaseCount('pedidos', 0);
        }
    }

    public function test_checkout_con_carrito_vacio_redirige_al_carrito(): void
    {
        $this->get('/checkout')->assertRedirect('/carrito');
        $this->post('/checkout', $this->datos())->assertRedirect('/carrito');
        $this->assertDatabaseCount('pedidos', 0);
    }

    #[TestWith(['stock', 0])]
    #[TestWith(['stock', 1])]
    #[TestWith(['activo', false])]
    public function test_checkout_rechaza_linea_sin_stock_y_no_crea_pedido(string $campo, mixed $valor): void
    {
        [$a, $b] = $this->carrito();
        $b->update([$campo => $valor]);
        $this->from('/checkout')->post('/checkout', $this->datos())->assertRedirect('/checkout')->assertSessionHasErrors('carrito')
            ->assertSessionHas('carrito', [$a->id => 1, $b->id => 2])
            ->assertSessionHas('errors', fn ($errors) => str_contains($errors->first('carrito'), 'Good Girl · Decant 5 ml'));
        $this->get('/checkout')->assertOk()->assertSeeText(['Good Girl', '5 ml']);
        $this->assertDatabaseCount('pedidos', 0);
        $this->assertDatabaseCount('detalle_pedidos', 0);
        $this->assertSame(5, $a->fresh()->stock);
    }

    public function test_checkout_rechaza_producto_desactivado_o_linea_eliminada(): void
    {
        [$a, $b] = $this->carrito();
        $b->producto->update(['activo' => false]);
        $this->post('/checkout', $this->datos())->assertSessionHasErrors('carrito');
        $this->assertDatabaseCount('pedidos', 0);
        $b->delete();
        $this->post('/checkout', $this->datos())->assertSessionHasErrors('carrito')->assertSessionHas('carrito', [$a->id => 1, $b->id => 2]);
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_linea_eliminada_se_puede_quitar_desde_la_pagina_del_carrito(): void
    {
        [$a, $b] = $this->carrito();
        $id = $b->id;
        $b->delete();
        $this->get('/carrito')->assertOk()->assertSeeText('Quitá el decant que ya no está disponible')->assertSee('action="'.route('tienda.carrito.quitar', $id).'"', false);
        $this->delete('/carrito/'.$id)->assertSessionHas('carrito', [$a->id => 1]);
        $this->post('/checkout', $this->datos())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pedidos', ['subtotal' => 60000]);
    }

    public function test_totales_usan_precio_y_costo_configurado_del_momento(): void
    {
        [$a] = $this->carrito();
        $a->update(['precio' => 70000]);
        config(['tienda.envio_asuncion' => 25000]);
        $this->get('/checkout')->assertOk()->assertSeeText(['₲ 134.000', '₲ 25.000', '₲ 159.000']);
        $this->post('/checkout', $this->datos())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pedidos', ['subtotal' => 134000, 'costo_envio' => 25000, 'total' => 159000]);
    }

    public function test_envio_al_interior_se_guarda_sin_costo(): void
    {
        $this->carrito();
        $this->post('/checkout', [...$this->datos(), 'metodo_entrega' => 'interior'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pedidos', ['metodo_entrega' => 'interior', 'costo_envio' => 0, 'total' => 124000]);
    }

    public function test_cliente_logueado_precarga_datos_y_asocia_pedido(): void
    {
        $rol = Rol::create(['nombre' => 'cliente']);
        $usuario = Usuario::create(['rol_id' => $rol->id, 'nombre' => 'María Paz', 'email' => 'paz@example.com', 'password' => bcrypt('password')]);
        $cliente = Cliente::create(['usuario_id' => $usuario->id, 'telefono' => '0981234567', 'direccion' => 'Mi dirección']);
        $this->carrito();
        $this->actingAs($usuario)->get('/checkout')->assertOk()->assertSee(['María Paz', 'paz@example.com', '0981234567', 'Mi dirección']);
        $this->post('/checkout', $this->datos())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pedidos', ['cliente_id' => $cliente->id]);
    }

    public function test_pagina_pedido_enviado_muestra_numero_y_boton_whatsapp(): void
    {
        $this->carrito();
        $this->post('/checkout', $this->datos());
        $p = Pedido::sole();
        $this->get('/pedido/'.$p->numero)->assertOk()->assertSeeText([$p->numero, 'Abrir WhatsApp de nuevo', '₲ 144.000'])->assertSee('https://wa.me/595900000000?text=', false);
        $this->get('/carrito')->assertOk()->assertSeeText(['Tu carrito está vacío', 'Volver a mi pedido '.$p->numero]);
    }

    public function test_pedido_de_otra_sesion_no_expone_datos_por_numero_secuencial(): void
    {
        $p = Pedido::create(['fecha' => now(), 'estado' => 'pendiente', 'total' => 60000, 'nombre_contacto' => 'Privado']);
        $this->get('/pedido/'.$p->numero)->assertNotFound();
    }

    public function test_checkout_valida_nombre_metodo_y_largos_sin_modificar_carrito(): void
    {
        [$a, $b] = $this->carrito();
        $this->post('/checkout', ['telefono' => '0981234567', 'metodo_entrega' => 'otro', 'nombre_contacto' => '', 'referencia' => str_repeat('a', 256)])
            ->assertSessionHasErrors(['nombre_contacto', 'metodo_entrega', 'referencia'])->assertSessionHas('carrito', [$a->id => 1, $b->id => 2]);
        $this->assertDatabaseCount('pedidos', 0);
    }

    #[TestWith(['nombre_contacto'])]
    #[TestWith(['telefono'])]
    #[TestWith(['cedula'])]
    #[TestWith(['email'])]
    #[TestWith(['ciudad'])]
    #[TestWith(['barrio'])]
    #[TestWith(['direccion'])]
    #[TestWith(['referencia'])]
    #[TestWith(['metodo_entrega'])]
    public function test_campos_manipulados_como_array_muestran_error_sin_romper_formulario(string $campo): void
    {
        $this->carrito();
        $respuesta = $this->from('/checkout')->post('/checkout', [...$this->datos(), $campo => ['valor manipulado']])->assertRedirect('/checkout')->assertSessionHasErrors($campo);
        $cookie = config('session.cookie');
        $html = $this->withCookie($cookie, $respuesta->getCookie($cookie)->getValue())->get('/checkout')->assertOk()->assertSee('class="field-error"', false)->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $this->assertSame(1, (new \DOMXPath($dom))->query('//input[@name="metodo_entrega" and @value="asuncion" and @checked]')->length);
        $this->assertDatabaseCount('pedidos', 0);
    }

    private function carrito(): array
    {
        $a = ProductoPresentacion::factory()->for(Producto::factory()->for(Marca::factory()->create(['nombre' => 'Dior']), 'marca')->create(['nombre' => 'Sauvage']), 'producto')
            ->for(Tamano::factory()->create(['nombre' => '10 ml']), 'tamano')->create(['precio' => 60000, 'stock' => 5]);
        $b = ProductoPresentacion::factory()->for(Producto::factory()->for(Marca::factory()->create(['nombre' => 'Carolina Herrera']), 'marca')->create(['nombre' => 'Good Girl']), 'producto')
            ->for(Tamano::factory()->create(['nombre' => '5 ml']), 'tamano')->create(['precio' => 32000, 'stock' => 5]);
        $this->withSession(['carrito' => [$a->id => 1, $b->id => 2], 'carrito_ultimo' => $a->producto_id]);

        return [$a, $b];
    }

    private function datos(): array
    {
        return ['nombre_contacto' => 'María Paz Gutiérrez', 'telefono' => '0981 234 567', 'metodo_entrega' => 'asuncion', 'ciudad' => 'Lambaré', 'barrio' => 'Centro', 'direccion' => 'Av. Cacique Lambaré 1234', 'referencia' => 'Portón negro, frente a la farmacia'];
    }
}
