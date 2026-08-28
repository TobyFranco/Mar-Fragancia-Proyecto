<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = ['Amaderada', 'Fresca', 'Floral', 'Oriental', 'Cítrica', 'Dulce'];

        foreach ($categorias as $categoria) {
            Categoria::create(['nombre' => $categoria]);
        }
    }
}