<?php

namespace Tests\Feature\Tienda;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaClienteTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $email = 'ana@example.com', string $rol = 'cliente'): Usuario
    {
        $rol = Rol::firstOrCreate(['nombre' => $rol]);

        return Usuario::create(['rol_id' => $rol->id, 'nombre' => 'Ana', 'email' => $email, 'password' => bcrypt('password')]);
    }

    public function test_cuenta_muestra_datos_y_accesos(): void
    {
        $u = $this->usuario();
        Cliente::create(['usuario_id' => $u->id, 'telefono' => '0981234567', 'direccion' => 'Calle Uno']);
        $this->actingAs($u)->get('/cliente/home')->assertOk()->assertSeeText(['Ana', 'ana@example.com', '0981234567', 'Calle Uno', 'Mis datos', 'Mis pedidos', 'Salir'])->assertSee('/cliente/datos')->assertSee('/cliente/pedidos');
        $this->get('/')->assertSee('href="'.url('/cliente/home').'" class="icon-button account-link"', false);
    }

    public function test_edita_solo_sus_datos_y_crea_cliente_si_falta(): void
    {
        $u = $this->usuario();
        $otra = Cliente::create(['usuario_id' => $this->usuario('otra@example.com')->id, 'telefono' => '123']);
        $this->actingAs($u)->get('/cliente/datos')->assertOk()->assertSee('Ana');
        $this->put('/cliente/datos', ['nombre' => 'Ana Nueva', 'telefono' => '456', 'direccion' => 'Mi casa', 'email' => 'cambio@example.com', 'cliente_id' => $otra->id])->assertRedirect();
        $this->assertDatabaseHas('usuarios', ['id' => $u->id, 'nombre' => 'Ana Nueva', 'email' => 'ana@example.com']);
        $this->assertDatabaseHas('clientes', ['usuario_id' => $u->id, 'telefono' => '456', 'direccion' => 'Mi casa']);
        $this->assertDatabaseHas('clientes', ['id' => $otra->id, 'telefono' => '123']);
        $this->put('/cliente/datos', ['nombre' => 'Ana', 'telefono' => null, 'direccion' => null])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('clientes', 2);
        $this->assertDatabaseHas('clientes', ['usuario_id' => $u->id, 'telefono' => null, 'direccion' => null]);
    }

    public function test_valida_datos_sin_guardar_cambios(): void
    {
        $u = $this->usuario();
        $this->actingAs($u)->from('/cliente/datos')->put('/cliente/datos', ['nombre' => str_repeat('a', 101), 'telefono' => str_repeat('1', 31), 'direccion' => str_repeat('x', 256)])->assertSessionHasErrors(['nombre', 'telefono', 'direccion']);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertSame('Ana', $u->fresh()->nombre);
        $this->put('/cliente/datos', ['nombre' => '', 'telefono' => [], 'direccion' => []])->assertSessionHasErrors(['nombre', 'telefono', 'direccion']);
    }

    public function test_pedidos_son_propios_ordenados_paginados_y_enlazados(): void
    {
        $u = $this->usuario();
        $cliente = Cliente::create(['usuario_id' => $u->id]);
        $otro = Cliente::create(['usuario_id' => $this->usuario('otra@example.com')->id]);
        $ajeno = Pedido::create(['cliente_id' => $otro->id, 'fecha' => now(), 'estado' => 'pendiente', 'total' => 100]);
        for ($i = 0; $i < 16; $i++) {
            $pedido = Pedido::create(['cliente_id' => $cliente->id, 'fecha' => now()->addDays($i), 'estado' => 'confirmado', 'total' => 123456]);
            if ($i === 0) {
                $primero = $pedido;
            }
        }
        $this->actingAs($u)->get('/cliente/pedidos')->assertOk()->assertSeeText([$pedido->numero, 'Confirmado', '₲ 123.456'])->assertDontSee($ajeno->numero)->assertDontSee($primero->numero)->assertSee(route('tienda.pedido', $pedido->numero))->assertSeeInOrder([$pedido->numero, 'MF-0016']);
        $this->get('/cliente/pedidos?page=2')->assertOk()->assertSee($primero->numero)->assertDontSee($ajeno->numero);
        $this->get('/pedido/'.$ajeno->numero)->assertNotFound();
    }

    public function test_sin_cliente_ni_pedidos_muestra_estado_vacio(): void
    {
        $this->actingAs($this->usuario())->get('/cliente/pedidos')->assertOk()->assertSeeText('Todavía no tenés pedidos')->assertSee(route('tienda.catalogo'));
    }

    public function test_visitantes_y_administradores_no_acceden(): void
    {
        foreach (['/cliente/home', '/cliente/datos', '/cliente/pedidos'] as $ruta) {
            $this->get($ruta)->assertRedirect('/login');
        }
        $this->put('/cliente/datos', [])->assertRedirect('/login');
        $this->actingAs($this->usuario('admin@example.com', 'administrador'));
        foreach (['/cliente/home', '/cliente/datos', '/cliente/pedidos'] as $ruta) {
            $this->get($ruta)->assertForbidden();
        }
        $this->put('/cliente/datos', [])->assertForbidden();
    }
}
