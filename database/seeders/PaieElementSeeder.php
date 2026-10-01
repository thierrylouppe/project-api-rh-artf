<?php

namespace Database\Seeders;

use App\Enums\CodePaieElement;
use App\Models\PaieElement;
use Illuminate\Database\Seeder;

class PaieElementSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CodePaieElement::cases() as $code) {
            $meta = $code->meta();

            PaieElement::query()->updateOrCreate(
                ['code' => $code->value],
                [
                    'libelle' => $meta['libelle'],
                    'nature' => $meta['nature']->value,
                    'sens' => $meta['nature']->sens()->value,
                    'periodicite' => $meta['periodicite']->value,
                    'mode_calcul' => $meta['mode_calcul']->value,
                    'article_ccn' => $meta['article_ccn'],
                    'fonction_sigles' => $meta['fonction_sigles'],
                    'mois_declenchement' => $meta['mois_declenchement'],
                    'actif' => $meta['actif'],
                    'systeme' => true,
                ]
            );
        }
    }
}
