<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tamano;

class TamanoSeeder extends Seeder
{
    public function run(): void
    {
        Tamano::create(['nombre' => '5 ml', 'descripcion' => 'Decant 5 mililitros']);
        Tamano::create(['nombre' => '10 ml', 'descripcion' => 'Decant 10 mililitros']);
    }
}