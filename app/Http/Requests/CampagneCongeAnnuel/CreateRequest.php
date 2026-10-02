<?php

namespace App\Http\Requests\CampagneCongeAnnuel;

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
            'annee'          => ['required', 'integer', 'min:2000', 'max:2100'],
            'date_ouverture' => ['required', 'date'],
            'date_cloture'   => ['required', 'date', 'after_or_equal:date_ouverture'],
        ];
    }
}
