<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacionApiTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): Usuario
    {
        return Usuario::create(['nombre' => 'Paz', 'email' => 'paz@example.com', 'password' => Hash::make('password123'), 'rol_id' => Rol::firstOrCreate(['nombre' => 'cliente'])->id]);
    }

    public function test_login_y_me_no_exponen_password_y_usan_token_real(): void
    {
        $u = $this->usuario();
        Cliente::create(['usuario_id' => $u->id, 'telefono' => '0981234567']);
        $r = $this->postJson('/api/v1/auth/login', ['email' => $u->email, 'password' => 'password123', 'device_name' => 'prueba'])->assertOk()->assertJsonPath('usuario.rol.nombre', 'cliente')->assertJsonMissingPath('usuario.password');
        $this->withToken($r->json('token'))->get('/api/v1/auth/me')->assertOk()->assertJsonPath('data.cliente.telefono', '0981234567')->assertJsonMissingPath('data.password');
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_incorrecto_y_validaciones(): void
    {
        $u = $this->usuario();
        $this->postJson('/api/v1/auth/login', ['email' => $u->email, 'password' => 'otra', 'device_name' => 'prueba'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/v1/auth/login', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password', 'device_name']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_revoca_solo_el_token_actual(): void
    {
        $u = $this->usuario();
        $a = $u->createToken('a')->plainTextToken;
        $b = $u->createToken('b')->plainTextToken;
        $this->withToken($a)->post('/api/v1/auth/logout')->assertOk();
        app('auth')->forgetGuards();
        $this->withToken($a)->get('/api/v1/auth/me')->assertUnauthorized();
        app('auth')->forgetGuards();
        $this->withToken($b)->get('/api/v1/auth/me')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_limita_intentos_y_error429_es_json(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'a@example.com', 'password' => 'mala', 'device_name' => 'x'])->assertUnprocessable();
        }
        $this->post('/api/v1/auth/login', ['email' => 'a@example.com', 'password' => 'mala', 'device_name' => 'x'])->assertStatus(429)->assertHeader('Content-Type', 'application/json');
    }

    public function test_sesion_web_no_sustituye_el_bearer(): void
    {
        $this->actingAs($this->usuario())->get('/api/v1/auth/me')->assertUnauthorized();
    }
}
