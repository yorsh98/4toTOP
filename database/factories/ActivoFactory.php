<?php

namespace Database\Factories;

use App\Models\Activo;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activo>
 */
class ActivoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->words(3, true),
            'sector_id' => Sector::factory(),
            'codigo_inventario' => $this->faker->unique()->numerify('18000#####'),
            'numero_serie' => $this->faker->bothify('SN-#####'),
            'estado' => $this->faker->randomElement(['Operativo', 'En Reparación', 'Baja']),
            'observaciones' => $this->faker->sentence(),
        ];
    }
}
