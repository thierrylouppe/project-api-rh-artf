<?php

namespace App\Http\Resources;

use App\Models\Echelon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiplomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'nom'                      => $this->nom,
            'sigle'                    => $this->sigle ?? null,
            'description'              => $this->description,
            'classegrillesalariale_id' => $this->classegrillesalariale_id,
            'classe_grille'            => $this->whenLoaded('classeGrille', function () {
                return $this->classeGrillePayload();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Échelon de départ (numéro 1), même règle que AgentService — jamais de fonction.
     *
     * @return array<string, mixed>|null
     */
    private function classeGrillePayload(): ?array
    {
        if (! $this->classeGrille) {
            return null;
        }

        $echelon = once(fn () => Echelon::query()->where('numero', 1)->first());

        return [
            'id'           => $this->classeGrille->id,
            'coefficient'  => $this->classeGrille->coefficient,
            'categorie'    => $this->classeGrille->categorie?->nom,
            'categorie_id' => $this->classeGrille->categorie_id,
            'grade'        => $this->classeGrille->grade?->nom,
            'grade_id'     => $this->classeGrille->grade_id,
            'echelon'      => $echelon?->nom,
            'echelon_id'   => $echelon?->id,
        ];
    }
}
