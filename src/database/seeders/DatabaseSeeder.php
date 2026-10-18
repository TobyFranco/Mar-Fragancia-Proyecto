<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            UsuarioSeeder::class,
            ClienteSeeder::class,
            MarcaSeeder::class,
            CategoriaSeeder::class,
            TamanoSeeder::class,
            NotaSeeder::class,
            ProductoSeeder::class,
            ProductoPresentacionSeeder::class,
        ]);
    }
}
