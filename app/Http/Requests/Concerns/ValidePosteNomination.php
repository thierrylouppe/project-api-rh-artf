<?php

namespace App\Http\Requests\Concerns;

use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Service;
use Illuminate\Validation\Validator;

trait ValidePosteNomination
{
    /** @return array<string, list<string>> */
    protected function postesParStructure(): array
    {
        return [
            Direction::class => ['Directeur Général', 'Directeur Central', 'Directeur Départemental'],
            Service::class   => ['Chef de Service'],
            Bureau::class    => ['Chef de Bureau'],
        ];
    }

    protected function validerCoherencePostePour(
        Validator $validator,
        mixed $poste,
        mixed $type,
        string $attribute,
    ): void {
        if (! is_string($poste) || ! is_string($type)) {
            return;
        }

        $postesAutorises = $this->postesParStructure()[$type] ?? null;
        if ($postesAutorises === null || in_array($poste, $postesAutorises, true)) {
            return;
        }

        $validator->errors()->add(
            $attribute,
            'Le poste « '.$poste.' » n\'est pas cohérent avec le type de structure indiqué.'
        );
    }
}
