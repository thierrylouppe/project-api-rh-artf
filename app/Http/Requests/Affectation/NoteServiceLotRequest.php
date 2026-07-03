<?php

namespace App\Http\Requests\Affectation;

use Illuminate\Foundation\Http\FormRequest;

class NoteServiceLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'affectation_ids'   => ['required', 'array', 'min:1', 'max:50'],
            'affectation_ids.*' => ['required', 'integer', 'exists:affectations,id', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'affectation_ids.required'  => 'La liste des affectations est obligatoire.',
            'affectation_ids.min'        => 'Au moins une affectation doit être sélectionnée.',
            'affectation_ids.max'        => '50 notes de service maximum par lot.',
            'affectation_ids.*.exists'   => 'L\'affectation :input n\'existe pas.',
            'affectation_ids.*.distinct' => 'Une même affectation ne peut pas apparaître deux fois.',
        ];
    }
}
