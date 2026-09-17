<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Vague F — rattacher un utilisateur DRHL à son bureau de rattachement.
 * bureau_id nullable : passer null (ou omettre) pour retirer le rattachement.
 */
class RattacherBureauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bureau_id' => ['nullable', 'integer', 'exists:bureaus,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'bureau_id.exists' => 'Le bureau indiqué n\'existe pas.',
            'bureau_id.integer' => 'L\'identifiant du bureau doit être un entier.',
        ];
    }
}
