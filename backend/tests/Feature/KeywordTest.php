<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_keywords_of_project(): void
    {
        $project = Project::factory()->create();
        Keyword::factory()->count(2)->create(['project_id' => $project->id]);

        $this->actingAs($this->user($project->tenant_id, 'viewer'), 'sanctum')
            ->getJson("/api/projects/{$project->id}/keywords")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'keyword', 'is_active', 'created_at']]]);
    }

    public function test_can_add_keyword_to_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user($project->tenant_id, 'editor'), 'sanctum')
            ->postJson("/api/projects/{$project->id}/keywords", [
                'keyword' => 'brand reputation',
                'is_active' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.keyword', 'brand reputation');

        $this->assertDatabaseHas('keywords', ['keyword' => 'brand reputation', 'project_id' => $project->id]);
    }

    public function test_can_update_keyword(): void
    {
        $keyword = Keyword::factory()->create();

        $this->actingAs($this->user($keyword->project->tenant_id, 'editor'), 'sanctum')
            ->putJson("/api/keywords/{$keyword->id}", [
                'keyword' => 'brand reputation & awareness',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.keyword', 'brand reputation & awareness')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_can_delete_keyword(): void
    {
        $keyword = Keyword::factory()->create();

        $this->actingAs($this->user($keyword->project->tenant_id, 'editor'), 'sanctum')
            ->deleteJson("/api/keywords/{$keyword->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Keyword deleted successfully');

        $this->assertDatabaseMissing('keywords', ['id' => $keyword->id]);
    }

    public function test_viewer_cannot_add_keyword(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user($project->tenant_id, 'viewer'), 'sanctum')
            ->postJson("/api/projects/{$project->id}/keywords", ['keyword' => 'x'])
            ->assertStatus(403);
    }

    protected function user(int $tenantId, string $role): User
    {
        return User::factory()->create([
            'tenant_id' => $tenantId,
            'role_id' => Role::factory()->create(['name' => $role])->id,
        ]);
    }
}
