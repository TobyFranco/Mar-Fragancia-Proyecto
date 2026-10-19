<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Tamano;
use Illuminate\Database\Seeder;

class ProductoPresentacionSeeder extends Seeder
{
    public function run(): void
    {
        $tamanos = Tamano::pluck('id', 'nombre');

        foreach (ProductoSeeder::CATALOGO as $datos) {
            $producto = Producto::where('nombre', $datos['nombre'])->firstOrFail();
            foreach (['5 ml' => 1, '10 ml' => 1.7] as $tamano => $factor) {
                ProductoPresentacion::create([
                    'producto_id' => $producto->id,
                    'tamano_id' => $tamanos[$tamano],
                    'precio' => (int) round($datos['precio_5ml'] * $factor),
                    'stock' => $datos['nombre'] === 'Sauvage' && $tamano === '10 ml' ? 0 : 12,
                    'activo' => true,
                ]);
            }
        }
    }
}
