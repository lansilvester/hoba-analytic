<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Project;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();
        $role = Role::factory()->create(['name' => 'viewer']);

        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_tenant_cannot_read_another_tenants_project(): void
    {
        $project = Project::factory()->create(['tenant_id' => $this->tenantA->id]);

        $this->actingAs($this->userB, 'sanctum')
            ->getJson("/api/projects/{$project->id}")
            ->assertStatus(404);
    }

    public function test_tenant_cannot_see_another_tenants_projects_in_list(): void
    {
        Project::factory()->count(3)->create(['tenant_id' => $this->tenantA->id]);

        $this->actingAs($this->userB, 'sanctum')
            ->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_tenant_cannot_read_another_tenants_article(): void
    {
        $article = Article::factory()->create(['tenant_id' => $this->tenantA->id]);

        $this->actingAs($this->userB, 'sanctum')
            ->getJson("/api/articles/{$article->id}")
            ->assertStatus(404);
    }

    public function test_tenant_cannot_update_another_tenants_project(): void
    {
        $project = Project::factory()->create(['tenant_id' => $this->tenantA->id]);
        $role = Role::factory()->create(['name' => 'editor']);

        $this->actingAs(User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'role_id' => $role->id,
        ]), 'sanctum')
            ->putJson("/api/projects/{$project->id}", ['name' => 'Hacked'])
            ->assertStatus(404);
    }

    public function test_owner_can_read_own_project(): void
    {
        $project = Project::factory()->create(['tenant_id' => $this->tenantB->id]);

        $this->actingAs($this->userB, 'sanctum')
            ->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }
}
