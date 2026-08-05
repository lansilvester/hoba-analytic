<?php

namespace Database\Factories;

use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Keyword>
 */
class KeywordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'keyword' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }
}
