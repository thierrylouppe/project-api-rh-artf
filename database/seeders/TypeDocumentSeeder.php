<?php

namespace Database\Seeders;

use App\Models\TypeDocument;
use Illuminate\Database\Seeder;

class TypeDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Documents obligatoires pour CDI / CDD
            ['nom' => 'Curriculum vitae',           'obligatoire' => true,  'description' => 'CV — obligatoire pour tous les contrats'],
            ['nom' => 'Demande',                    'obligatoire' => true,  'description' => 'Lettre de demande — obligatoire pour tous les contrats'],
            ['nom' => 'Diplôme',                    'obligatoire' => true,  'description' => 'Justificatif de niveau d\'études — obligatoire CDI/CDD'],
            ['nom' => 'Engagement',                 'obligatoire' => true,  'description' => 'Acte d\'engagement — obligatoire CDI/CDD/Consultant'],
            ['nom' => 'Certificat de nationalité',  'obligatoire' => true,  'description' => 'Justificatif de nationalité congolaise — obligatoire CDI/CDD'],
            ['nom' => 'Casier judiciaire',          'obligatoire' => true,  'description' => 'Extrait du casier judiciaire — obligatoire CDI/CDD'],
            ['nom' => 'Certificat médical',         'obligatoire' => true,  'description' => 'Certificat d\'aptitude médicale — obligatoire CDI/CDD'],
            ['nom' => 'Acte de naissance',          'obligatoire' => true,  'description' => 'Extrait d\'acte de naissance — obligatoire CDI/CDD'],

            // Document administratif complémentaire
            ['nom' => 'Nomination',                 'obligatoire' => false, 'description' => 'Acte de nomination à un poste ou une fonction'],

            // Documents spécifiques stage (communs à tous les types)
            ['nom' => 'Demande de stage adressée au Directeur Général', 'obligatoire' => true,  'description' => 'Lettre formelle de candidature adressée au DG — obligatoire tous types de stage'],
            ['nom' => 'Lettre de recommandation de l\'établissement',   'obligatoire' => true,  'description' => 'Aval de l\'école, université ou organisme d\'origine — obligatoire tous types de stage'],
            ['nom' => 'Convention de stage',                            'obligatoire' => true,  'description' => 'Convention tripartite ou bilatérale ARTF / stagiaire / établissement'],
            ['nom' => 'Certificat de scolarité',                        'obligatoire' => false, 'description' => 'Justificatif d\'inscription scolaire ou universitaire (stage académique)'],
            ['nom' => 'Attestation d\'inscription',                     'obligatoire' => false, 'description' => 'Preuve d\'inscription en cours de formation (stage académique)'],
            ['nom' => 'Décision de mise en stage',                      'obligatoire' => false, 'description' => 'Acte administratif de mise en stage après concours (stage de qualification)'],
        ];

        foreach ($types as $data) {
            TypeDocument::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
