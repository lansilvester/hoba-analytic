<?php

namespace Database\Factories;

use App\Models\Analysis;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Analysis>
 */
class AnalysisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'sentiment' => fake()->randomElement(['positive', 'negative', 'neutral']),
            'confidence' => fake()->randomFloat(4, 0.7, 0.99),
            'topic' => fake()->randomElement(['ekonomi', 'politik', 'teknologi']),
            'entities' => [
                ['type' => 'GPE', 'text' => 'Indonesia'],
            ],
            'analyzed_at' => now(),
        ];
    }
}
