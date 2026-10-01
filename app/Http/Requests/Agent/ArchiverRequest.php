<?php

namespace App\Http\Requests\Agent;

use App\Enums\MotifArchivage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ArchiverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif'                   => ['required', 'string', 'min:3'],
            'motif_code'              => ['nullable', new Enum(MotifArchivage::class)],
            'prioritaire_reembauche'  => ['nullable', 'boolean'],
        ];
    }
}
