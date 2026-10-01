<?php

namespace App\Http\Requests\Commission;

use Illuminate\Foundation\Http\FormRequest;

/** Clôturer une commission. */
class CloturerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
