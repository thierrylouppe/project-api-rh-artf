<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : [];

        return [
            'id'         => $this->id,
            'type'       => class_basename($this->type),
            'domaine'    => $data['domaine'] ?? null,
            'action'     => $data['action'] ?? null,
            'message'    => $data['message'] ?? null,
            'data'       => $data,
            'lu'         => $this->read_at !== null,
            'read_at'    => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
