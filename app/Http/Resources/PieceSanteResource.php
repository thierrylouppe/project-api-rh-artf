<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PieceSanteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_piece' => $this->type_piece?->value,
            'type_piece_label' => $this->type_piece?->label(),
            'nom_original' => $this->nom_original,
            'mime_type' => $this->mime_type,
            'taille' => $this->taille,
            'uploaded_by' => $this->uploaded_by,
            'uploader' => $this->when(
                $this->relationLoaded('uploader'),
                fn () => $this->uploader ? [
                    'id' => $this->uploader->id,
                    'name' => $this->uploader->name,
                ] : null
            ),
            'created_at' => $this->created_at,
        ];
    }
}
