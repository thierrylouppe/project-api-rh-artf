<?php

namespace App\Http\Requests\PositionConventionnelle;

use Illuminate\Foundation\Http\FormRequest;

class TraiterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
