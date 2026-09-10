<?php

namespace App\Http\Requests\AvancementExceptionnel;

use Illuminate\Foundation\Http\FormRequest;

class ProposeAvancementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'agent_id'                 => ['required', 'integer', 'exists:agents,id'],
            'nb_echelons'              => ['required', 'integer', 'min:1', 'max:2'],
            'motif'                    => ['required', 'string', 'min:10', 'max:2000'],
            'commission_avancement_id' => ['nullable', 'integer', 'exists:commissions_avancements,id'],
            'date_proposition'         => ['nullable', 'date'],
        ];
    }
}
