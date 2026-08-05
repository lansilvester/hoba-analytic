<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrawlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.crawler.token' => 'test-crawler-token']);
    }

    private function headers(): array
    {
        return ['X-Crawler-Token' => 'test-crawler-token'];
    }

    public function test_requires_valid_crawler_token(): void
    {
        $this->getJson('/api/crawler/keywords?project_id=1', ['X-Crawler-Token' => 'wrong'])
            ->assertStatus(401);
    }

    public function test_returns_active_project_keywords(): void
    {
        $project = Project::factory()->create();

        Keyword::factory()->create(['project_id' => $project->id, 'keyword' => 'Pixel Joy', 'is_active' => true]);
        Keyword::factory()->create(['project_id' => $project->id, 'keyword' => 'media monitoring', 'is_active' => true]);
        Keyword::factory()->create(['project_id' => $project->id, 'keyword' => 'reputasi', 'is_active' => false]);

        $this->getJson('/api/crawler/keywords?project_id='.$project->id, $this->headers())
            ->assertStatus(200)
            ->assertJson(['data' => ['Pixel Joy', 'media monitoring']]);
    }

    public function test_returns_404_for_missing_project(): void
    {
        $this->getJson('/api/crawler/keywords?project_id=999', $this->headers())
            ->assertStatus(404);
    }
}
