<?php

namespace App\Http\Requests\PositionConventionnelle;

use Illuminate\Foundation\Http\FormRequest;

class CloturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_fin'    => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
