<?php

namespace Database\Seeders;

use App\Models\TypeDocument;
use Illuminate\Database\Seeder;

class TypeDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Carte Nationale d\'Identité',        'obligatoire' => true],
            ['nom' => 'Acte de Naissance',                   'obligatoire' => true],
            ['nom' => 'Diplôme de Formation',                'obligatoire' => true],
            ['nom' => 'Curriculum Vitae',                    'obligatoire' => true],
            ['nom' => 'Casier Judiciaire',                   'obligatoire' => true],
            ['nom' => 'Certificat Médical d\'Aptitude',      'obligatoire' => true],
            ['nom' => 'Photo d\'Identité',                   'obligatoire' => true],
            ['nom' => 'Attestation de Résidence',            'obligatoire' => false],
            ['nom' => 'Extrait du Registre de Commerce',     'obligatoire' => false],
            ['nom' => 'Relevé de Notes',                     'obligatoire' => false],
            ['nom' => 'Lettre de Recommandation',            'obligatoire' => false],
            ['nom' => 'Attestation de Travail',              'obligatoire' => false],
        ];

        foreach ($types as $data) {
            TypeDocument::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
