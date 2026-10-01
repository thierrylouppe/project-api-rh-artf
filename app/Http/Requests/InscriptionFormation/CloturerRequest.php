<?php

namespace App\Http\Requests\InscriptionFormation;

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
            'rapport_remis' => ['nullable', 'boolean'],
            'date_fin' => ['nullable', 'date'],
        ];
    }
}
