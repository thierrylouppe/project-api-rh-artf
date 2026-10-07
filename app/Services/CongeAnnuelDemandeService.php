<?php

namespace App\Services;

use App\Enums\OrigineDemandeCongeAnnuel;
use App\Enums\StatutCampagneCongeAnnuel;
use App\Enums\StatutDemandeConge;
use App\Interfaces\AgentInterface;
use App\Interfaces\CampagneCongeAnnuelInterface;
use App\Interfaces\DemandeCongeInterface;
use App\Interfaces\TypeCongeInterface;
use App\Models\Agent;
use App\Models\CampagneCongeAnnuel;
use App\Models\DemandeConge;
use App\Models\TypeConge;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/** @property DemandeCongeInterface $repository */
class CongeAnnuelDemandeService extends BaseService
{
    public function __construct(
        DemandeCongeInterface $repository,
        private readonly DemandeCongeService $demandeCongeService,
        private readonly CampagneCongeAnnuelInterface $campagneRepository,
        private readonly TypeCongeInterface $typeCongeRepository,
        private readonly AgentInterface $agentRepository,
        private readonly CongeSoldeService $congeSoldeService,
        private readonly JourFerieService $jourFerieService,
    ) {
        parent::__construct($repository);
    }

    public function getAll(array $filters = []): Collection
    {
        $filters['type_conge_id'] = $this->typeAnnuel()->id;

        return $this->repository->getAll($filters)
            ->filter(fn (DemandeConge $demande) => $demande->origine !== null)
            ->values();
    }

    public function deposer(array $data): DemandeConge
    {
        $type    = $this->typeAnnuel();
        $agent   = $this->agentRepository->findById((int) $data['agent_id']);
        $origine = OrigineDemandeCongeAnnuel::tryFrom((string) ($data['origine'] ?? OrigineDemandeCongeAnnuel::CAMPAGNE->value))
            ?? OrigineDemandeCongeAnnuel::CAMPAGNE;
        $annee   = (int) substr((string) $data['date_debut'], 0, 4);

        $campagne = $this->campagneRepository->findByAnnee($annee);
        abort_unless(
            $campagne instanceof CampagneCongeAnnuel,
            422,
            "Aucune campagne de congé annuel pour {$annee}."
        );

        if ($origine === OrigineDemandeCongeAnnuel::APRES_CLOTURE) {
            $this->assertDroitApresCloture($agent, $campagne, (string) $data['date_debut']);
        } else {
            abort_unless(
                $campagne->statut === StatutCampagneCongeAnnuel::OUVERTE,
                422,
                'Les propositions de la campagne ne sont possibles que pendant la période d\'ouverture.'
            );
            $origine = OrigineDemandeCongeAnnuel::CAMPAGNE;
        }

        $solde = $this->congeSoldeService->getOrCreate((int) $agent->id, (int) $type->id, $annee);
        $jours = (int) floor((float) $solde->solde_actuel);
        $echeance = $this->jourFerieService->calculerEcheance((string) $data['date_debut'], $jours);

        unset($data['date_fin']);
        $data['date_fin']                 = $echeance['date_fin'];
        $data['date_reprise']             = $echeance['date_reprise'];
        $data['type_conge_id']            = $type->id;
        $data['campagne_conge_annuel_id'] = $campagne->id;
        $data['origine']                  = $origine->value;

        return $this->demandeCongeService->create($data);
    }

    public function consulter(int $id): DemandeConge
    {
        return $this->demandeAnnuelle($id);
    }

    public function aValider(): Collection
    {
        return $this->demandeCongeService->aValider()
            ->filter(function (DemandeConge $demande) {
                if (! $this->estCongeAnnuel($demande) || $demande->origine === null) {
                    return false;
                }

                if ($demande->origine === OrigineDemandeCongeAnnuel::CAMPAGNE && $demande->statut === StatutDemandeConge::SOUMISE) {
                    $demande->loadMissing('campagne');

                    return $demande->campagne?->statut === StatutCampagneCongeAnnuel::CLOTUREE;
                }

                return true;
            })
            ->values();
    }

    public function validerN1(int $id, ?string $commentaire = null): DemandeConge
    {
        $this->assertVisaApresCloture($id);

        return $this->demandeCongeService->validerN1($id, $commentaire);
    }

    public function rejeterN1(int $id, string $commentaire): DemandeConge
    {
        $this->assertVisaApresCloture($id);

        return $this->demandeCongeService->rejeterN1($id, $commentaire);
    }

    public function validerRH(int $id, ?string $commentaire = null): DemandeConge
    {
        $this->assertVisaApresCloture($id);

        return $this->demandeCongeService->validerRH($id, $commentaire);
    }

    public function rejeterRH(int $id, string $commentaire): DemandeConge
    {
        $this->assertVisaApresCloture($id);

        return $this->demandeCongeService->rejeterRH($id, $commentaire);
    }

    public function annuler(int $id): DemandeConge
    {
        $demande = $this->demandeAnnuelle($id);

        if ($demande->origine === OrigineDemandeCongeAnnuel::CAMPAGNE) {
            $demande->loadMissing('campagne');
            abort_unless(
                $demande->campagne?->statut === StatutCampagneCongeAnnuel::OUVERTE,
                422,
                'Une proposition de campagne ne peut être annulée qu\'avant la clôture.'
            );
        }

        return $this->demandeCongeService->annuler($id);
    }

    public function solde(int $agentId, ?int $annee = null): \App\Models\CongeSolde
    {
        $annee ??= (int) now()->year;

        return $this->congeSoldeService->getOrCreate($agentId, (int) $this->typeAnnuel()->id, $annee);
    }

    public function statistiques(array $filters = []): array
    {
        $items = $this->getAll($filters);

        $parStatut = [];
        foreach (StatutDemandeConge::cases() as $statut) {
            $parStatut[$statut->value] = $items->where('statut', $statut)->count();
        }

        $accordees = $items->filter(function (DemandeConge $demande) {
            $demande->loadMissing('typeConge');

            return $demande->typeConge?->estAccordee($demande->statut) ?? false;
        });

        return [
            'total'          => $items->count(),
            'par_statut'     => $parStatut,
            'jours_accordes' => $accordees->sum('nb_jours'),
        ];
    }

    public function fichePdf(int $id): Response
    {
        $this->demandeAnnuelle($id);

        return $this->demandeCongeService->fichePdf($id);
    }

    public function attestationPdf(int $id): Response
    {
        $this->demandeAnnuelle($id);

        return $this->demandeCongeService->attestationPdf($id);
    }

    private function assertDroitApresCloture(Agent $agent, CampagneCongeAnnuel $campagne, string $dateDebut): void
    {
        abort_unless(
            $campagne->statut === StatutCampagneCongeAnnuel::CLOTUREE,
            422,
            'Le dépôt après clôture n\'est possible que lorsque la campagne de l\'année est clôturée.'
        );
        abort_unless(
            $agent->date_prise_service !== null,
            422,
            'La date de prise de service est absente du dossier agent.'
        );

        $dateCloture = ($campagne->date_cloture_effective ?? $campagne->date_cloture)->copy()->startOfDay();
        $mois        = (int) $agent->date_prise_service->copy()->startOfDay()->diffInMonths($dateCloture);
        abort_unless(
            $mois < 12,
            422,
            'Ce circuit est réservé à l\'agent qui n\'avait pas 12 mois de service à la clôture de la campagne.'
        );

        $anniversaire = $agent->date_prise_service->copy()->startOfDay()->addMonths(12);
        abort_unless(
            Carbon::parse($dateDebut)->startOfDay()->greaterThanOrEqualTo($anniversaire),
            422,
            'La date de début doit être au plus tôt le jour où l\'agent atteint 12 mois de service ('.$anniversaire->format('Y-m-d').').'
        );
    }

    private function assertVisaApresCloture(int $id): void
    {
        $demande = $this->demandeAnnuelle($id);
        if ($demande->origine !== OrigineDemandeCongeAnnuel::CAMPAGNE) {
            return;
        }

        $demande->loadMissing('campagne');
        abort_unless(
            $demande->campagne?->statut === StatutCampagneCongeAnnuel::CLOTUREE,
            422,
            'Le traitement d\'une proposition de campagne commence après la clôture.'
        );
    }

    private function demandeAnnuelle(int $id): DemandeConge
    {
        /** @var DemandeConge $demande */
        $demande = $this->repository->findById($id);
        $demande->load(['agent', 'typeConge', 'campagne']);

        abort_unless(
            $this->estCongeAnnuel($demande) && $demande->origine !== null,
            422,
            'Cette demande ne relève pas du circuit congé annuel.'
        );

        return $demande;
    }

    private function estCongeAnnuel(DemandeConge $demande): bool
    {
        $demande->loadMissing('typeConge');

        return str_starts_with(strtolower((string) $demande->typeConge?->nom), 'congé annuel');
    }

    private function typeAnnuel(): TypeConge
    {
        $type = $this->typeCongeRepository->getAll(['nom' => 'Congé annuel'])->first();
        abort_unless($type instanceof TypeConge, 422, 'Le type Congé annuel est introuvable.');

        return $type;
    }
}
