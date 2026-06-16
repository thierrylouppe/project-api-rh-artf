<?php

namespace App\Http\Requests\Parametregrile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'valeur_point_indice' => ['sometimes', 'numeric', 'min:0'],
            'indice_base'         => ['sometimes', 'integer', 'min:1'],
            'echelon_depart'      => ['sometimes', 'integer', 'min:1', 'max:20'],
            'echelon_fin'         => ['sometimes', 'integer', 'min:1', 'max:20', 'gte:echelon_depart'],
            'ecart_depart'        => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
