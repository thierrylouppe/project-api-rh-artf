<?php

namespace Database\Seeders;

use App\Models\TypeConge;
use Illuminate\Database\Seeder;

class TypeCongeSeeder extends Seeder
{
    public function run(): void
    {
        // Renommage des anciens types dont le libellé a changé (migration non destructive)
        $renames = [
            'Congé exceptionnel — mariage'           => 'Congé exceptionnel — mariage du salarié',
            'Congé exceptionnel — décès d\'un parent' => 'Congé exceptionnel — décès d\'un parent (père, mère, frère, sœur, enfant)',
        ];
        foreach ($renames as $ancienNom => $nouveauNom) {
            $nouveauNomExiste = TypeConge::where('nom', $nouveauNom)->exists();
            if (! $nouveauNomExiste) {
                TypeConge::where('nom', $ancienNom)->update(['nom' => $nouveauNom]);
            }
        }

        // -------------------------------------------------------------------------
        // Types de congé conformes à la Convention Collective ARTF (art. 77–79)
        // -------------------------------------------------------------------------
        $types = [

            // ── Congé annuel ─────────────────────────────────────────────────────
            [
                'nom'                  => 'Congé annuel',
                'jours_max'            => 30,
                'necessite_n1'         => true,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => true,
                'justificatif_requis'  => false,
                'description'          => 'CCN art. 77 — Base 30 j + jours ancienneté. N+1 puis RH.',
            ],

            // ── Congés de maternité / paternité ──────────────────────────────────
            [
                'nom'                  => 'Congé de maternité',
                'jours_max'            => 105, // CCN : 15 semaines × 7 jours
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 15 semaines (9 sem. post-délivrance). Payant. Validation RH.',
            ],
            [
                'nom'                  => 'Congé de paternité',
                'jours_max'            => 2, // CCN art. 77 : 2 jours
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 2 jours. Payant. Validation RH.',
            ],

            // ── Congés exceptionnels (art. 77 — événements familiaux) ─────────────
            [
                'nom'                  => 'Congé exceptionnel — mariage du salarié',
                'jours_max'            => 5,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 5 jours. Payant. Pris au moment de l\'événement.',
            ],
            [
                'nom'                  => 'Congé exceptionnel — mariage d\'un enfant',
                'jours_max'            => 2,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 2 jours. Payant. Pris au moment de l\'événement.',
            ],
            [
                'nom'                  => 'Congé exceptionnel — baptême d\'un enfant',
                'jours_max'            => 1,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 1 jour. Payant. Pris au moment de l\'événement.',
            ],
            [
                'nom'                  => 'Congé exceptionnel — déménagement',
                'jours_max'            => 2,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => false,
                'description'          => 'CCN art. 77 — 2 jours. Payant.',
            ],
            [
                'nom'                  => 'Congé exceptionnel — décès du conjoint',
                'jours_max'            => 10,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 10 jours. Payant. Distinct du décès d\'un parent.',
            ],
            [
                'nom'                  => 'Congé exceptionnel — décès d\'un parent (père, mère, frère, sœur, enfant)',
                'jours_max'            => 5,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 5 jours. Payant.',
            ],
            [
                'nom'                  => 'Congé exceptionnel — retrait de deuil',
                'jours_max'            => 2,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => false,
                'description'          => 'CCN art. 77 — 2 jours. Payant.',
            ],
            [
                'nom'                  => 'Congé exceptionnel — construction de la pierre tombale',
                'jours_max'            => 2,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => false,
                'description'          => 'CCN art. 77 — 2 jours. Payant.',
            ],

            // ── Maladie des enfants, conjoint et ascendants (art. 77) ─────────────
            [
                'nom'                  => 'Congé maladie — ascendants',
                'jours_max'            => 4,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 4 jours/an. Payant plein traitement. Certificat médical obligatoire.',
            ],
            [
                'nom'                  => 'Congé maladie — conjoint',
                'jours_max'            => 7,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 7 jours/an. Payant plein traitement. Certificat médical obligatoire.',
            ],
            [
                'nom'                  => 'Congé maladie — 1 enfant à charge',
                'jours_max'            => 5,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 5 jours/an. Payant plein traitement. Certificat médical obligatoire.',
            ],
            [
                'nom'                  => 'Congé maladie — 2 enfants à charge',
                'jours_max'            => 9,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 9 jours/an. Payant plein traitement. Certificat médical obligatoire.',
            ],
            [
                'nom'                  => 'Congé maladie — 3 enfants et plus à charge',
                'jours_max'            => 12,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — 12 jours/an. Payant plein traitement. Certificat médical obligatoire.',
            ],

            // ── Congé maladie ordinaire ───────────────────────────────────────────
            [
                'nom'                  => 'Congé maladie',
                'jours_max'            => 0, // Pas de plafond
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — Durée selon certificat médical (art. 47 code du travail). Payant. Validation RH.',
            ],

            // ── Congés pour convenances personnelles ──────────────────────────────
            [
                'nom'                  => 'Congé pour convenances personnelles',
                'jours_max'            => 180, // 6 mois max par année civile
                'necessite_n1'         => true,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => false,
                'description'          => 'CCN art. 77 — Max 6 mois/an, min 15 jours. Non rémunéré (sauf allocations familiales). N+1 puis RH.',
            ],

            // ── Congé pour concours ────────────────────────────────────────────────
            [
                'nom'                  => 'Congé pour concours',
                'jours_max'            => 1,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — Max 1 jour/an pour préparation. Rémunéré. Sur autorisation administrative.',
            ],

            // ── Congé d'éducation / formation syndicale ───────────────────────────
            [
                'nom'                  => 'Congé d\'éducation et formation syndicale',
                'jours_max'            => 0, // Variable selon la durée du stage/séminaire
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'CCN art. 77 — Séminaire syndical, école syndicale ou formation agréée. Rémunéré. Validation RH.',
            ],

            // ── Congés sans solde / sabbatique ────────────────────────────────────
            [
                'nom'                  => 'Congé sans solde',
                'jours_max'            => 90,
                'necessite_n1'         => true,
                'necessite_rh'         => true,
                'necessite_dg'         => true,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'Non rémunéré — N+1, RH puis DG.',
            ],
            [
                'nom'                  => 'Congé sabbatique',
                'jours_max'            => 180,
                'necessite_n1'         => true,
                'necessite_rh'         => true,
                'necessite_dg'         => true,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'Non rémunéré — N+1, RH puis DG.',
            ],
        ];

        foreach ($types as $data) {
            TypeConge::updateOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
