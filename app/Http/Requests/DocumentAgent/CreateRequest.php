<?php

namespace App\Http\Requests\DocumentAgent;

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
            'type_document_id' => ['required', 'integer', 'exists:type_documents,id'],
            'titre'            => ['nullable', 'string', 'max:255'],
            'sous_dossier'     => ['nullable', 'string', 'max:100'],
            'fichier'          => ['required', 'file', 'max:10240'],
        ];
    }
}
