<?php

namespace Database\Seeders;

use App\Enums\NiveauValidation;
use App\Models\CircuitValidationTypeIntegration;
use App\Models\TypeIntegration;
use Illuminate\Database\Seeder;

class CircuitValidationSeeder extends Seeder
{
    /**
     * Circuit par type d'intégration.
     * Niveaux listés dans l'ordre de validation souhaité.
     */
    private const CIRCUITS = [
        'Recrutement externe' => [
            NiveauValidation::CHEF_BUREAU,
            NiveauValidation::CHEF_SERVICE,
            NiveauValidation::DIRECTEUR,
            NiveauValidation::DRH,
            NiveauValidation::DIRECTEUR_GENERAL,
        ],
        'Mutation' => [
            NiveauValidation::CHEF_BUREAU,
            NiveauValidation::CHEF_SERVICE,
            NiveauValidation::DIRECTEUR,
            NiveauValidation::DRH,
            NiveauValidation::DIRECTEUR_GENERAL,
        ],
        'Détachement' => [
            NiveauValidation::CHEF_SERVICE,
            NiveauValidation::DIRECTEUR,
            NiveauValidation::DRH,
            NiveauValidation::DIRECTEUR_GENERAL,
        ],
        'Mise à disposition' => [
            NiveauValidation::CHEF_SERVICE,
            NiveauValidation::DIRECTEUR,
            NiveauValidation::DRH,
            NiveauValidation::DIRECTEUR_GENERAL,
        ],
        'Réintégration' => [
            NiveauValidation::CHEF_BUREAU,
            NiveauValidation::CHEF_SERVICE,
            NiveauValidation::DIRECTEUR,
            NiveauValidation::DRH,
            NiveauValidation::DIRECTEUR_GENERAL,
        ],
        'Contractuel' => [
            NiveauValidation::CHEF_BUREAU,
            NiveauValidation::CHEF_SERVICE,
            NiveauValidation::DIRECTEUR,
            NiveauValidation::DRH,
            NiveauValidation::DIRECTEUR_GENERAL,
        ],
        // Stages : circuit allégé, pas de DG
        'Stage professionnel' => [
            NiveauValidation::CHEF_SERVICE,
            NiveauValidation::DIRECTEUR,
            NiveauValidation::DRH,
        ],
        'Stage académique' => [
            NiveauValidation::CHEF_SERVICE,
            NiveauValidation::DIRECTEUR,
            NiveauValidation::DRH,
        ],
        // Stage de qualification : concours réussi → passage par DG
        'Stage de qualification' => [
            NiveauValidation::CHEF_BUREAU,
            NiveauValidation::CHEF_SERVICE,
            NiveauValidation::DIRECTEUR,
            NiveauValidation::DRH,
            NiveauValidation::DIRECTEUR_GENERAL,
        ],
    ];

    public function run(): void
    {
        foreach (self::CIRCUITS as $nomType => $niveaux) {
            $type = TypeIntegration::where('nom', $nomType)->first();

            if (! $type) {
                $this->command->warn("TypeIntegration introuvable : {$nomType}");
                continue;
            }

            // On purge et on recrée pour être idempotent
            CircuitValidationTypeIntegration::where('type_integration_id', $type->id)->delete();

            foreach ($niveaux as $ordre => $niveau) {
                CircuitValidationTypeIntegration::create([
                    'type_integration_id' => $type->id,
                    'niveau'              => $niveau->value,
                    'ordre'               => $ordre + 1,
                    'actif'               => true,
                ]);
            }
        }
    }
}
