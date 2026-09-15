<?php

namespace App\Http\Requests\Reclassement;

use Illuminate\Foundation\Http\FormRequest;

class TraiterReclassementRequest extends FormRequest
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
