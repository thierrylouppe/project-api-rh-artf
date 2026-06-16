<?php

namespace App\Http\Requests\Classegrillesalariale;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'categorie_id' => ['required', 'integer', 'exists:categories,id', 'unique:classegrillesalariales,categorie_id'],
            'grade_id'     => ['required', 'integer', 'exists:grades,id',     'unique:classegrillesalariales,grade_id'],
            'coefficient'  => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }
}
