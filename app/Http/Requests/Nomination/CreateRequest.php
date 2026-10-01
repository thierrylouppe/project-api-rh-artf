<?php

namespace App\Http\Requests\Nomination;

use App\Enums\TypeActeNomination;
use App\Http\Requests\Concerns\ValidePosteNomination;
use App\Http\Requests\Concerns\ValideStructurable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class CreateRequest extends FormRequest
{
    use ValidePosteNomination;
    use ValideStructurable;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id'          => ['required', 'integer', 'exists:agents,id'],
            'poste'             => ['required', 'string', 'in:Directeur Général,Directeur Central,Directeur Départemental,Chef de Service,Chef de Bureau'],
            'structurable_type' => ['required', 'string', 'in:App\\Models\\Direction,App\\Models\\Service,App\\Models\\Bureau'],
            'structurable_id'   => ['required', 'integer'],
            'date_debut'                  => ['required', 'date'],
            'type_acte'                   => ['nullable', new Enum(TypeActeNomination::class)],
            'soumis_a_essai'              => ['nullable', 'boolean'],
            'classegrillesalariale_id'    => [
                'nullable',
                'integer',
                'exists:classegrillesalariales,id',
                Rule::requiredIf(fn () => filter_var($this->input('soumis_a_essai'), FILTER_VALIDATE_BOOLEAN)),
            ],
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

            $this->validerCoherencePostePour(
                $validator,
                $this->input('poste'),
                $this->input('structurable_type'),
                'poste'
            );
        });
    }

    public function messages(): array
    {
        return [
            'poste.in'             => 'Le poste doit être un des postes de responsabilité reconnus.',
            'structurable_type.in' => 'La structure doit être une Direction, un Service ou un Bureau.',
            'type_acte.Illuminate\\Validation\\Rules\\Enum' => 'Le type d\'acte doit être arrêté, décision ou note de service.',
            'classegrillesalariale_id.required' => 'La classe cible est obligatoire pour un essai d\'emploi supérieur (art. 50).',
        ];
    }
}
