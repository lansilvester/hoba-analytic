<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $analysis = $this->whenLoaded('analysis');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'source' => SourceResource::make($this->whenLoaded('source')),
            'sentiment' => $analysis?->sentiment,
            'confidence' => isset($analysis->confidence) ? (float) $analysis->confidence : null,
            'published_at' => $this->published_at,
            'url' => $this->url,
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ]),
        ];
    }
}
