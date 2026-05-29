<?php

namespace Database\Factories;

use App\Models\TtyppRegistro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TtyppRegistro>
 */
class TtyppRegistroFactory extends Factory
{
    protected $model = TtyppRegistro::class;

    public function definition(): array
    {
        return [
            'rit' => fake()->optional()->bothify('####-##'),
            'tipo_audiencia' => fake()->optional()->randomElement(['rpa', 'nna', 'vif']),
            'numero_dias' => fake()->optional()->randomElement(['1d', '2d', '10d', '60d', '1/2d']),
            'cantidad' => fake()->optional()->randomElement(['4 MP', '7 MP + 1 D', '12 Zoom + 1 Stud.']),
            'modalidad' => fake()->optional()->randomElement(['Zoom', 'Presencial', 'Hibrida']),
            'encargada_ti_mp' => fake()->optional()->firstName(),
            'encargada_ti_d' => fake()->optional()->firstName(),
            'encargada_zoom' => fake()->optional()->firstName(),
            'ruc' => fake()->optional()->numerify('123123132-#'),
            'acta' => fake()->optional()->userName(),
            'sala' => fake()->optional()->numerify('###'),
            'jueces' => fake()->optional()->name(),
            'fecha_asignacion' => fake()->optional()->date(),
        ];
    }
}
