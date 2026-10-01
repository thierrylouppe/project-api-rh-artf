<?php

namespace App\Services;

use App\Enums\TypeAyantDroit;
use App\Interfaces\AffiliationSocialeInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\AyantDroitInterface;
use App\Models\AyantDroit;

class DossierSocialService
{
    public function __construct(
        private readonly AgentInterface $agentRepository,
        private readonly AffiliationSocialeInterface $affiliationRepository,
        private readonly AyantDroitInterface $ayantDroitRepository,
    ) {}

    public function getDossier(int $agentId): array
    {
        $agent = $this->agentRepository->findById($agentId);
        $affiliations = $this->affiliationRepository->getByAgent($agentId);
        $ayantsDroit = $this->ayantDroitRepository->getByAgent($agentId);

        $enfantsACharge = $ayantsDroit->filter(
            fn (AyantDroit $item) => $item->type === TypeAyantDroit::ENFANT && $item->estACharge()
        );
        $enfantsArbreNoel = $ayantsDroit->filter(
            fn (AyantDroit $item) => $item->estEligibleArbreNoel()
        );
        $conjoint = $ayantsDroit->first(
            fn (AyantDroit $item) => $item->type === TypeAyantDroit::CONJOINT && $item->estACharge()
        );

        return [
            'agent' => [
                'id' => $agent->id,
                'matricule' => $agent->matricule,
                'nom' => $agent->nom,
                'prenom' => $agent->prenom,
                'nom_complet' => $agent->nom_complet,
                'numero_cnss' => $agent->numero_cnss,
                'statut' => $agent->statut,
            ],
            'affiliations' => $affiliations,
            'ayants_droit' => $ayantsDroit,
            'synthese' => [
                'affiliation_cnss' => $affiliations->contains(
                    fn ($affiliation) => $affiliation->estActive() && $affiliation->organisme?->estSysteme()
                ) || $affiliations->contains(
                    fn ($affiliation) => $affiliation->estActive()
                        && $affiliation->organisme?->type?->value === 'cnss'
                ),
                'nb_enfants_a_charge' => $enfantsACharge->count(),
                'nb_enfants_arbre_noel' => min(3, $enfantsArbreNoel->count()),
                'prime_arbre_noel_forfaitaire' => $enfantsArbreNoel->isEmpty(),
                'nb_enfants_tutelle' => $ayantsDroit->filter(
                    fn (AyantDroit $item) => $item->type === TypeAyantDroit::ENFANT
                        && $item->actif
                        && $item->lien_juridique?->value === 'tutelle'
                )->count(),
                'a_conjoint_a_charge' => $conjoint !== null,
            ],
        ];
    }
}
