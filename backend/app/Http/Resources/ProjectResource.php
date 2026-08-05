<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $withKeywords = $this->whenLoaded('keywords');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'keywords' => $withKeywords->map(fn ($keyword) => $keyword->keyword),
            'sources' => SourceResource::collection($this->whenLoaded('sources')),
            'article_count' => $this->article_count ?? $this->articles()->count(),
            'created_at' => $this->created_at,
        ];
    }
}
