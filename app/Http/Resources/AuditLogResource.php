<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'user_id' => $this->when(! $this->relationLoaded('user'), $this->user_id),
            'user' => new UserResource($this->whenLoaded('user')),
            'loggable_type' => $this->loggable_type,
            'loggable_id' => $this->loggable_id,
            'details' => $this->details,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at,
        ];
    }
}
