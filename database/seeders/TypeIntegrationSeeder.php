<?php

namespace Database\Seeders;

use App\Models\TypeIntegration;
use Illuminate\Database\Seeder;

class TypeIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'nom'                     => 'Recrutement externe',
                'description'             => 'Nouvel agent recruté par concours ou sélection',
                'type_acte_administratif' => 'decision_recrutement',
                'necessite_contrat'       => false,
            ],
            [
                'nom'                     => 'Mutation',
                'description'             => 'Agent provenant d\'une autre administration',
                'type_acte_administratif' => 'decision_mutation',
                'necessite_contrat'       => false,
            ],
            [
                'nom'                     => 'Détachement',
                'description'             => 'Agent mis temporairement à disposition de l\'ARTF',
                'type_acte_administratif' => 'arrete_detachement',
                'necessite_contrat'       => false,
            ],
            [
                'nom'                     => 'Mise à disposition',
                'description'             => 'Agent prêté par une autre administration ou institution',
                'type_acte_administratif' => 'note_de_service',
                'necessite_contrat'       => false,
            ],
            [
                'nom'                     => 'Réintégration',
                'description'             => 'Agent revenant après une disponibilité, un détachement ou un congé spécial',
                'type_acte_administratif' => 'decision_recrutement',
                'necessite_contrat'       => false,
            ],
            [
                'nom'                     => 'Contractuel',
                'description'             => 'Agent recruté sous contrat à durée déterminée ou indéterminée',
                'type_acte_administratif' => 'contrat',
                'necessite_contrat'       => true,
            ],
            [
                'nom'                     => 'Stage professionnel',
                'description'             => 'Stagiaire ou agent en période d\'essai',
                'type_acte_administratif' => 'contrat',
                'necessite_contrat'       => true,
            ],
        ];

        foreach ($types as $data) {
            TypeIntegration::updateOrCreate(['nom' => $data['nom']], $data);
        }

        TypeIntegration::whereNotIn('nom', array_column($types, 'nom'))->delete();
    }
}
