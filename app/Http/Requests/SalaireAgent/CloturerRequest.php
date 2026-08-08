<?php

namespace App\Http\Requests\SalaireAgent;

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
            'date_fin' => ['nullable', 'date'],
            'motif'    => ['nullable', 'string', 'max:500'],
        ];
    }
}
