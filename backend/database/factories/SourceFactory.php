<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Kompas', 'Detik', 'Tempo', 'Antara']),
            'base_url' => fake()->url(),
            'type' => 'news',
            'is_active' => true,
        ];
    }
}
