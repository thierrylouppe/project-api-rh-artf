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
            'bureau_id'     => $this->bureau_id,
            'vue_personnel' => $this->vuePersonnel(),
            'bureau'        => $this->when(
                $this->relationLoaded('bureau'),
                fn () => $this->bureau === null ? null : $this->blocBureau()
            ),
            'structure' => $this->when(
                $this->relationLoaded('bureau'),
                fn () => $this->blocStructure()
            ),
            'fonction' => $this->when(
                $this->relationLoaded('agent'),
                fn () => $this->blocFonction()
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

    /** @return array{id: int, nom: string, sigle: ?string, service: ?array} */
    private function blocBureau(): array
    {
        $bureau = $this->bureau;
        $service = $bureau->relationLoaded('service') ? $bureau->service : null;

        return [
            'id'      => $bureau->id,
            'nom'     => $bureau->nom,
            'sigle'   => $bureau->sigle,
            'service' => $service === null ? null : [
                'id'        => $service->id,
                'nom'       => $service->nom,
                'sigle'     => $service->sigle,
                'direction' => ($service->relationLoaded('direction') && $service->direction !== null) ? [
                    'id'    => $service->direction->id,
                    'nom'   => $service->direction->nom,
                    'sigle' => $service->direction->sigle,
                ] : null,
            ],
        ];
    }

    /**
     * Structure du périmètre réel (direction, service ou bureau), avec son nom complet.
     *
     * @return array{id: int, nom: string, sigle: ?string, type: string}|null
     */
    private function blocStructure(): ?array
    {
        if ($this->bureau === null || $this->vuePersonnel() === 'globale') {
            return null;
        }

        $bureau = $this->bureau;
        $service = $bureau->relationLoaded('service') ? $bureau->service : null;
        $direction = $service?->relationLoaded('direction') ? $service->direction : null;

        if ($this->vuePersonnel() === 'direction' && $direction !== null) {
            return [
                'id'    => $direction->id,
                'nom'   => $direction->nom,
                'sigle' => $direction->sigle,
                'type'  => 'direction',
            ];
        }

        if ($this->vuePersonnel() === 'service' && $service !== null) {
            return [
                'id'    => $service->id,
                'nom'   => $service->nom,
                'sigle' => $service->sigle,
                'type'  => 'service',
            ];
        }

        return [
            'id'    => $bureau->id,
            'nom'   => $bureau->nom,
            'sigle' => $bureau->sigle,
            'type'  => 'bureau',
        ];
    }

    /** @return array{id: int, nom: string, sigle: ?string}|null */
    private function blocFonction(): ?array
    {
        $fonction = $this->agent?->fonction;

        if ($fonction === null) {
            return null;
        }

        return [
            'id'    => $fonction->id,
            'nom'   => $fonction->nom,
            'sigle' => $fonction->sigle,
        ];
    }
}
