<?php

namespace App\Http\Requests\RegleAcquisitionConge;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_conge_id'  => ['required', 'integer', 'exists:type_conges,id'],
            'jours_par_mois' => ['required', 'numeric', 'min:0'],
            'jours_max'      => ['nullable', 'integer', 'min:0'],
        ];
    }
}
