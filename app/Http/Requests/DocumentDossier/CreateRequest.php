<?php

namespace App\Http\Requests\DocumentDossier;

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
            'fichier'          => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'est_obligatoire'  => ['nullable', 'boolean'],
        ];
    }
}
