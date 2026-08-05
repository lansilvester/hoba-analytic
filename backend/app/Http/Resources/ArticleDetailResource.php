<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $analysis = $this->whenLoaded('analysis');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'source' => SourceResource::make($this->whenLoaded('source')),
            'url' => $this->url,
            'content' => $this->content,
            'published_at' => $this->published_at,
            'sentiment' => $analysis ? [
                'label' => $analysis->sentiment,
                'confidence' => (float) $analysis->confidence,
            ] : null,
            'topic' => $analysis?->topic,
            'entities' => $analysis?->entities ?? [],
            'created_at' => $this->created_at,
        ];
    }
}
