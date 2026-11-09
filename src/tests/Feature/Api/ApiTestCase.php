<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function usuario(string $rol = 'cliente'): Usuario
    {
        return Usuario::create(['nombre' => 'Paz', 'email' => Str::uuid().'@example.com', 'password' => Hash::make('password123'), 'rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id]);
    }

    protected function bearer(Usuario $u): static
    {
        app('auth')->forgetGuards();

        return $this->withToken($u->createToken('prueba')->plainTextToken);
    }

    protected function pedido(Usuario $u, array $datos = []): Pedido
    {
        return Pedido::create(array_merge(['cliente_id' => Cliente::firstOrCreate(['usuario_id' => $u->id])->id, 'fecha' => now(), 'estado' => 'pendiente', 'total' => 60000, 'subtotal' => 60000, 'costo_envio' => 0], $datos));
    }
}
