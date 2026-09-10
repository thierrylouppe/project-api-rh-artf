<?php

namespace App\Http\Requests\BonificationStage;

use Illuminate\Foundation\Http\FormRequest;

class TraiterBonificationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'approuver'    => ['required', 'boolean'],
            'commentaire'  => ['nullable', 'string', 'max:2000'],
        ];
    }
}
