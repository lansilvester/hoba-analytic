<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ArticleService
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['source', 'project', 'analysis'])
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('source_id'), fn ($q) => $q->where('source_id', $request->integer('source_id')))
            ->when($request->filled('sentiment'), fn ($q) => $q->whereHas('analysis', fn ($a) => $a->where('sentiment', $request->string('sentiment'))))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('published_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('published_at', '<=', $request->date('to')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->toString();
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'ilike', "%{$search}%")
                        ->orWhere('content', 'ilike', "%{$search}%");
                });
            });

        $sort = $request->string('sort', '-published_at')->toString();
        [$column, $direction] = $this->parseSort($sort);

        return $query->orderBy($column, $direction)
            ->paginate($request->integer('per_page', 15));
    }

    public function trends(Request $request): array
    {
        $interval = $request->string('interval', 'day')->toString();
        $from = $request->filled('from')
            ? Carbon::parse($request->string('from')->toString())
            : Carbon::today()->subDays(6);
        $to = $request->filled('to')
            ? Carbon::parse($request->string('to')->toString())
            : Carbon::today();

        $articles = Article::query()
            ->with('analysis')
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->whereBetween('published_at', [$from->startOfDay(), $to->endOfDay()])
            ->get();

        $labels = $this->buildLabels($from, $to, $interval);
        $series = [
            'positive' => array_fill(0, count($labels), 0),
            'negative' => array_fill(0, count($labels), 0),
            'neutral' => array_fill(0, count($labels), 0),
        ];

        foreach ($articles as $article) {
            $sentiment = $article->analysis?->sentiment;
            if (! $sentiment || ! isset($series[$sentiment])) {
                continue;
            }
            $index = $this->bucketIndex($article->published_at, $from, $interval, $labels);
            if ($index !== null) {
                $series[$sentiment][$index]++;
            }
        }

        $result = ['labels' => $labels];
        foreach ($series as $name => $data) {
            $result['series'][] = ['name' => $name, 'data' => $data];
        }

        return $result;
    }

    protected function parseSort(string $sort): array
    {
        $direction = 'asc';
        $column = $sort;

        if (str_starts_with($sort, '-')) {
            $direction = 'desc';
            $column = substr($sort, 1);
        }

        if (! in_array($column, ['published_at', 'title', 'id'], true)) {
            $column = 'published_at';
            $direction = 'desc';
        }

        return [$column, $direction];
    }

    protected function buildLabels(Carbon $from, Carbon $to, string $interval): array
    {
        $labels = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $labels[] = $this->formatLabel($cursor, $interval);
            $cursor = match ($interval) {
                'week' => $cursor->addWeek(),
                'month' => $cursor->addMonth(),
                default => $cursor->addDay(),
            };
        }

        return $labels;
    }

    protected function bucketIndex(?Carbon $publishedAt, Carbon $from, string $interval, array $labels): ?int
    {
        if (! $publishedAt) {
            return null;
        }

        $label = $this->formatLabel($publishedAt->copy(), $interval);
        $index = array_search($label, $labels, true);

        return $index === false ? null : $index;
    }

    protected function formatLabel(Carbon $date, string $interval): string
    {
        return match ($interval) {
            'week' => $date->startOfWeek()->toDateString(),
            'month' => $date->format('Y-m'),
            default => $date->toDateString(),
        };
    }
}
