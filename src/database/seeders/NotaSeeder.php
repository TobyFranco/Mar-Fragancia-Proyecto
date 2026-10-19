<?php

namespace Database\Seeders;

use App\Models\Nota;
use Illuminate\Database\Seeder;

class NotaSeeder extends Seeder
{
    public function run(): void
    {
        $notas = collect(ProductoSeeder::CATALOGO)->pluck('notas')->flatten()->unique();

        foreach ($notas as $nombre) {
            Nota::create(['nombre' => $nombre]);
        }
    }
}
