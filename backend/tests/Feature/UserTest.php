<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?Tenant $tenant = null): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);

        return User::factory()->create([
            'tenant_id' => $tenant?->id,
            'role_id' => $roleModel->id,
        ]);
    }

    public function test_admin_can_list_users(): void
    {
        $tenant = Tenant::factory()->create();
        $this->makeUser('admin', $tenant);
        $this->makeUser('editor', $tenant);
        Sanctum::actingAs($this->makeUser('admin', $tenant));

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_non_admin_cannot_list_users(): void
    {
        Sanctum::actingAs($this->makeUser('editor'));

        $this->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $tenant = Tenant::factory()->create();
        $role = Role::factory()->create(['name' => 'viewer']);
        Sanctum::actingAs($this->makeUser('admin'));

        $this->postJson('/api/users', [
            'name' => 'Sari',
            'email' => 'sari@example.com',
            'password' => 'secret123',
            'role_id' => $role->id,
            'tenant_id' => $tenant->id,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.email', 'sari@example.com')
            ->assertJsonPath('data.role', 'viewer');

        $this->assertDatabaseHas('users', ['email' => 'sari@example.com', 'role_id' => $role->id]);
    }

    public function test_admin_can_update_user_role(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser('viewer');
        $role = Role::factory()->create(['name' => 'editor']);
        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $role->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.role', 'editor');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role_id' => $role->id]);
    }

    public function test_admin_cannot_demote_self(): void
    {
        $admin = $this->makeUser('admin');
        $role = Role::factory()->create(['name' => 'viewer']);
        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'role_id' => $role->id,
        ])
            ->assertStatus(422);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->makeUser('admin');
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/users/{$admin->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser('viewer');
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/users/{$user->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_roles_endpoint_returns_roles(): void
    {
        Role::factory()->create(['name' => 'admin']);
        Sanctum::actingAs($this->makeUser('admin'));

        $this->getJson('/api/roles')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }
}
