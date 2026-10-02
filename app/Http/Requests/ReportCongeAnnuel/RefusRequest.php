<?php

namespace App\Http\Requests\ReportCongeAnnuel;

use Illuminate\Foundation\Http\FormRequest;

class RefusRequest extends FormRequest
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
