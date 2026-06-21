<?php

namespace App\Http\Requests\ActeAdministratif;

use App\Enums\TypeActeAdministratif;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GenererRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_acte' => ['required', new Enum(TypeActeAdministratif::class)],
            'contenu'   => ['nullable', 'string'],
        ];
    }
}
