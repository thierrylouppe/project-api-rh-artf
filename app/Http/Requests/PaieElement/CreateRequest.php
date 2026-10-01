<?php

namespace App\Http\Requests\PaieElement;

use App\Enums\ModeCalculPaieElement;
use App\Enums\NaturePaieElement;
use App\Enums\PeriodicitePaieElement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:paie_elements,code'],
            'libelle' => ['required', 'string', 'max:255'],
            'nature' => ['required', 'string', Rule::enum(NaturePaieElement::class)],
            'periodicite' => ['required', 'string', Rule::enum(PeriodicitePaieElement::class)],
            'mode_calcul' => ['required', 'string', Rule::enum(ModeCalculPaieElement::class)],
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

    public function messages(): array
    {
        return [
            'code.regex' => 'Le code doit être en snake_case (lettres minuscules, chiffres, underscores).',
        ];
    }
}
