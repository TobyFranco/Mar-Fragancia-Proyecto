<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ProductoSeeder::class,
            ProductoPresentacionSeeder::class,
        ]);
    }
}