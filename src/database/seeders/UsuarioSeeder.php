<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $adminRol = Rol::where('nombre', 'administrador')->first();
        $clienteRol = Rol::where('nombre', 'cliente')->first();

        Usuario::create([
            'rol_id' => $adminRol->id,
            'nombre' => 'Tobias Franco',
            'email' => 'admin@marfragancia.com',
            'password' => Hash::make('password123'),
        ]);

        Usuario::create([
            'rol_id' => $clienteRol->id,
            'nombre' => 'Maria Paz Gutierrez',
            'email' => 'mariapaz@example.com',
            'password' => Hash::make('password123'),
        ]);

        Usuario::create([
            'rol_id' => $clienteRol->id,
            'nombre' => 'Jose Fernandez',
            'email' => 'jose@example.com',
            'password' => Hash::make('password123'),
        ]);
    }
}
