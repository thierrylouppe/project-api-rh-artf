<?php

namespace App\Http\Requests\Commission;

use App\Enums\DecisionCommission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Enregistrer la décision d'avancement pour une fiche. */
class DeciderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'evaluation_id'   => ['required', 'integer', 'exists:evaluations,id'],
            'decision'        => ['required', 'string', Rule::enum(DecisionCommission::class)],
            'nombre_echelons' => ['nullable', 'integer', 'min:0', 'max:2'],
            'note_avancement' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'commentaire'     => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required'      => 'La décision (favorable, defavorable, reporte) est obligatoire.',
            'nombre_echelons.max'    => 'Le nombre d\'échelons ne peut excéder 2 (même classe, art. 70).',
        ];
    }
}
