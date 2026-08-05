<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Keyword;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ProjectService
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Project::query()
            ->with(['keywords', 'sources'])
            ->withCount('articles as article_count')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));
    }

    public function create(array $data): Project
    {
        $project = Project::create([
            'tenant_id' => TenantContext::id(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncKeywords($project, $data['keywords'] ?? []);

        if (! empty($data['source_ids'])) {
            $project->sources()->sync($data['source_ids']);
        }

        return $project->load(['keywords', 'sources']);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update([
            'name' => $data['name'] ?? $project->name,
            'description' => array_key_exists('description', $data)
                ? $data['description']
                : $project->description,
        ]);

        if (array_key_exists('keywords', $data)) {
            $this->syncKeywords($project, $data['keywords']);
        }

        if (array_key_exists('source_ids', $data)) {
            $project->sources()->sync($data['source_ids']);
        }

        return $project->load(['keywords', 'sources']);
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    protected function syncKeywords(Project $project, array $keywords): void
    {
        $project->keywords()->delete();

        foreach ($keywords as $keyword) {
            Keyword::create([
                'project_id' => $project->id,
                'keyword' => $keyword,
            ]);
        }
    }
}
