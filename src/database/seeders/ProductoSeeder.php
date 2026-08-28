<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Marca;
use App\Models\Categoria;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            ['nombre' => 'Sauvage', 'marca' => 'Dior', 'categoria' => 'Fresca', 'descripcion' => 'Fragancia fresca y amaderada'],
            ['nombre' => 'Bleu de Chanel', 'marca' => 'Chanel', 'categoria' => 'Amaderada', 'descripcion' => 'Aromática y elegante'],
            ['nombre' => 'Eros', 'marca' => 'Versace', 'categoria' => 'Dulce', 'descripcion' => 'Fragancia dulce y vibrante'],
            ['nombre' => '1 Million', 'marca' => 'Paco Rabanne', 'categoria' => 'Oriental', 'descripcion' => 'Especiada y seductora'],
            ['nombre' => 'Good Girl', 'marca' => 'Carolina Herrera', 'categoria' => 'Floral', 'descripcion' => 'Floral con toques de cacao'],
            ['nombre' => 'Black Opium', 'marca' => 'Yves Saint Laurent', 'categoria' => 'Dulce', 'descripcion' => 'Café y vainilla'],
        ];

        foreach ($productos as $p) {
            Producto::create([
                'marca_id' => Marca::where('nombre', $p['marca'])->first()->id,
                'categoria_id' => Categoria::where('nombre', $p['categoria'])->first()->id,
                'nombre' => $p['nombre'],
                'descripcion' => $p['descripcion'],
                'imagen' => null,
                'activo' => true,
            ]);
        }
    }
}