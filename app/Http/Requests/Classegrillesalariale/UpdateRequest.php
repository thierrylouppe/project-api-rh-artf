<?php

namespace App\Http\Requests\Classegrillesalariale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = (int) $this->route('classegrillesalariale');

        return [
            'categorie_id' => ['sometimes', 'integer', 'exists:categories,id', Rule::unique('classegrillesalariales', 'categorie_id')->ignore($id)],
            'grade_id'     => ['sometimes', 'integer', 'exists:grades,id',     Rule::unique('classegrillesalariales', 'grade_id')->ignore($id)],
            'coefficient'  => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }
}
