<?php

namespace App\Jobs;

use App\Models\Analysis;
use App\Models\Article;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessArticle implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(public Article $article) {}

    public function handle(): void
    {
        $url = config('services.python_api.url');

        if (! $url) {
            Log::warning('Python API URL not configured, skipping article analysis', [
                'article_id' => $this->article->id,
            ]);

            return;
        }

        $response = Http::baseUrl($url)
            ->timeout(30)
            ->post('/analyze', [
                'article_id' => $this->article->id,
                'title' => $this->article->title,
                'content' => $this->article->content ?? '',
                'source' => $this->article->source?->name,
                'published_at' => $this->article->published_at?->toIso8601String(),
            ]);

        $response->throw();

        $payload = $response->json();
        $sentiment = $payload['sentiment']['label'] ?? null;

        if ($sentiment) {
            Analysis::updateOrCreate(
                ['article_id' => $this->article->id],
                [
                    'sentiment' => $sentiment,
                    'confidence' => $payload['sentiment']['confidence'] ?? null,
                    'topic' => $payload['topic']['label'] ?? null,
                    'entities' => $payload['entities'] ?? [],
                    'analyzed_at' => now(),
                ],
            );
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Article analysis failed', [
            'article_id' => $this->article->id,
            'error' => $e->getMessage(),
        ]);
    }
}
