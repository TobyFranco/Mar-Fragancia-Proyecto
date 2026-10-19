<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Tamano;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductoPresentacion>
 */
class ProductoPresentacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'tamano_id' => Tamano::factory(),
            'precio' => 30000,
            'stock' => 10,
            'activo' => true,
        ];
    }
}
