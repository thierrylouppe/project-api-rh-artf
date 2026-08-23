<?php

namespace App\Http\Requests\Nomination;

use App\Enums\TypeActeNomination;
use App\Http\Requests\Concerns\ValideStructurable;
use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class CreateRequest extends FormRequest
{
    use ValideStructurable;

    /** @var array<string, list<string>> */
    private const POSTES_PAR_STRUCTURE = [
        Direction::class => ['Directeur Général', 'Directeur Central', 'Directeur Départemental'],
        Service::class   => ['Chef de Service'],
        Bureau::class    => ['Chef de Bureau'],
    ];

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
            'date_debut'        => ['required', 'date'],
            'type_acte'         => ['nullable', new Enum(TypeActeNomination::class)],
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

            $this->validerCoherencePoste($validator);
        });
    }

    public function messages(): array
    {
        return [
            'poste.in'               => 'Le poste doit être un des postes de responsabilité reconnus.',
            'structurable_type.in'   => 'La structure doit être une Direction, un Service ou un Bureau.',
            'type_acte.Illuminate\\Validation\\Rules\\Enum' => 'Le type d\'acte doit être arrêté, décision ou note de service.',
        ];
    }

    private function validerCoherencePoste(Validator $validator): void
    {
        $poste = $this->input('poste');
        $type  = $this->input('structurable_type');

        if (! is_string($poste) || ! is_string($type)) {
            return;
        }

        $postesAutorises = self::POSTES_PAR_STRUCTURE[$type] ?? null;
        if ($postesAutorises === null || in_array($poste, $postesAutorises, true)) {
            return;
        }

        $validator->errors()->add(
            'poste',
            'Le poste « '.$poste.' » n\'est pas cohérent avec le type de structure indiqué.'
        );
    }
}
