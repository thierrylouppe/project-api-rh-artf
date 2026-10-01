<?php

namespace App\Http\Requests\DocumentDossier;

use App\Services\DocumentDossierService;
use Illuminate\Contracts\Validation\Validator;
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
            'type_document_id' => [
                'required',
                'integer',
                'exists:type_documents,id',
                Rule::unique('documents_dossier', 'type_document_id')
                    ->where('dossier_integration_id', $this->route('dossier')),
            ],
            'fichier'         => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'est_obligatoire' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_document_id.unique' => 'Ce type de document a déjà été déposé pour ce dossier.',
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->has('type_document_id')) {
                return;
            }

            $typeDocumentId = (int) $this->input('type_document_id');
            $dossierId      = (int) $this->route('dossier');

            $idsAutorises = app(DocumentDossierService::class)->idsTypesAutorisesPourDossier($dossierId);

            if ($idsAutorises === null) {
                $v->errors()->add('type_document_id', 'Dossier introuvable.');
                return;
            }

            if ($idsAutorises !== [] && ! in_array($typeDocumentId, $idsAutorises, true)) {
                $v->errors()->add(
                    'type_document_id',
                    'Ce type de document n\'est pas autorisé pour ce type d\'intégration.'
                );
            }
        });
    }
}
