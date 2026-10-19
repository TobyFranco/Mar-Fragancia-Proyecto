<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_admin_redirige_al_dashboard(): void
    {
        $usuario = $this->usuario('administrador');
        $this->post('/login', ['email' => $usuario->email, 'password' => 'password123'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_login_cliente_redirige_a_cliente_home(): void
    {
        $usuario = $this->usuario('cliente');
        $this->post('/login', ['email' => $usuario->email, 'password' => 'password123'])->assertRedirect(route('cliente.home'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_credenciales_invalidas_vuelven_con_error_y_conservan_email(): void
    {
        $usuario = $this->usuario('cliente');
        $this->from('/login')->post('/login', ['email' => $usuario->email, 'password' => 'incorrecta'])
            ->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Credenciales incorrectas.'])->assertSessionHasInput('email', $usuario->email)->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }

    public function test_logout_invalida_la_sesion(): void
    {
        $usuario = $this->usuario('cliente');
        $this->actingAs($usuario)->withSession(['dato_privado' => 'valor'])->post('/logout')->assertRedirect('/login')->assertSessionMissing('dato_privado');
        $this->assertGuest();
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    #[TestWith(['/login'])]
    #[TestWith(['/register'])]
    public function test_vistas_de_auth_usan_el_nuevo_diseno(string $ruta): void
    {
        $this->get($ruta)->assertOk()->assertSeeText(['Ingresar', 'Crear cuenta', 'Para comprar no hace falta cuenta', 'seguí sin cuenta'])
            ->assertSee('href="'.route('tienda.inicio').'"', false)->assertSee('class="auth-tabs"', false);
    }

    #[TestWith(['email'])]
    #[TestWith(['password'])]
    public function test_login_rechaza_arrays_y_permite_volver_al_formulario(string $campo): void
    {
        $this->usuario('cliente');
        $r = $this->from('/login')->post('/login', ['email' => 'paz@example.com', 'password' => 'password123', $campo => ['manipulado']])->assertRedirect('/login')->assertSessionHasErrors($campo);
        $cookie = config('session.cookie');
        $this->withCookie($cookie, $r->getCookie($cookie)->getValue())->get('/login')->assertOk()->assertSee('role="alert"', false);
        $this->assertGuest();
    }

    private function usuario(string $rol): Usuario
    {
        return Usuario::create(['rol_id' => Rol::create(['nombre' => $rol])->id, 'nombre' => 'María Paz', 'email' => 'paz@example.com', 'password' => Hash::make('password123')]);
    }
}
