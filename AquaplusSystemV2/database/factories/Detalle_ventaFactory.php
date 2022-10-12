<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Detalle_venta>
 */
class Detalle_ventaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'precio_unitario' => $this->faker->numberBetween(6, 10),
            'cantidad_entregada' => $this->faker->numberBetween(20, 60),
            'cantidad_recibida' => $this->faker->numberBetween(20, 60),
            'venta_id' => $this->faker->numberBetween(1, 3000),
            'material_id' => $this->faker->numberBetween(1, 3),                       
        ];
    }
}
