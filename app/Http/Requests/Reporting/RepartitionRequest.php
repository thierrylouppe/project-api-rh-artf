<?php

namespace App\Http\Requests\Reporting;

use App\Enums\AxeRepartitionReporting;
use Illuminate\Validation\Rule;

class RepartitionRequest extends FilterRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'axe' => ['required', 'string', Rule::in(AxeRepartitionReporting::values())],
        ]);
    }

    public function messages(): array
    {
        return [
            'axe.required' => 'Indiquez l\'axe de répartition.',
            'axe.in' => 'Axe inconnu. Valeurs : '.implode(', ', AxeRepartitionReporting::values()).'.',
        ];
    }
}
