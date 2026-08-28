<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;
use App\Models\Usuario;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = Usuario::whereHas('rol', function ($q) {
            $q->where('nombre', 'cliente');
        })->get();

        foreach ($usuarios as $usuario) {
            Cliente::create([
                'usuario_id' => $usuario->id,
                'telefono' => '09' . rand(80000000, 99999999),
                'direccion' => 'Asunción, Paraguay',
            ]);
        }
    }
}