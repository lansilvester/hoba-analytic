<?php

namespace Tests\Feature;

use App\Jobs\ProcessArticle;
use App\Models\Project;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IngestTest extends TestCase
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
        $this->postJson('/api/ingest/articles', [], ['X-Crawler-Token' => 'wrong'])
            ->assertStatus(401);

        $this->postJson('/api/ingest/articles', [], [])
            ->assertStatus(401);
    }

    public function test_can_ingest_articles(): void
    {
        Queue::fake();
        $project = Project::factory()->create();

        $response = $this->postJson('/api/ingest/articles', [
            'project_id' => $project->id,
            'articles' => [
                [
                    'source' => 'Kompas',
                    'title' => 'Pertumbuhan Ekonomi Membaik',
                    'url' => 'https://www.kompas.com/ekonomi/1',
                    'content' => 'Ekonomi membaik, pasar saham naik.',
                    'published_at' => '2026-08-05T09:00:00Z',
                ],
                [
                    'source' => 'Detik',
                    'title' => 'Rupiah Anjlok',
                    'url' => 'https://finance.detik.com/1',
                    'content' => 'Nilai tukar rupiah melemah.',
                    'published_at' => '2026-08-05T10:00:00Z',
                ],
            ],
        ], $this->headers());

        $response->assertStatus(201)
            ->assertJsonPath('data.created', 2)
            ->assertJsonPath('data.skipped', 0);

        $this->assertDatabaseHas('articles', ['url' => 'https://www.kompas.com/ekonomi/1']);
        $this->assertDatabaseHas('articles', ['url' => 'https://finance.detik.com/1']);
        $this->assertDatabaseHas('sources', ['name' => 'Kompas']);
        Queue::assertPushed(ProcessArticle::class, 2);
    }

    public function test_ingest_deduplicates_existing_url(): void
    {
        Queue::fake();
        $project = Project::factory()->create();
        $source = Source::factory()->create(['name' => 'Kompas']);

        $project->articles()->create([
            'tenant_id' => $project->tenant_id,
            'source_id' => $source->id,
            'title' => 'Lama',
            'url' => 'https://www.kompas.com/ekonomi/1',
            'content' => 'Lama',
        ]);

        $this->postJson('/api/ingest/articles', [
            'project_id' => $project->id,
            'articles' => [
                [
                    'source' => 'Kompas',
                    'title' => 'Baru',
                    'url' => 'https://www.kompas.com/ekonomi/1',
                ],
                [
                    'source' => 'Tempo',
                    'title' => 'Beda',
                    'url' => 'https://www.tempo.co/2',
                ],
            ],
        ], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.skipped', 1);

        Queue::assertPushed(ProcessArticle::class, 1);
    }

    public function test_ingest_rejects_invalid_payload(): void
    {
        $project = Project::factory()->create();

        $this->postJson('/api/ingest/articles', [
            'project_id' => $project->id,
            'articles' => [
                ['source' => 'Kompas', 'title' => 'Tanpa URL'],
            ],
        ], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('articles.0.url');
    }

    public function test_ingest_saves_social_source_type(): void
    {
        Queue::fake();
        $project = Project::factory()->create();

        $this->postJson('/api/ingest/articles', [
            'project_id' => $project->id,
            'articles' => [
                [
                    'source' => 'X (Twitter)',
                    'title' => 'Tweet viral',
                    'url' => 'https://x.com/user/status/1',
                    'content' => 'Isi tweet',
                    'type' => 'social',
                    'published_at' => '2026-08-05T09:00:00Z',
                ],
            ],
        ], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('data.created', 1);

        $this->assertDatabaseHas('sources', ['name' => 'X (Twitter)', 'type' => 'social']);
    }

    public function test_ingest_rejects_invalid_source_type(): void
    {
        $project = Project::factory()->create();

        $this->postJson('/api/ingest/articles', [
            'project_id' => $project->id,
            'articles' => [
                [
                    'source' => 'X (Twitter)',
                    'title' => 'Tweet viral',
                    'url' => 'https://x.com/user/status/1',
                    'type' => 'blog',
                ],
            ],
        ], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('articles.0.type');
    }
}
