<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_del_panel_no_ofrece_crear_cuenta(): void
    {
        $this->get('/admin/login')->assertOk()
            ->assertSeeText(['Panel de administración', 'Ingresar'])
            ->assertDontSeeText(['Crear cuenta', 'Creá tu cuenta', 'seguí sin cuenta'])
            ->assertDontSee(route('register'), false);
    }

    public function test_invitado_en_el_panel_va_al_login_del_panel(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->get('/cliente/home')->assertRedirect('/login');
    }

    public function test_admin_ingresa_al_dashboard(): void
    {
        $admin = $this->usuario('administrador');

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_cliente_no_puede_ingresar_por_el_login_del_panel(): void
    {
        $cliente = $this->usuario('cliente');

        $this->from('/admin/login')->post('/admin/login', ['email' => $cliente->email, 'password' => 'password123'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'Credenciales incorrectas.']);
        $this->assertGuest();
    }

    public function test_credenciales_invalidas_vuelven_al_login_del_panel(): void
    {
        $admin = $this->usuario('administrador');

        $this->from('/admin/login')->post('/admin/login', ['email' => $admin->email, 'password' => 'incorrecta'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'Credenciales incorrectas.'])
            ->assertSessionHasInput('email', $admin->email);
        $this->assertGuest();
    }

    public function test_admin_que_cierra_sesion_vuelve_al_login_del_panel(): void
    {
        $this->actingAs($this->usuario('administrador'))->post('/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    private function usuario(string $rol): Usuario
    {
        return Usuario::create(['rol_id' => Rol::create(['nombre' => $rol])->id, 'nombre' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password123')]);
    }
}
