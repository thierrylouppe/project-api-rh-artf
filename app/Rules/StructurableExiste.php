<?php

namespace App\Rules;

use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Service;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StructurableExiste implements ValidationRule
{
    public function __construct(private readonly ?string $type) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $types = [Direction::class, Service::class, Bureau::class];

        if ($this->type === null || $value === null || ! in_array($this->type, $types, true)) {
            return;
        }

        if (! $this->type::query()->whereKey($value)->exists()) {
            $fail('La structure indiquée n\'existe pas.');
        }
    }
}
