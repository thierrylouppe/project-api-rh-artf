<?php

namespace App\Http\Requests\DemandeConge;

use Illuminate\Foundation\Http\FormRequest;

class RejectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commentaire' => ['required', 'string', 'min:3'],
        ];
    }
}
