<?php

namespace Database\Factories;

use App\Models\Tamano;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tamano>
 */
class TamanoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => '5 ml',
            'descripcion' => 'Decant de prueba',
        ];
    }
}
