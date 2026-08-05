<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Report;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'file_path' => null,
            'status' => 'pending',
        ];
    }
}
