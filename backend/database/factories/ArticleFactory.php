<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Source;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'project_id' => null,
            'source_id' => Source::factory(),
            'title' => fake()->sentence(8),
            'url' => fake()->unique()->url(),
            'content' => fake()->paragraphs(3, true),
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
