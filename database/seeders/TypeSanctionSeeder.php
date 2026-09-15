<?php

namespace Database\Seeders;

use App\Enums\CodeTypeSanction;
use App\Enums\GraviteSanction;
use App\Models\TypeSanction;
use Illuminate\Database\Seeder;

class TypeSanctionSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => CodeTypeSanction::AVERTISSEMENT_ECRIT,
                'noms' => ['Avertissement écrit'],
                'nom' => 'Avertissement écrit',
                'gravite' => GraviteSanction::LEGER,
                'description' => 'Rappel formel écrit, sans incidence sur la carrière (CCN art. 90).',
                'exige_nb_jours' => false,
                'nb_jours_min' => null,
                'nb_jours_max' => null,
                'actif' => true,
            ],
            [
                'code' => CodeTypeSanction::BLAME_ECRIT,
                'noms' => ['Blâme', 'Blâme écrit'],
                'nom' => 'Blâme écrit',
                'gravite' => GraviteSanction::LEGER,
                'description' => 'Réprimande écrite inscrite au dossier de l\'agent (CCN art. 90).',
                'exige_nb_jours' => false,
                'nb_jours_min' => null,
                'nb_jours_max' => null,
                'actif' => true,
            ],
            [
                'code' => CodeTypeSanction::MISE_A_PIED,
                'noms' => ['Mise à pied', 'Mise à pied sans rémunération'],
                'nom' => 'Mise à pied sans rémunération',
                'gravite' => GraviteSanction::MOYEN,
                'description' => 'Suspension de fonctions sans rémunération, de 1 à 8 jours (CCN art. 90).',
                'exige_nb_jours' => true,
                'nb_jours_min' => 1,
                'nb_jours_max' => 8,
                'actif' => true,
            ],
            [
                'code' => CodeTypeSanction::LICENCIEMENT,
                'noms' => ['Licenciement'],
                'nom' => 'Licenciement',
                'gravite' => GraviteSanction::GRAVE,
                'description' => 'Rupture du contrat, avec ou sans indemnité (CCN art. 90).',
                'exige_nb_jours' => false,
                'nb_jours_min' => null,
                'nb_jours_max' => null,
                'actif' => true,
            ],
        ];

        $codes = [];

        foreach ($types as $data) {
            $codes[] = $data['code']->value;
            $existing = TypeSanction::query()
                ->where(function ($query) use ($data) {
                    $query->where('code', $data['code']->value)
                        ->orWhereIn('nom', $data['noms']);
                })
                ->first();

            $payload = [
                'code' => $data['code']->value,
                'nom' => $data['nom'],
                'gravite' => $data['gravite'],
                'description' => $data['description'],
                'exige_nb_jours' => $data['exige_nb_jours'],
                'nb_jours_min' => $data['nb_jours_min'],
                'nb_jours_max' => $data['nb_jours_max'],
                'actif' => true,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                TypeSanction::create($payload);
            }
        }

        TypeSanction::query()
            ->where(function ($query) use ($codes) {
                $query->whereNull('code')->orWhereNotIn('code', $codes);
            })
            ->update(['actif' => false]);
    }
}
