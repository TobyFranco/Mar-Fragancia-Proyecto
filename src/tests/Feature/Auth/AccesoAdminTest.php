<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AccesoAdminTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([null])]
    #[TestWith(['cliente'])]
    #[TestWith(['administrador'])]
    public function test_admin_exige_rol(?string $rol): void
    {
        if ($rol) {
            $usuario = Usuario::create(['rol_id' => Rol::create(['nombre' => $rol])->id, 'nombre' => 'María Paz', 'email' => 'paz@example.com', 'password' => Hash::make('password123')]);
            $this->actingAs($usuario);
        }
        foreach (['/admin/dashboard', '/admin/productos', '/admin/marcas', '/admin/categorias', '/admin/tamanos'] as $ruta) {
            $respuesta = $this->get($ruta);
            match ($rol) {
                null => $respuesta->assertRedirect('/login'),
                'cliente' => $respuesta->assertForbidden(),
                default => $respuesta->assertOk(),
            };
        }
    }
}
