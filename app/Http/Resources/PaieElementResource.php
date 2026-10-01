<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaieElementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'nature' => $this->nature?->value,
            'nature_label' => $this->nature?->label(),
            'sens' => $this->sens?->value,
            'sens_label' => $this->sens?->label(),
            'periodicite' => $this->periodicite?->value,
            'periodicite_label' => $this->periodicite?->label(),
            'mode_calcul' => $this->mode_calcul?->value,
            'mode_calcul_label' => $this->mode_calcul?->label(),
            'montant_defaut' => $this->montant_defaut !== null ? (float) $this->montant_defaut : null,
            'taux_defaut' => $this->taux_defaut !== null ? (float) $this->taux_defaut : null,
            'article_ccn' => $this->article_ccn,
            'fonction_sigles' => $this->fonction_sigles,
            'mois_declenchement' => $this->mois_declenchement,
            'actif' => (bool) $this->actif,
            'systeme' => (bool) $this->systeme,
            'a_parametrer' => $this->aParametrer(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
