<?php

namespace Database\Seeders;

use App\Models\MotifAdministratif;
use Illuminate\Database\Seeder;

class MotifAdministratifSeeder extends Seeder
{
    public function run(): void
    {
        $motifs = [
            ['nom' => 'Avancement d\'Échelon',         'description' => 'Progression automatique après ancienneté'],
            ['nom' => 'Promotion de Grade',             'description' => 'Élévation à un grade supérieur'],
            ['nom' => 'Mutation Interne',               'description' => 'Changement de poste au sein de l\'organisation'],
            ['nom' => 'Détachement',                    'description' => 'Affectation temporaire à un autre organisme'],
            ['nom' => 'Mise en Disponibilité',          'description' => 'Suspension temporaire du contrat à la demande de l\'agent'],
            ['nom' => 'Suspension Disciplinaire',       'description' => 'Mesure disciplinaire temporaire'],
            ['nom' => 'Fin de Contrat',                 'description' => 'Terme normal du contrat CDD'],
            ['nom' => 'Démission',                      'description' => 'Départ volontaire de l\'agent'],
            ['nom' => 'Licenciement',                   'description' => 'Rupture à l\'initiative de l\'employeur'],
            ['nom' => 'Retraite',                       'description' => 'Départ à la retraite'],
            ['nom' => 'Décès',                          'description' => 'Décès de l\'agent'],
            ['nom' => 'Réintégration',                  'description' => 'Retour en service après interruption'],
            ['nom' => 'Nomination à un Poste',          'description' => 'Désignation officielle à une fonction'],
        ];

        foreach ($motifs as $data) {
            MotifAdministratif::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
