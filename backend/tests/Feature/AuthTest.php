<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Rina',
            'email' => 'rina@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role', 'tenant_id']]);

        $this->assertDatabaseHas('users', ['email' => 'rina@example.com']);
    }

    public function test_user_can_login(): void
    {
        $tenant = Tenant::factory()->create();
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.tenant_id', $tenant->id)
            ->assertJsonStructure(['token']);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_their_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $role = Role::factory()->create(['name' => 'viewer']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/user');

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/auth/user')->assertStatus(401);
    }
}
