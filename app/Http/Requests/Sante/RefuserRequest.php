<?php

namespace App\Http\Requests\Sante;

use Illuminate\Foundation\Http\FormRequest;

class RefuserRequest extends FormRequest
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
