<?php

namespace Tests\Feature;

use App\Jobs\GenerateReport;
use App\Models\Project;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_report(): void
    {
        Queue::fake();
        $project = Project::factory()->create();

        $response = $this->actingAs($this->user($project->tenant_id, 'admin'), 'sanctum')
            ->postJson('/api/reports/generate', [
                'project_id' => $project->id,
                'title' => 'Laporan Bulanan - Agustus 2026',
                'from' => '2026-08-01',
                'to' => '2026-08-31',
                'format' => 'pdf',
            ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('reports', ['title' => 'Laporan Bulanan - Agustus 2026', 'status' => 'pending']);

        Queue::assertPushed(GenerateReport::class);
    }

    public function test_can_list_reports(): void
    {
        $project = Project::factory()->create();
        Report::factory()->count(2)->create([
            'tenant_id' => $project->tenant_id,
            'project_id' => $project->id,
        ]);

        $this->actingAs($this->user($project->tenant_id, 'viewer'), 'sanctum')
            ->getJson('/api/reports')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_report_detail(): void
    {
        $project = Project::factory()->create();
        $report = Report::factory()->create([
            'tenant_id' => $project->tenant_id,
            'project_id' => $project->id,
            'status' => 'ready',
        ]);

        $this->actingAs($this->user($project->tenant_id, 'viewer'), 'sanctum')
            ->getJson("/api/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.download_url', "/api/reports/{$report->id}/download");
    }

    public function test_cannot_download_pending_report(): void
    {
        $project = Project::factory()->create();
        $report = Report::factory()->create([
            'tenant_id' => $project->tenant_id,
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user($project->tenant_id, 'viewer'), 'sanctum')
            ->getJson("/api/reports/{$report->id}/download")
            ->assertStatus(409);
    }

    public function test_viewer_cannot_generate_report(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->user($project->tenant_id, 'viewer'), 'sanctum')
            ->postJson('/api/reports/generate', [
                'project_id' => $project->id,
                'title' => 'Dilarang',
            ])
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
