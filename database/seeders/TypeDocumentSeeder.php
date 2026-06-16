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
        ];

        foreach ($types as $data) {
            TypeDocument::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
