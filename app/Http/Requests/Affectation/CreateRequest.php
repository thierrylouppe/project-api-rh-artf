<?php

namespace App\Http\Requests\Affectation;

use App\Enums\MotifAffectation;
use App\Enums\PieceRapprochement;
use App\Http\Requests\Concerns\ValideStructurable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class CreateRequest extends FormRequest
{
    use ValideStructurable;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reglesPieces = [];
        foreach (PieceRapprochement::toutes() as $piece) {
            $reglesPieces[$piece->champFichier()] = [
                Rule::requiredIf(fn () => $this->estRapprochement()),
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ];
        }

        return [
            'agent_id'                  => ['required', 'integer', 'exists:agents,id'],
            'structurable_type'         => ['required', 'string', 'in:App\\Models\\Direction,App\\Models\\Service,App\\Models\\Bureau'],
            'structurable_id'           => ['required', 'integer'],
            'motif'                     => ['nullable', 'string', 'max:1000'],
            'motif_code'                => ['nullable', new Enum(MotifAffectation::class)],
            'commentaire_opportunite'   => ['nullable', 'string', 'max:2000'],
            'note_service'              => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'superieur_hierarchique_id' => ['nullable', 'integer', 'exists:agents,id'],
            'date_affectation'          => ['required', 'date'],
            ...$reglesPieces,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validerStructure(
                $validator,
                $this->input('structurable_type'),
                $this->input('structurable_id'),
                'structurable_id'
            );
        });
    }

    public function messages(): array
    {
        $messages = [
            'note_service.mimes' => 'La note de service doit être un fichier PDF, JPG ou PNG.',
            'note_service.max'   => 'La note de service ne doit pas dépasser 10 Mo.',
        ];

        foreach (PieceRapprochement::toutes() as $piece) {
            $messages[$piece->champFichier().'.required'] = $piece->label().' est obligatoire (art. 81).';
        }

        return $messages;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('motif_code') === null
            && $this->input('motif') === MotifAffectation::RAPPROCHEMENT_CONJOINTS->value) {
            $this->merge(['motif_code' => MotifAffectation::RAPPROCHEMENT_CONJOINTS->value]);
        }
    }

    private function estRapprochement(): bool
    {
        return MotifAffectation::estRapprochement(
            $this->input('motif_code'),
            $this->input('motif')
        );
    }
}
