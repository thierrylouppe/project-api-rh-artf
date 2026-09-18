<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'email'     => $this->email,
            'agent_id'  => $this->agent_id,
            'is_active' => $this->is_active,

            // Vague F — rattachement bureau (null = pas de cloisonnement)
            'bureau_id'          => $this->bureau_id,
            'vue_personnel' => $this->vuePersonnel(),
            'bureau'    => $this->when(
                $this->relationLoaded('bureau') && $this->bureau !== null,
                fn () => [
                    'id'    => $this->bureau->id,
                    'nom'   => $this->bureau->nom,
                    'sigle' => $this->bureau->sigle,
                ]
            ),

            'roles' => $this->when(
                $this->relationLoaded('roles'),
                fn () => RoleResource::collection($this->roles)
            ),
            'permissions' => $this->when(
                $this->relationLoaded('permissions'),
                fn () => PermissionResource::collection($this->permissions)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
