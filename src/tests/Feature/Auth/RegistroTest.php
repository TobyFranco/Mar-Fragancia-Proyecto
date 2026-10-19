<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RegistroTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_crea_usuario_cliente_y_perfil_y_autentica(): void
    {
        $rol = Rol::create(['nombre' => 'cliente']);
        $this->post('/register', [...$this->datos(), 'rol_id' => 999])->assertRedirect(route('cliente.home'));
        $usuario = Usuario::where('email', 'paz@example.com')->sole();
        $this->assertAuthenticatedAs($usuario);
        $this->assertSame($rol->id, $usuario->rol_id);
        $this->assertTrue(Hash::check('password123', $usuario->password));
        $this->assertDatabaseHas('clientes', ['usuario_id' => $usuario->id, 'telefono' => '0981234567', 'direccion' => 'Av. Principal']);
    }

    public function test_registro_rechaza_email_duplicado_y_contrasena_sin_confirmar(): void
    {
        $rol = Rol::create(['nombre' => 'cliente']);
        Usuario::create(['rol_id' => $rol->id, 'nombre' => 'Existente', 'email' => 'paz@example.com', 'password' => Hash::make('password123')]);
        $this->from('/register')->post('/register', [...$this->datos(), 'password_confirmation' => 'otra'])->assertRedirect('/register')->assertSessionHasErrors(['email', 'password']);
        $this->assertDatabaseCount('usuarios', 1);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertGuest();
    }

    #[TestWith(['nombre'])]
    #[TestWith(['email'])]
    #[TestWith(['password'])]
    #[TestWith(['telefono'])]
    #[TestWith(['direccion'])]
    public function test_registro_rechaza_arrays_sin_romper_la_vista(string $campo): void
    {
        Rol::create(['nombre' => 'cliente']);
        $valor = array_fill(0, 6, 'manipulado');
        $extra = $campo === 'password' ? ['password_confirmation' => $valor] : [];
        $r = $this->from('/register')->post('/register', [...$this->datos(), $campo => $valor, ...$extra])->assertRedirect('/register')->assertSessionHasErrors($campo);
        $cookie = config('session.cookie');
        $this->withCookie($cookie, $r->getCookie($cookie)->getValue())->get('/register')->assertOk()->assertSee('role="alert"', false);
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_registro_rechaza_email_mayor_que_la_columna_existente(): void
    {
        Rol::create(['nombre' => 'cliente']);
        $email = str_repeat('a', 60).'@'.str_repeat('b', 60).'.'.str_repeat('c', 35).'.com';
        $this->post('/register', [...$this->datos(), 'email' => $email])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('usuarios', 0);
    }

    private function datos(): array
    {
        return ['nombre' => 'María Paz', 'email' => 'paz@example.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'telefono' => '0981234567', 'direccion' => 'Av. Principal'];
    }
}
