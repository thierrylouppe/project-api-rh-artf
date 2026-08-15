<?php

namespace App\Http\Requests\Concerns;

use App\Rules\StructurableExiste;
use Illuminate\Validation\Validator;

trait ValideStructurable
{
    protected function validerStructure(Validator $validator, ?string $type, mixed $id, string $attribute): void
    {
        $rule = new StructurableExiste($type);
        $rule->validate($attribute, $id, function (string $message) use ($validator, $attribute): void {
            $validator->errors()->add($attribute, $message);
        });
    }
}
