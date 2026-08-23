<?php

namespace App\Http\Requests\Nomination;

use App\Enums\TypeActeNomination;
use App\Http\Requests\Concerns\ValidePosteNomination;
use App\Http\Requests\Concerns\ValideStructurable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class GroupeeRequest extends FormRequest
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
            'date_debut' => ['required', 'date'],
            'type_acte'  => ['nullable', new Enum(TypeActeNomination::class)],
            'agents'     => ['required', 'array', 'min:2'],
            'agents.*.agent_id'          => ['required', 'integer', 'exists:agents,id', 'distinct'],
            'agents.*.poste'             => ['required', 'string', 'in:Directeur Général,Directeur Central,Directeur Départemental,Chef de Service,Chef de Bureau'],
            'agents.*.structurable_type' => ['required', 'string', 'in:App\\Models\\Direction,App\\Models\\Service,App\\Models\\Bureau'],
            'agents.*.structurable_id'   => ['required', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $clesStructure = [];

            foreach ($this->input('agents', []) as $index => $ligne) {
                $this->validerStructure(
                    $validator,
                    $ligne['structurable_type'] ?? null,
                    $ligne['structurable_id'] ?? null,
                    "agents.{$index}.structurable_id"
                );

                $this->validerCoherencePostePour(
                    $validator,
                    $ligne['poste'] ?? null,
                    $ligne['structurable_type'] ?? null,
                    "agents.{$index}.poste"
                );

                $type = $ligne['structurable_type'] ?? '';
                $id   = $ligne['structurable_id'] ?? '';
                $cle  = $type.'#'.$id;
                if ($cle !== '#' && isset($clesStructure[$cle])) {
                    $validator->errors()->add(
                        "agents.{$index}.structurable_id",
                        'Une structure ne peut avoir qu\'un seul responsable dans le même lot.'
                    );
                }
                $clesStructure[$cle] = true;
            }
        });
    }

    public function messages(): array
    {
        return [
            'agents.required'            => 'La liste des agents est obligatoire.',
            'agents.min'                 => 'Un lot doit contenir au moins deux nominations.',
            'agents.*.agent_id.distinct' => 'Un même agent ne peut pas apparaître deux fois dans le lot.',
        ];
    }
}
