<?php

namespace App\Services;

use App\Enums\StatutEssai;
use App\Interfaces\AgentInterface;
use App\Interfaces\ContratInterface;
use App\Interfaces\DossierIntegrationInterface;
use App\Interfaces\TypeContratInterface;
use App\Models\Agent;
use App\Models\Contrat;
use App\Models\DossierIntegration;
use App\Models\TypeContrat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContratService extends BaseService
{
    private const SIGLES_ESSAI = ['CDI', 'CDD'];

    public function __construct(
        private readonly ContratInterface $contratRepository,
        private readonly SalaireAgentService $salaireAgentService,
        private readonly TypeContratInterface $typeContratRepository,
        private readonly AgentInterface $agentRepository,
        private readonly DossierIntegrationInterface $dossierRepository,
        private readonly JourFerieService $jourFerieService,
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct($contratRepository);
    }

    public function create(array $data): Model
    {
        return DB::transaction(fn () => parent::create($data));
    }

    protected function beforeCreate(array $data): array
    {
        $data['statut'] = $data['statut'] ?? 'actif';

        $type = $this->typeContratRepository->findById((int) $data['type_contrat_id']);
        $sigle = $type instanceof TypeContrat ? (string) $type->sigle : '';

        if (! in_array($sigle, self::SIGLES_ESSAI, true)) {
            $data['statut_essai']     = StatutEssai::NON_APPLICABLE->value;
            $data['essai_renouvele']  = false;
            $data['duree_essai_mois'] = null;
            $data['date_debut_essai'] = null;
            $data['date_fin_essai']   = null;

            return $data;
        }

        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        $duree = $this->dureeEssaiMois($agent);
        $debut = Carbon::parse($data['date_debut'])->startOfDay();

        $data['duree_essai_mois'] = $duree;
        $data['date_debut_essai'] = $debut->toDateString();
        $data['date_fin_essai']   = $debut->copy()->addMonths($duree)->subDay()->toDateString();
        $data['essai_renouvele']  = false;
        $data['statut_essai']     = StatutEssai::EN_COURS->value;

        return $data;
    }

    protected function afterCreate(Model $model): Model
    {
        /** @var Contrat $model */
        $model->loadMissing(['agent', 'typeContrat']);

        $echelonEssai = $model->essaiEstOuvert() ? 1 : null;

        $this->salaireAgentService->creerSalaireInitial(
            $model->agent,
            $model,
            $model->essaiEstOuvert() ? 'Période d\'essai art. 49 — traitement minimum de la classe.' : null,
            $echelonEssai,
        );

        return $this->chargerPourRessource($model->id);
    }

    /**
     * Durée d'essai CCN art. 49 / 50 : classes 1–4 = 1 mois, 5–6 = 2 mois, 7–10 = 3 mois.
     */
    public static function dureeEssaiMoisPourNiveau(int $niveau): int
    {
        if ($niveau < 1 || $niveau > 10) {
            throw ValidationException::withMessages([
                'grade_id' => 'Impossible de déterminer la durée d\'essai : classe CCN 1 à 10 requise.',
            ]);
        }

        return match (true) {
            $niveau <= 4 => 1,
            $niveau <= 6 => 2,
            default      => 3,
        };
    }

    public function dureeEssaiMois(Agent $agent): int
    {
        $agent->loadMissing('grade');

        return self::dureeEssaiMoisPourNiveau((int) ($agent->grade?->niveau ?? 0));
    }

    public function renouvelerEssai(int $id): Contrat
    {
        return DB::transaction(function () use ($id) {
            $contrat = $this->contratEssaiOuvert($id);

            if (! $contrat->peutRenouvelerEssai()) {
                throw ValidationException::withMessages([
                    'essai' => 'La période d\'essai ne peut être renouvelée qu\'une seule fois (art. 49).',
                ]);
            }

            $duree = (int) $contrat->duree_essai_mois;
            $fin   = $contrat->date_fin_essai->copy()->addMonths($duree);

            $this->contratRepository->update($id, [
                'date_fin_essai' => $fin->toDateString(),
                'essai_renouvele' => true,
                'statut_essai'    => StatutEssai::RENOUVELE->value,
            ]);

            $contrat = $this->chargerPourRessource($id);
            $this->notifierEssai(
                $contrat,
                'essai_renouvele',
                sprintf(
                    'La période d\'essai de %s a été renouvelée jusqu\'au %s (art. 49).',
                    $contrat->agent?->nom_complet ?? 'l\'agent',
                    $contrat->date_fin_essai?->format('d/m/Y') ?? '—'
                )
            );

            return $contrat;
        });
    }

    public function confirmerEssai(int $id): Contrat
    {
        return DB::transaction(function () use ($id) {
            $contrat = $this->contratEssaiOuvert($id);

            $this->salaireAgentService->appliquerEchelonCibleApresEssai(
                $contrat->agent,
                'Confirmation de la période d\'essai art. 49 — échelon prévu.'
            );

            $this->contratRepository->update($id, [
                'statut_essai'            => StatutEssai::CONCLUANT->value,
                'date_confirmation_essai' => now()->toDateString(),
            ]);

            $contrat = $this->chargerPourRessource($id);
            $this->notifierEssai(
                $contrat,
                'essai_concluant',
                sprintf(
                    'La période d\'essai de %s est concluante. L\'engagement est définitif (art. 49).',
                    $contrat->agent?->nom_complet ?? 'l\'agent'
                )
            );

            return $contrat;
        });
    }

    /**
     * @param  array{commentaire?: string|null}  $data
     */
    public function rompreEssai(int $id, array $data = []): Contrat
    {
        return DB::transaction(function () use ($id, $data) {
            $contrat = $this->contratEssaiOuvert($id);

            $this->salaireAgentService->cloturerActuel(
                (int) $contrat->agent_id,
                now()->toDateString(),
                'Rupture de la période d\'essai art. 49 — sans préavis ni indemnité.'
            );

            $this->contratRepository->update($id, [
                'statut'       => 'resilie',
                'statut_essai' => StatutEssai::ROMPU->value,
                'date_fin'     => now()->toDateString(),
            ]);

            $contrat = $this->chargerPourRessource($id);
            $commentaire = trim((string) ($data['commentaire'] ?? ''));
            $this->notifierEssai(
                $contrat,
                'essai_rompu',
                sprintf(
                    'Le contrat de %s a été rompu pendant la période d\'essai, sans préavis ni indemnité (art. 49).%s',
                    $contrat->agent?->nom_complet ?? 'l\'agent',
                    $commentaire !== '' ? ' '.$commentaire : ''
                )
            );

            return $contrat;
        });
    }

    /**
     * Art. 52 : contrat écrit ≤ 30 jours ouvrables après la prise de service.
     *
     * @return Collection<int, DossierIntegration>
     */
    public function alertesDelai30Jours(): Collection
    {
        return $this->dossierRepository->getIntegresNecessitantContrat()
            ->filter(function (DossierIntegration $dossier) {
                $agent = $dossier->agent;
                $debut = $agent?->date_prise_service;
                if ($debut === null) {
                    return false;
                }

                $fin = now()->startOfDay();
                if ($fin->lt($debut->copy()->startOfDay())) {
                    return false;
                }

                // Moins de 31 j. calendaires ⇒ impossible d’avoir > 30 j. ouvrables.
                if ($debut->copy()->startOfDay()->diffInDays($fin) + 1 <= 30) {
                    return false;
                }

                $jours = $this->jourFerieService->calculerJoursOuvrables(
                    $debut->toDateString(),
                    $fin->toDateString()
                );

                if ($jours <= 30) {
                    return false;
                }

                $dossier->setAttribute('jours_ouvrables', $jours);

                return true;
            })
            ->values();
    }

    public function notifierAlertesDelai30Jours(): int
    {
        $alertes = $this->alertesDelai30Jours();

        foreach ($alertes as $dossier) {
            $this->notificationService->notifierRole(
                'rh',
                'integration',
                'contrat_delai_depasse',
                sprintf(
                    'Aucun contrat CDI/CDD pour %s %d jours ouvrables après la prise de service (art. 52, délai 30 j).',
                    $dossier->agent?->nom_complet ?? 'un agent',
                    (int) $dossier->getAttribute('jours_ouvrables')
                ),
                [
                    'dossier_id'          => $dossier->id,
                    'agent_id'            => $dossier->agent_id,
                    'date_prise_service'  => $dossier->agent?->date_prise_service?->toDateString(),
                    'jours_ouvrables'     => $dossier->getAttribute('jours_ouvrables'),
                ]
            );
        }

        return $alertes->count();
    }

    public function notifierEssaisEcheance(int $jours): int
    {
        $date     = now()->addDays($jours)->toDateString();
        $contrats = $this->contratRepository->getEssaisEcheantLe($date);
        $count    = 0;

        foreach ($contrats as $contrat) {
            $message = $jours === 0
                ? sprintf(
                    'La période d\'essai de %s arrive à échéance aujourd\'hui (%s).',
                    $contrat->agent?->nom_complet ?? 'un agent',
                    $contrat->date_fin_essai?->format('d/m/Y') ?? '—'
                )
                : sprintf(
                    'La période d\'essai de %s arrive à échéance dans %d jour(s) (fin le %s).',
                    $contrat->agent?->nom_complet ?? 'un agent',
                    $jours,
                    $contrat->date_fin_essai?->format('d/m/Y') ?? '—'
                );

            $this->notifierEssai($contrat, 'essai_echeance', $message, ['jours' => $jours]);
            $count++;
        }

        return $count;
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->contratRepository->getByAgent($agentId);
    }

    public function getActif(int $agentId): ?Contrat
    {
        return $this->contratRepository->getActif($agentId);
    }

    public function resilier(int $id): Contrat
    {
        return $this->contratRepository->resilier($id);
    }

    public function chargerPourRessource(int $id): Contrat
    {
        /** @var Contrat $contrat */
        $contrat = $this->contratRepository->findById($id);
        $contrat->load([
            'agent.grade',
            'agent.categorie',
            'agent.echelon',
            'agent.fonction',
            'agent.situationFamiliale',
            'agent.affectationActive.structure',
            'typeContrat',
            'fonction',
        ]);

        return $contrat;
    }

    private function contratEssaiOuvert(int $id): Contrat
    {
        /** @var Contrat $contrat */
        $contrat = $this->contratRepository->findById($id);
        $contrat->loadMissing(['agent.echelon', 'typeContrat']);

        if ($contrat->statut !== 'actif') {
            throw ValidationException::withMessages([
                'statut' => 'Seul un contrat actif peut faire l\'objet d\'une action sur la période d\'essai.',
            ]);
        }

        if (! $contrat->essaiEstOuvert()) {
            throw ValidationException::withMessages([
                'essai' => 'Aucune période d\'essai en cours sur ce contrat.',
            ]);
        }

        return $contrat;
    }

    private function notifierEssai(Contrat $contrat, string $action, string $message, array $meta = []): void
    {
        $this->notificationService->notifierEvenementGroupe(
            $this->notificationService->destinatairesRoleEtAgent('rh', (int) $contrat->agent_id),
            'contrat',
            $action,
            $message,
            array_merge([
                'contrat_id' => $contrat->id,
                'agent_id'   => $contrat->agent_id,
            ], $meta)
        );
    }
}
