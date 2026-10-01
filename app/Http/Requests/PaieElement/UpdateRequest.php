<?php

namespace App\Http\Requests\PaieElement;

use App\Enums\ModeCalculPaieElement;
use App\Enums\NaturePaieElement;
use App\Enums\PeriodicitePaieElement;
use Illuminate\Validation\Rule;

class UpdateRequest extends CreateRequest
{
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('paie_elements', 'code')->ignore($this->route('id'))],
            'libelle' => ['sometimes', 'string', 'max:255'],
            'nature' => ['sometimes', 'string', Rule::enum(NaturePaieElement::class)],
            'periodicite' => ['sometimes', 'string', Rule::enum(PeriodicitePaieElement::class)],
            'mode_calcul' => ['sometimes', 'string', Rule::enum(ModeCalculPaieElement::class)],
            'montant_defaut' => ['nullable', 'numeric', 'min:0'],
            'taux_defaut' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'article_ccn' => ['nullable', 'string', 'max:20'],
            'fonction_sigles' => ['nullable', 'array'],
            'fonction_sigles.*' => ['string', 'max:10'],
            'mois_declenchement' => ['nullable', 'array'],
            'mois_declenchement.*' => ['integer', 'min:1', 'max:12'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
