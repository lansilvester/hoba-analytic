<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role?->name,
            'role_id' => $this->role_id,
            'tenant_id' => $this->tenant_id,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant?->id,
                'name' => $this->tenant?->name,
            ]),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
