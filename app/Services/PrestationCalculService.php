<?php

namespace App\Services;

use App\Enums\StatutAgent;
use App\Enums\StatutPositionConventionnelle;
use App\Enums\TypeAyantDroit;
use App\Enums\TypePositionConventionnelle;
use App\Enums\TypePrestation;
use App\Interfaces\AyantDroitInterface;
use App\Interfaces\PositionConventionnelleInterface;
use App\Interfaces\SalaireAgentInterface;
use App\Models\Agent;
use App\Models\AyantDroit;
use App\Models\PositionConventionnelle;
use App\Models\SalaireAgent;
use Carbon\CarbonInterface;

class PrestationCalculService
{
    public function __construct(
        private readonly PaieCalculService $calcul,
        private readonly SalaireAgentInterface $salaireAgentRepository,
        private readonly PositionConventionnelleInterface $positionRepository,
        private readonly AyantDroitInterface $ayantDroitRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function simuler(
        TypePrestation $type,
        Agent $agent,
        CarbonInterface $dateFait,
        bool $transportCorps = false,
        ?int $montantDemande = null,
    ): array {
        $anciennete = $this->anciennete($agent, $dateFait);
        $salaire = $this->salaireReference($agent);
        $base = $salaire !== null ? (int) round((float) $salaire->montant_base) : 0;
        $primeAnciennete = $base > 0
            ? $this->calcul->montantAnciennete($base, $anciennete['annees'])
            : 0;
        $traitementBrut = $base + $primeAnciennete;
        $nbEnfants = $this->nbEnfantsACharge($agent, $dateFait);

        $nbMois = $type->nbMoisBareme($anciennete['annees']);
        $montant = $this->montant($type, $traitementBrut, $nbMois, $nbEnfants, $montantDemande);

        return [
            'type' => $type->value,
            'article_ccn' => $type->articleCcn(),
            'montant' => $montant,
            'base' => $base,
            'prime_anciennete' => $primeAnciennete,
            'traitement_brut' => $traitementBrut,
            'annees_anciennete' => $anciennete['annees'],
            'jours_deduits' => $anciennete['jours_deduits'],
            'nb_mois_bareme' => $nbMois,
            'nb_enfants_a_charge' => $nbEnfants,
            'transport_corps' => $transportCorps,
            'plafond_funeraires' => TypePrestation::PLAFOND_FUNERAIRES,
        ];
    }

    /**
     * @return array{annees: int, jours_deduits: int}
     */
    public function anciennete(Agent $agent, CarbonInterface $dateFait): array
    {
        $prise = $agent->date_prise_service;
        abort_unless(
            $prise !== null,
            422,
            'La date de prise de service est requise pour calculer l\'ancienneté (CCN art. 119 / 121).'
        );

        $joursDeduits = $this->joursDetachementDisponibilite($agent, $prise, $dateFait);
        $priseEffective = $prise->copy()->startOfDay()->addDays($joursDeduits);

        return [
            'annees' => $this->calcul->anneesRevolues($priseEffective, $dateFait),
            'jours_deduits' => $joursDeduits,
        ];
    }

    public function salaireReference(Agent $agent): ?SalaireAgent
    {
        return $this->salaireAgentRepository->getActuel((int) $agent->id)
            ?? $this->salaireAgentRepository->getHistoriqueByAgent((int) $agent->id)->last();
    }

    public function nbEnfantsACharge(Agent $agent, CarbonInterface $dateFait): int
    {
        return $this->ayantDroitRepository->getByAgent((int) $agent->id)
            ->filter(fn (AyantDroit $item) => $item->type === TypeAyantDroit::ENFANT && $item->estACharge($dateFait))
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    public function assertEligible(
        TypePrestation $type,
        Agent $agent,
        CarbonInterface $dateFait,
        bool $transportCorps = false,
        ?int $montantDemande = null,
    ): array {
        if ($type->bloqueSiEssai()) {
            $contrat = $agent->contratActif;
            abort_if(
                $contrat?->essaiEstOuvert() === true,
                422,
                'Cette prestation ne s\'applique pas pendant la période d\'essai (CCN art. 121).'
            );
        }

        if ($type->reserveRetraite()) {
            abort_unless(
                in_array((string) $agent->statut, [StatutAgent::RETRAITE->value, StatutAgent::ARCHIVE->value], true),
                422,
                'L\'allocation décès de l\'article 120 est réservée aux ayants droit d\'un salarié retraité.'
            );
        }

        $snapshot = $this->simuler($type, $agent, $dateFait, $transportCorps, $montantDemande);

        if ($type->exigeSalaire()) {
            abort_if(
                $snapshot['base'] <= 0,
                422,
                'Aucun salaire de référence pour calculer cette prestation.'
            );
        }

        if ($type === TypePrestation::INDEMNITE_RETRAITE) {
            abort_if(
                $snapshot['nb_mois_bareme'] === null,
                422,
                'L\'indemnité d\'admission à la retraite exige au moins cinq ans d\'ancienneté (CCN art. 119).'
            );
        }

        if ($type === TypePrestation::PRIME_ENFANTS_DECES) {
            abort_if(
                $snapshot['nb_enfants_a_charge'] < 1,
                422,
                'Aucun enfant à charge au moment du décès (CCN art. 121).'
            );
        }

        return $snapshot;
    }

    private function montant(
        TypePrestation $type,
        int $traitementBrut,
        ?int $nbMois,
        int $nbEnfants,
        ?int $montantDemande,
    ): int {
        return match ($type) {
            TypePrestation::CAPITAL_DECES => $this->calcul->arrondirFcfa($traitementBrut * (int) $nbMois),
            TypePrestation::INDEMNITE_RETRAITE => $nbMois === null
                ? 0
                : $this->calcul->arrondirFcfa($traitementBrut * $nbMois),
            TypePrestation::PRIME_ENFANTS_DECES => $this->calcul->arrondirFcfa(
                TypePrestation::PRIME_ENFANT_FCFA * $nbEnfants
            ),
            TypePrestation::ALLOCATION_DECES_RETRAITE => TypePrestation::ALLOCATION_DECES_RETRAITE_FCFA,
            TypePrestation::FRAIS_FUNERAIRES => $this->calcul->arrondirFcfa((float) ($montantDemande ?? 0)),
        };
    }

    private function joursDetachementDisponibilite(
        Agent $agent,
        CarbonInterface $prise,
        CarbonInterface $dateFait,
    ): int {
        $debutRef = $prise->copy()->startOfDay();
        $finRef = $dateFait->copy()->startOfDay();

        return $this->positionRepository->parAgent((int) $agent->id)
            ->filter(function (PositionConventionnelle $position) {
                $type = $position->type;
                $statut = $position->statut;

                return $type instanceof TypePositionConventionnelle
                    && $type->coupeRemuneration()
                    && in_array($statut, [
                        StatutPositionConventionnelle::ACTIVE,
                        StatutPositionConventionnelle::CLOTUREE,
                    ], true);
            })
            ->sum(function (PositionConventionnelle $position) use ($debutRef, $finRef) {
                $debut = $position->date_debut?->copy()->startOfDay() ?? $debutRef;
                $fin = ($position->date_fin?->copy()->startOfDay() ?? $finRef);

                if ($debut->lt($debutRef)) {
                    $debut = $debutRef->copy();
                }
                if ($fin->gt($finRef)) {
                    $fin = $finRef->copy();
                }
                if ($fin->lt($debut)) {
                    return 0;
                }

                return (int) $debut->diffInDays($fin);
            });
    }
}
