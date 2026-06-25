<?php

namespace Database\Seeders;

use App\Models\TypeDocument;
use App\Models\TypeIntegration;
use Illuminate\Database\Seeder;

class TypeIntegrationSeeder extends Seeder
{
    /**
     * Documents obligatoires par type d'intégration (référencés par nom).
     * Doit correspondre aux entrées de TypeDocumentSeeder.
     */
    private const DOCUMENTS_PAR_TYPE = [
        'Recrutement externe' => [
            'Curriculum vitae',
            'Demande',
            'Diplôme',
            'Engagement',
            'Certificat de nationalité',
            'Casier judiciaire',
            'Certificat médical',
            'Acte de naissance',
        ],
        'Mutation' => [
            'Curriculum vitae',
            'Demande',
            'Diplôme',
            'Engagement',
            'Certificat de nationalité',
        ],
        'Détachement' => [
            'Curriculum vitae',
            'Demande',
            'Diplôme',
            'Engagement',
        ],
        'Mise à disposition' => [
            'Curriculum vitae',
            'Demande',
            'Diplôme',
            'Engagement',
        ],
        'Réintégration' => [
            'Curriculum vitae',
            'Demande',
            'Engagement',
        ],
        'Contractuel' => [
            'Curriculum vitae',
            'Demande',
            'Diplôme',
            'Engagement',
            'Certificat de nationalité',
            'Casier judiciaire',
            'Certificat médical',
            'Acte de naissance',
        ],
        'Stage professionnel' => [
            'Demande de stage adressée au Directeur Général',
            'Lettre de recommandation de l\'établissement',
            'Convention de stage',
        ],
        'Stage académique' => [
            'Demande de stage adressée au Directeur Général',
            'Lettre de recommandation de l\'établissement',
            'Convention de stage',
            'Certificat de scolarité',
        ],
        'Stage de qualification' => [
            'Demande de stage adressée au Directeur Général',
            'Lettre de recommandation de l\'établissement',
            'Convention de stage',
            'Décision de mise en stage',
        ],
    ];

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
                'nom'                          => 'Stage professionnel',
                'description'                  => 'Stage en milieu professionnel — stagiaires non statutaires',
                'type_acte_administratif'      => 'contrat',
                'necessite_contrat'            => true,
                'necessite_validation_dg'      => false,
                'necessite_compte_utilisateur' => false,
                'prefixe_matricule'            => 'STG',
            ],
            [
                'nom'                          => 'Stage académique',
                'description'                  => "Stage d'application ou de fin d'études — étudiants",
                'type_acte_administratif'      => 'contrat',
                'necessite_contrat'            => true,
                'necessite_validation_dg'      => false,
                'necessite_compte_utilisateur' => false,
                'prefixe_matricule'            => 'STG',
            ],
            [
                'nom'                          => 'Stage de qualification',
                'description'                  => 'Mise en stage après réussite à un concours professionnel',
                'type_acte_administratif'      => 'contrat',
                'necessite_contrat'            => true,
                'necessite_validation_dg'      => true,
                'necessite_compte_utilisateur' => false,
                'prefixe_matricule'            => 'STG',
            ],
        ];

        foreach ($types as $data) {
            $typeIntegration = TypeIntegration::updateOrCreate(['nom' => $data['nom']], $data);

            $nomsDocuments = self::DOCUMENTS_PAR_TYPE[$data['nom']] ?? [];

            if ($nomsDocuments !== []) {
                $ids = TypeDocument::whereIn('nom', $nomsDocuments)->pluck('id');
                $typeIntegration->documentsObligatoires()->sync($ids);
            }
        }

        TypeIntegration::whereNotIn('nom', array_column($types, 'nom'))->delete();
    }
}
