<?php

namespace App\Http\Requests\PositionConventionnelle;

use Illuminate\Foundation\Http\FormRequest;

class RenouvelerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_debut' => ['required', 'date'],
            'date_fin'   => ['required', 'date', 'after_or_equal:date_debut'],
        ];
    }
}
