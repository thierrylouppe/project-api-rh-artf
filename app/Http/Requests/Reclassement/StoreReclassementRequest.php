<?php

namespace App\Http\Requests\Reclassement;

use App\Enums\MotifReconversion;
use App\Enums\TypeReclassement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReclassementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id'            => ['required', 'integer', 'exists:agents,id'],
            'type'                => ['required', 'string', Rule::enum(TypeReclassement::class)],
            'motif'               => ['required', 'string', 'min:10', 'max:2000'],
            'diplome_id'          => ['nullable', 'integer', 'exists:diplomes,id'],
            'classe_cible_id'     => ['nullable', 'integer', 'exists:classegrillesalariales,id'],
            'fonction_cible_id'   => ['nullable', 'integer', 'exists:fonctions,id'],
            'motif_reconversion'  => ['nullable', 'string', Rule::enum(MotifReconversion::class)],
            'piece_path'          => ['nullable', 'string', 'max:500'],
        ];
    }
}
