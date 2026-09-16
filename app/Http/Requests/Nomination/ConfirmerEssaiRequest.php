<?php

namespace App\Http\Requests\Nomination;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmerEssaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
