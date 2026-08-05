<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\Article;
use App\Models\Project;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_articles(): void
    {
        $tenant = Tenant::factory()->create();
        Article::factory()->count(3)->create(['tenant_id' => $tenant->id]);

        $this->actingAs($this->user($tenant->id, 'viewer'), 'sanctum')
            ->getJson('/api/articles')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_can_filter_articles_by_sentiment(): void
    {
        $tenant = Tenant::factory()->create();
        $positive = Article::factory()->create(['tenant_id' => $tenant->id]);
        $negative = Article::factory()->create(['tenant_id' => $tenant->id]);
        Analysis::factory()->create(['article_id' => $positive->id, 'sentiment' => 'positive']);
        Analysis::factory()->create(['article_id' => $negative->id, 'sentiment' => 'negative']);

        $this->actingAs($this->user($tenant->id, 'viewer'), 'sanctum')
            ->getJson('/api/articles?sentiment=positive')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $positive->id);
    }

    public function test_can_filter_articles_by_project_and_search(): void
    {
        $tenant = Tenant::factory()->create();
        $project = Project::factory()->create(['tenant_id' => $tenant->id]);
        Article::factory()->create([
            'tenant_id' => $tenant->id,
            'project_id' => $project->id,
            'title' => 'Kabar Ekonomi Indonesia',
        ]);
        Article::factory()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Berita Olahraga',
        ]);

        $this->actingAs($this->user($tenant->id, 'viewer'), 'sanctum')
            ->getJson('/api/articles?project_id='.$project->id.'&search=ekonomi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Kabar Ekonomi Indonesia');
    }

    public function test_can_get_article_detail_with_analysis(): void
    {
        $article = Article::factory()->create();
        Analysis::factory()->create([
            'article_id' => $article->id,
            'sentiment' => 'positive',
            'confidence' => 0.92,
        ]);

        $this->actingAs($this->user($article->tenant_id, 'viewer'), 'sanctum')
            ->getJson("/api/articles/{$article->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $article->id)
            ->assertJsonPath('data.sentiment.label', 'positive')
            ->assertJsonPath('data.sentiment.confidence', 0.92)
            ->assertJsonStructure(['data' => ['title', 'source', 'url', 'content', 'topic', 'entities']]);
    }

    public function test_can_get_trends(): void
    {
        $tenant = Tenant::factory()->create();
        $positive = Article::factory()->create(['tenant_id' => $tenant->id, 'published_at' => now()]);
        Analysis::factory()->create(['article_id' => $positive->id, 'sentiment' => 'positive']);

        $this->actingAs($this->user($tenant->id, 'viewer'), 'sanctum')
            ->getJson('/api/articles/trends')
            ->assertOk()
            ->assertJsonStructure(['data' => ['labels', 'series']])
            ->assertJsonPath('data.series.0.name', 'positive');
    }

    protected function user(int $tenantId, string $role): User
    {
        return User::factory()->create([
            'tenant_id' => $tenantId,
            'role_id' => Role::factory()->create(['name' => $role])->id,
        ]);
    }
}
