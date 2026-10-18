<?php

namespace Database\Seeders;

use App\Models\Marca;
use Illuminate\Database\Seeder;

class MarcaSeeder extends Seeder
{
    public function run(): void
    {
        $destacadas = ['Chanel', 'Dior', 'Tom Ford', 'Creed', 'Guerlain', 'Hermès', 'Yves Saint Laurent', 'Maison Francis Kurkdjian'];
        $otras = ['Carolina Herrera', 'Versace', 'Paco Rabanne', 'Parfums de Marly', 'Xerjoff', 'Lattafa', 'Armaf'];

        foreach ($destacadas as $orden => $nombre) {
            Marca::create(['nombre' => $nombre, 'destacada' => true, 'orden_menu' => $orden + 1]);
        }
        foreach ($otras as $nombre) {
            Marca::create(['nombre' => $nombre, 'destacada' => false]);
        }
    }
}
