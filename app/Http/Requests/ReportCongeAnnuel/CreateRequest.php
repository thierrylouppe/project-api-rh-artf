<?php

namespace App\Http\Requests\ReportCongeAnnuel;

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
            'agent_id'     => ['required', 'integer', 'exists:agents,id'],
            'annee_source' => ['required', 'integer', 'min:2000', 'max:2100'],
            'motif'        => ['required', 'string', 'min:3'],
        ];
    }
}
