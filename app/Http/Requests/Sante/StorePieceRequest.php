<?php

namespace App\Http\Requests\Sante;

use App\Enums\TypePieceSante;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePieceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fichier' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'type_piece' => ['required', 'string', Rule::enum(TypePieceSante::class)],
        ];
    }
}
