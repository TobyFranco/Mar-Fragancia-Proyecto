<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marca;

class MarcaSeeder extends Seeder
{
    public function run(): void
    {
        $marcas = ['Dior', 'Chanel', 'Versace', 'Paco Rabanne', 'Carolina Herrera', 'Yves Saint Laurent'];

        foreach ($marcas as $marca) {
            Marca::create(['nombre' => $marca]);
        }
    }
}