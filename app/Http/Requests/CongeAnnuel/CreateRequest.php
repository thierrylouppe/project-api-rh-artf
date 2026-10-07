<?php

namespace App\Http\Requests\CongeAnnuel;

use App\Enums\OrigineDemandeCongeAnnuel;
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
            'agent_id'   => ['required', 'integer', 'exists:agents,id'],
            'date_debut' => ['required', 'date'],
            'motif'      => ['nullable', 'string'],
            'origine'    => ['nullable', 'string', Rule::in(array_column(OrigineDemandeCongeAnnuel::cases(), 'value'))],
        ];
    }
}
