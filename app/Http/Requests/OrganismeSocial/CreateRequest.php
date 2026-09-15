<?php

namespace App\Http\Requests\OrganismeSocial;

use App\Enums\TypeOrganismeSocial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:organismes_sociaux,code'],
            'type' => ['required', 'string', Rule::enum(TypeOrganismeSocial::class)],
            'description' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}
