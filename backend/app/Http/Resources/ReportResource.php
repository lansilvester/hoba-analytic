<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ]),
            'status' => $this->status,
            'file_path' => $this->file_path,
            'download_url' => $this->when($this->status === 'ready', "/api/reports/{$this->id}/download"),
            'created_at' => $this->created_at,
        ];
    }
}
