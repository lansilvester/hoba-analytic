<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\Source;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->admin = $this->user('admin');
        $this->viewer = $this->user('viewer');
    }

    public function test_admin_can_create_project_with_keywords_and_sources(): void
    {
        $sources = Source::factory()->count(2)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/projects', [
                'name' => 'Brand Monitoring 2026',
                'description' => 'Pantau brand utama',
                'keywords' => ['Pixel Joy', 'media monitoring'],
                'source_ids' => $sources->pluck('id')->all(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Brand Monitoring 2026')
            ->assertJsonPath('data.keywords', ['Pixel Joy', 'media monitoring'])
            ->assertJsonCount(2, 'data.sources');

        $this->assertDatabaseHas('projects', ['name' => 'Brand Monitoring 2026']);
        $this->assertDatabaseCount('keywords', 2);
    }

    public function test_create_project_requires_keyword(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/projects', [
                'name' => 'Tanpa Keyword',
                'keywords' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['keywords']);
    }

    public function test_admin_can_list_projects(): void
    {
        Project::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_update_project(): void
    {
        $project = Project::factory()->create(['tenant_id' => $this->tenant->id]);
        Project::factory()->create(['tenant_id' => $this->tenant->id]); // avoid keyword collision

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Updated',
                'keywords' => ['baru'],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.keywords', ['baru']);
    }

    public function test_admin_can_delete_project(): void
    {
        $project = Project::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Project deleted successfully');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_viewer_cannot_create_project(): void
    {
        $this->actingAs($this->viewer, 'sanctum')
            ->postJson('/api/projects', [
                'name' => 'Dilarang',
                'keywords' => ['x'],
            ])
            ->assertStatus(403);
    }

    public function test_editor_cannot_delete_project(): void
    {
        $editor = $this->user('editor');
        $project = Project::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($editor, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}")
            ->assertStatus(403);
    }

    protected function user(string $role): User
    {
        return User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_id' => Role::factory()->create(['name' => $role])->id,
        ]);
    }
}
