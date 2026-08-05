<?php

namespace App\Http\Controllers;

use App\Http\Requests\IngestRequest;
use App\Jobs\ProcessArticle;
use App\Models\Article;
use App\Models\Project;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class IngestController extends Controller
{
    public function store(IngestRequest $request): JsonResponse
    {
        $project = Project::withoutGlobalScopes()->findOrFail($request->input('project_id'));

        $knownUrls = Article::withoutGlobalScopes()
            ->where('tenant_id', $project->tenant_id)
            ->pluck('url')
            ->flip();

        $created = 0;
        $skipped = 0;
        $articles = [];

        foreach ($request->input('articles') as $item) {
            if ($knownUrls->has($item['url'])) {
                $skipped++;

                continue;
            }

            $source = Source::firstOrCreate(
                ['name' => $item['source']],
                ['base_url' => Str::of($item['url'])->before('/'), 'type' => $item['type'] ?? 'news', 'is_active' => true],
            );

            $article = Article::withoutGlobalScopes()->create([
                'tenant_id' => $project->tenant_id,
                'project_id' => $project->id,
                'source_id' => $source->id,
                'title' => $item['title'],
                'url' => $item['url'],
                'content' => $item['content'] ?? null,
                'published_at' => $item['published_at'] ?? null,
            ]);

            $knownUrls->put($article->url, true);
            $created++;
            $articles[] = $article;
        }

        foreach ($articles as $article) {
            ProcessArticle::dispatch($article);
        }

        return response()->json([
            'data' => [
                'project_id' => $project->id,
                'created' => $created,
                'skipped' => $skipped,
            ],
        ], 201);
    }
}
