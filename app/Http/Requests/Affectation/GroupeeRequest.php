<?php

namespace App\Http\Requests\Affectation;

use Illuminate\Foundation\Http\FormRequest;

class GroupeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Champs communs à toutes les affectations du lot
            'date_affectation'  => ['required', 'date'],
            'motif'             => ['nullable', 'string', 'max:1000'],
            'note_service'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            // Liste des agents avec leur propre structure et supérieur
            'agents'                                  => ['required', 'array', 'min:1'],
            'agents.*.agent_id'                       => ['required', 'integer', 'exists:agents,id', 'distinct'],
            'agents.*.structurable_type'              => ['required', 'string', 'in:App\\Models\\Direction,App\\Models\\Service,App\\Models\\Bureau'],
            'agents.*.structurable_id'                => ['required', 'integer'],
            'agents.*.superieur_hierarchique_id'      => ['nullable', 'integer', 'exists:agents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'agents.required'                        => 'La liste des agents est obligatoire.',
            'agents.min'                             => 'Au moins un agent doit être fourni.',
            'agents.*.agent_id.required'             => 'L\'identifiant de l\'agent est obligatoire.',
            'agents.*.agent_id.exists'               => 'L\'agent :input n\'existe pas.',
            'agents.*.agent_id.distinct'             => 'Un même agent ne peut pas apparaître deux fois dans le lot.',
            'agents.*.structurable_type.required'    => 'Le type de structure est obligatoire pour chaque agent.',
            'agents.*.structurable_type.in'          => 'Le type de structure doit être Direction, Service ou Bureau.',
            'agents.*.structurable_id.required'      => 'L\'identifiant de la structure est obligatoire pour chaque agent.',
            'note_service.mimes'                     => 'La note de service doit être un fichier PDF, JPG ou PNG.',
            'note_service.max'                       => 'La note de service ne doit pas dépasser 10 Mo.',
        ];
    }
}
