<?php

namespace Database\Seeders;

use App\Models\MotifAdministratif;
use Illuminate\Database\Seeder;

class MotifAdministratifSeeder extends Seeder
{
    public function run(): void
    {
        $motifs = [
            // Positions liées aux congés
            ['nom' => 'Congé administratif',                    'description' => 'Congé annuel légal après 11 mois de service (loi n° 021-89)'],
            ['nom' => 'Congé de maternité',                     'description' => 'Position administrative pour congé de maternité'],
            ['nom' => 'Congé de maladie',                       'description' => 'Position administrative pour incapacité médicale'],
            ['nom' => 'Congé exceptionnel',                     'description' => 'Congé pour circonstance exceptionnelle (décès, mariage)'],
            ['nom' => 'Congé pour concours',                    'description' => 'Absence autorisée pour préparation ou participation à un concours (max. 1 mois)'],
            ['nom' => 'Congé de longue durée',                  'description' => 'Absence prolongée pour maladie grave ou infirmité'],
            ['nom' => 'Congé pour convenances personnelles',    'description' => 'Congé non rémunéré pour motif personnel (max. 6 mois/an, min. 15 jours)'],
            ['nom' => 'Congé formation syndicale',              'description' => 'Absence autorisée pour activité de formation syndicale (max. 6 mois)'],

            // Positions statutaires (détachement, disponibilité, stage)
            ['nom' => 'Mise en stage de qualification',         'description' => 'Mise en stage après réussite à un concours professionnel'],
            ['nom' => 'Mise en détachement',                    'description' => 'Affectation temporaire dans un autre organisme avec droits conservés (décret n° 86/1025)'],
            ['nom' => 'Fin de détachement',                     'description' => 'Retour en poste à l\'issue d\'un détachement'],
            ['nom' => 'Mise en disponibilité',                  'description' => 'Suspension temporaire des obligations de service à la demande de l\'agent (décret n° 86/1026)'],
            ['nom' => 'Fin de disponibilité',                   'description' => 'Retour en service à l\'issue d\'une mise en disponibilité'],

            // Carrière — avancements et promotions
            ['nom' => 'Avancement d\'échelon',                  'description' => 'Progression automatique à l\'échelon supérieur après ancienneté requise'],
            ['nom' => 'Promotion / reclassement',               'description' => 'Élévation à un grade ou une classe supérieure'],
            ['nom' => 'Affectation / mutation',                 'description' => 'Changement de poste, de service ou d\'administration'],
            ['nom' => 'Nomination à un poste',                  'description' => 'Désignation officielle à une fonction par acte de l\'autorité compétente'],
            ['nom' => 'Réintégration',                          'description' => 'Retour en service actif après interruption (disponibilité, détachement, congé)'],

            // Fins de carrière et sanctions
            ['nom' => 'Admission à la retraite',                'description' => 'Départ à la retraite par limite d\'âge ou sur demande'],
            ['nom' => 'Démission',                              'description' => 'Départ volontaire accepté par l\'autorité compétente'],
            ['nom' => 'Licenciement',                           'description' => 'Rupture de la relation de travail à l\'initiative de l\'administration'],
            ['nom' => 'Révocation',                             'description' => 'Sanction disciplinaire entraînant la cessation définitive de fonctions'],
            ['nom' => 'Radiation d\'office',                    'description' => 'Radiation automatique pour abandon de poste ou condamnation pénale'],
            ['nom' => 'Suspension de fonctions',                'description' => 'Mesure conservatoire en attente d\'une procédure disciplinaire'],
        ];

        foreach ($motifs as $data) {
            MotifAdministratif::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
