<?php

namespace App\Services;

use App\Enums\CodePaieElement;
use App\Enums\NatureArretSante;
use App\Enums\StatutAgent;
use App\Enums\TypeAyantDroit;
use App\Enums\TypePriseEnCharge;
use App\Enums\TypeStructureSanitaire;
use App\Interfaces\AyantDroitInterface;
use App\Interfaces\PaieElementInterface;
use App\Interfaces\StructureSanitaireInterface;
use App\Models\Agent;
use App\Models\AyantDroit;
use App\Models\StructureSanitaire;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SanteCalculService
{
    public function __construct(
        private readonly PrestationCalculService $prestationCalcul,
        private readonly PaieCalculService $calcul,
        private readonly PaieElementInterface $elementRepository,
        private readonly StructureSanitaireInterface $structureRepository,
        private readonly AyantDroitInterface $ayantDroitRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function simulerPriseEnCharge(
        TypePriseEnCharge $type,
        Agent $agent,
        StructureSanitaire $structure,
        CarbonInterface $dateSoins,
        ?int $montantFacture,
        ?int $ayantDroitId,
        bool $atMp = false,
        ?string $dateDebut = null,
        ?string $dateFin = null,
        ?string $lieu = null,
    ): array {
        $this->assertStructure($type, $structure);
        $this->assertPatient($type, $agent, $ayantDroitId, $dateSoins);

        if ($type->exigeMontantFacture()) {
            abort_if(
                $montantFacture === null || $montantFacture < 1,
                422,
                'Indiquez le montant de la facture.'
            );
        }

        if ($type->estEvacuation()) {
            $this->assertEvacuation($atMp, $dateDebut, $dateFin, $lieu);
        }

        $taux = $type->tauxEmployeur();
        $montant = $this->calcul->arrondirFcfa(((int) ($montantFacture ?? 0)) * $taux);

        return [
            'type' => $type->value,
            'article_ccn' => $type->articleCcn(),
            'taux_employeur' => $taux,
            'montant_facture' => $montantFacture,
            'montant' => $montant,
            'at_mp' => $atMp,
            'lieu' => $lieu,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function simulerArret(
        NatureArretSante $nature,
        Agent $agent,
        CarbonInterface $dateFait,
        ?CarbonInterface $dateNotification,
    ): array {
        abort_unless(
            in_array((string) $agent->statut, StatutAgent::effectifPresent(), true),
            422,
            'Les allocations maladie / accident sont réservées aux salariés en activité (CCN art. 128).'
        );

        $anciennete = $this->prestationCalcul->anciennete($agent, $dateFait);
        $salaire = $this->prestationCalcul->salaireReference($agent);
        abort_if(
            $salaire === null || (float) $salaire->montant_base <= 0,
            422,
            'Aucun salaire de référence pour calculer l\'allocation.'
        );

        $base = (int) round((float) $salaire->montant_base);
        $primeAnciennete = $this->calcul->montantAnciennete($base, $anciennete['annees']);
        $nbEnfants = $this->prestationCalcul->nbEnfantsACharge($agent, $dateFait);
        $af = $this->montantAllocationsFamiliales($nbEnfants);

        $nbMoisBareme = $nature->nbMoisBareme($anciennete['annees']);
        abort_if(
            $nbMoisBareme === null,
            422,
            'L\'allocation maladie / AT exige au moins un an d\'ancienneté (CCN art. 132).'
        );

        $majoration = $nature->nbMoisMajoration($anciennete['annees'], $nbEnfants);
        $nbMois = $nbMoisBareme + $majoration;

        $plein = $this->calcul->arrondirFcfa($base + $primeAnciennete + $af);
        $demi = $this->calcul->arrondirFcfa((int) round($base / 2) + $primeAnciennete + $af);

        $alerte72h = false;
        if ($dateNotification !== null) {
            $alerte72h = $dateFait->copy()->startOfDay()->diffInHours($dateNotification->copy()->startOfDay()) > 72;
        }

        return [
            'nature' => $nature->value,
            'article_ccn' => $nature->articleCcn(),
            'base' => $base,
            'prime_anciennete' => $primeAnciennete,
            'allocations_familiales' => $af,
            'montant_mensuel' => $nature === NatureArretSante::ACCIDENT_NON_PROFESSIONNEL ? $plein : $plein,
            'montant_mensuel_demi' => $nature === NatureArretSante::ACCIDENT_NON_PROFESSIONNEL ? $demi : null,
            'nb_mois_bareme' => $nbMoisBareme,
            'nb_mois_majoration' => $majoration,
            'nb_mois' => $nbMois,
            'annees_anciennete' => $anciennete['annees'],
            'nb_enfants_a_charge' => $nbEnfants,
            'alerte_72h' => $alerte72h,
        ];
    }

    private function assertStructure(TypePriseEnCharge $type, StructureSanitaire $structure): void
    {
        abort_unless($structure->actif, 422, 'Cette structure sanitaire n\'est plus agréée.');

        $types = array_map(fn (TypeStructureSanitaire $item) => $item->value, $type->typesStructure());
        abort_unless(
            in_array($structure->type?->value, $types, true),
            422,
            'Cette structure n\'est pas agréée pour ce type de prise en charge.'
        );
    }

    private function assertPatient(
        TypePriseEnCharge $type,
        Agent $agent,
        ?int $ayantDroitId,
        CarbonInterface $dateSoins,
    ): void {
        if ($type->patientAgentUniquement()) {
            abort_if(
                $ayantDroitId !== null,
                422,
                'Le remboursement des verres correcteurs est réservé au salarié (CCN art. 124).'
            );

            return;
        }

        if ($ayantDroitId === null) {
            return;
        }

        $ayant = $this->ayantDroitRepository->findById($ayantDroitId);
        abort_unless(
            $ayant instanceof AyantDroit && (int) $ayant->agent_id === (int) $agent->id,
            422,
            'L\'ayant droit indiqué n\'appartient pas à cet agent.'
        );
        abort_unless(
            $ayant->estACharge($dateSoins),
            422,
            'L\'ayant droit n\'est pas à charge à la date des soins.'
        );
        abort_unless(
            in_array($ayant->type, [TypeAyantDroit::CONJOINT, TypeAyantDroit::ENFANT], true),
            422,
            'Seuls le conjoint et les enfants à charge sont pris en charge.'
        );
    }

    private function assertEvacuation(bool $atMp, ?string $dateDebut, ?string $dateFin, ?string $lieu): void
    {
        abort_if(blank($dateDebut) || blank($dateFin), 422, 'Indiquez les dates d\'évacuation.');
        abort_if(blank($lieu), 422, 'Indiquez le lieu d\'évacuation.');

        $debut = Carbon::parse($dateDebut)->startOfDay();
        $fin = Carbon::parse($dateFin)->startOfDay();
        abort_if($fin->lt($debut), 422, 'La date de fin d\'évacuation est antérieure au début.');

        $mois = (int) $debut->diffInMonths($fin);
        abort_if(
            $mois > 6 && ! $atMp,
            422,
            'La durée de prise en charge d\'une évacuation ne peut excéder six mois, sauf accident du travail ou maladie professionnelle (CCN art. 127).'
        );
    }

    private function montantAllocationsFamiliales(int $nbEnfants): int
    {
        if ($nbEnfants < 1) {
            return 0;
        }

        $element = $this->elementRepository->findByCode(CodePaieElement::ALLOCATIONS_FAMILIALES->value);
        $unitaire = $element?->montant_defaut !== null ? (float) $element->montant_defaut : 0;

        return $this->calcul->montantAllocationsFamiliales($unitaire, $nbEnfants);
    }
}
