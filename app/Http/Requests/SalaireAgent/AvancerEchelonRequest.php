<?php

namespace App\Http\Requests\SalaireAgent;

use Illuminate\Foundation\Http\FormRequest;

class AvancerEchelonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif' => ['nullable', 'string', 'max:500'],
        ];
    }
}
