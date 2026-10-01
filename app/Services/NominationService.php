<?php

namespace App\Services;

use App\Enums\StatutEssai;
use App\Enums\StatutNomination;
use App\Enums\TypeActeNomination;
use App\Enums\TypeChangementSalaireAgent;
use App\Interfaces\AffectationInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\ClassegrillesalarialeInterface;
use App\Interfaces\HistoriqueIntegrationInterface;
use App\Interfaces\NominationInterface;
use App\Interfaces\UserInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Models\Bureau;
use App\Models\Classegrillesalariale;
use App\Models\Direction;
use App\Models\Fonction;
use App\Models\Nomination;
use App\Models\Service;
use App\Models\User;
use App\Notifications\NominationEvenementNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** @property NominationInterface $repository */
class NominationService extends BaseService
{
    /** @var array<int, Classegrillesalariale> */
    private array $classesParId = [];

    public function __construct(
        NominationInterface $repository,
        private readonly ValidationWorkflowInterface $workflowRepository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
        private readonly AffectationInterface $affectationRepository,
        private readonly AgentInterface $agentRepository,
        private readonly UserInterface $userRepository,
        private readonly ClassegrillesalarialeInterface $classeRepository,
        private readonly SalaireAgentService $salaireAgentService,
    ) {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['statut']     = StatutNomination::EN_ATTENTE;

        return $this->preparerDonneesEssai($data);
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $nomination = $this->repository->findById($id);

        abort_unless(
            $nomination->statut === StatutNomination::EN_ATTENTE,
            422,
            'Seule une nomination en attente de validation peut être modifiée.'
        );

        abort_if(
            $nomination->lot_nomination_id,
            422,
            'Cette nomination appartient à un lot : elle ne peut pas être modifiée isolément.'
        );

        unset($data['statut'], $data['created_by']);

        if (array_key_exists('soumis_a_essai', $data) || array_key_exists('classegrillesalariale_id', $data)) {
            $data['agent_id'] = $data['agent_id'] ?? $nomination->agent_id;
            $data = $this->preparerDonneesEssai($data);
        }

        return $data;
    }

    protected function afterCreate($model): Nomination
    {
        $this->workflowRepository->initialiserCircuit(Nomination::class, $model->id);

        $this->historiqueRepository->enregistrer(
            Nomination::class,
            $model->id,
            Auth::id(),
            'nomination_creee',
            null,
            $model->toArray(),
            null
        );

        $this->notifier($model, 'creee');

        return $model;
    }

    public function approuver(int $id): Nomination
    {
        return DB::transaction(function () use ($id) {
            $nomination = $this->repository->findById($id);

            abort_unless(
                $nomination->statut->peutTransitionnerVers(StatutNomination::APPROUVEE),
                422,
                "La nomination ne peut pas être approuvée depuis le statut « {$nomination->statut->label()} »."
            );

            $ancienStatut = $nomination->statut;
            $nomination->update(['statut' => StatutNomination::APPROUVEE]);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_approuvee',
                ['statut' => $ancienStatut->value],
                ['statut' => StatutNomination::APPROUVEE->value],
                null
            );

            $nomination = $nomination->fresh();
            $this->notifier($nomination, 'approuvee');

            return $nomination;
        });
    }

    public function activer(int $id): Nomination
    {
        return DB::transaction(function () use ($id) {
            $nomination = $this->repository->findById($id);

            abort_if(
                $nomination->lot_nomination_id,
                422,
                'Cette nomination appartient à un lot : activez le lot, pas la ligne.'
            );

            return $this->executerActivation($nomination);
        });
    }

    public function activerLigneDeLot(int $id): Nomination
    {
        $nomination = $this->repository->findById($id);

        abort_unless(
            $nomination->lot_nomination_id,
            422,
            'Cette nomination n\'appartient pas à un lot.'
        );

        return $this->executerActivation($nomination);
    }

    /**
     * Art. 50 : option essai si classe cible > classe actuelle.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function preparerDonneesEssai(array $data): array
    {
        $soumis = filter_var($data['soumis_a_essai'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['soumis_a_essai'] = $soumis;

        if (! $soumis) {
            unset($data['classegrillesalariale_id']);

            return $data;
        }

        $classeId = $data['classegrillesalariale_id'] ?? null;
        if ($classeId === null || $classeId === '') {
            throw ValidationException::withMessages([
                'classegrillesalariale_id' => 'La classe cible est obligatoire pour un essai d\'emploi supérieur (art. 50).',
            ]);
        }

        $classe = $this->classeAvecGrade((int) $classeId);
        $niveauCible = (int) ($classe->grade?->niveau ?? 0);

        $agent = $this->agentRepository->findAvecGrade((int) $data['agent_id']);
        $niveauActuel = (int) ($agent->grade?->niveau ?? 0);

        if ($niveauActuel < 1) {
            throw ValidationException::withMessages([
                'soumis_a_essai' => 'L\'agent doit avoir une classe CCN pour un essai d\'emploi supérieur (art. 50).',
            ]);
        }

        if ($niveauCible <= $niveauActuel) {
            throw ValidationException::withMessages([
                'classegrillesalariale_id' => 'La classe cible doit être supérieure à la classe actuelle de l\'agent (art. 50).',
            ]);
        }

        $data['duree_essai_mois'] = ContratService::dureeEssaiMoisPourNiveau($niveauCible);

        return $data;
    }

    private function executerActivation(Nomination $nomination): Nomination
    {
        $id = (int) $nomination->id;

        abort_unless(
            $nomination->statut->peutTransitionnerVers(StatutNomination::ACTIVE),
            422,
            "La nomination ne peut être activée que depuis le statut « Approuvée ». Statut actuel : « {$nomination->statut->label()} »."
        );

        $champsEssai = $this->preparerActivationEssai($nomination);

        $this->repository->cloturerActivesPourStructure(
            $nomination->structurable_type,
            $nomination->structurable_id,
            $id
        );
        $this->repository->cloturerActivePourAgent($nomination->agent_id, $id);

        abort_if(
            $this->repository->getActive($nomination->agent_id) !== null,
            422,
            'Cet agent a déjà une nomination active.'
        );

        $nomination->update(array_merge([
            'statut'     => StatutNomination::ACTIVE,
            'date_debut' => $nomination->date_debut ?? now()->toDateString(),
        ], $champsEssai));

        $this->appliquerSalaireEssai($nomination);

        $this->historiqueRepository->enregistrer(
            Nomination::class,
            $id,
            Auth::id(),
            'nomination_activee',
            ['statut' => StatutNomination::APPROUVEE->value],
            ['statut' => StatutNomination::ACTIVE->value, 'poste' => $nomination->poste],
            null
        );

        $this->notifier($nomination, 'activee');

        return $nomination;
    }

    public function cloturer(int $id, ?string $dateFin = null): Nomination
    {
        return DB::transaction(function () use ($id, $dateFin) {
            $nomination = $this->repository->findById($id);

            abort_unless(
                $nomination->statut->peutTransitionnerVers(StatutNomination::CLOTUREE),
                422,
                "La nomination ne peut être clôturée que depuis le statut « Active »."
            );

            $cloturee = $this->repository->cloturer($id, $dateFin);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_cloturee',
                ['statut' => StatutNomination::ACTIVE->value],
                ['statut' => StatutNomination::CLOTUREE->value],
                null
            );

            return $cloturee;
        });
    }

    public function confirmerEssai(int $id): Nomination
    {
        return DB::transaction(function () use ($id) {
            $nomination = $this->essaiNominationOuvert($id);

            $nomination->update([
                'essai_statut'             => StatutEssai::CONCLUANT,
                'date_confirmation_essai'  => now()->toDateString(),
            ]);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_essai_concluant',
                ['essai_statut' => StatutEssai::EN_COURS->value],
                ['essai_statut' => StatutEssai::CONCLUANT->value],
                null
            );

            $this->notifier($nomination, 'essai_concluant');

            return $nomination;
        });
    }

    public function rompreEssai(int $id, ?string $commentaire = null): Nomination
    {
        return DB::transaction(function () use ($id, $commentaire) {
            $nomination = $this->essaiNominationOuvert($id);
            $snapshot   = $nomination->snapshot_carriere ?? [];
            $agentId    = (int) $nomination->agent_id;

            $this->agentRepository->update($agentId, [
                'fonction_id' => $snapshot['fonction_id'] ?? null,
            ]);

            $this->restaurerSalaireDepuisSnapshot($nomination);

            $nomination->update([
                'essai_statut' => StatutEssai::ROMPU,
                'statut'       => StatutNomination::CLOTUREE,
                'date_fin'     => now()->toDateString(),
            ]);

            if ($nomination->nomination_precedente_id) {
                $this->repository->reactiver((int) $nomination->nomination_precedente_id);
            }

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_essai_rompu',
                ['essai_statut' => StatutEssai::EN_COURS->value],
                ['essai_statut' => StatutEssai::ROMPU->value],
                $commentaire
            );

            $this->notifier($nomination, 'essai_rompu');

            return $nomination;
        });
    }

    private function essaiNominationOuvert(int $id): Nomination
    {
        $nomination = $this->repository->findById($id);

        abort_unless(
            $nomination->statut === StatutNomination::ACTIVE,
            422,
            'L\'essai d\'emploi supérieur ne peut être tranché que sur une nomination active.'
        );

        abort_unless(
            $nomination->soumis_a_essai && $nomination->essai_statut?->estOuvert(),
            422,
            'Cette nomination n\'a pas d\'essai d\'emploi supérieur en cours (art. 50).'
        );

        return $nomination;
    }

    /** @return array<string, mixed> */
    private function preparerActivationEssai(Nomination $nomination): array
    {
        if (! $nomination->soumis_a_essai) {
            return [];
        }

        $agent = $this->agentRepository->findById((int) $nomination->agent_id);
        $salaire = $this->salaireAgentService->getActuel((int) $agent->id);
        $duree = (int) ($nomination->duree_essai_mois ?? 0);
        if ($duree < 1) {
            $classe = $this->classeAvecGrade((int) $nomination->classegrillesalariale_id);
            $duree  = ContratService::dureeEssaiMoisPourNiveau((int) ($classe->grade?->niveau ?? 0));
        }

        $debut      = Carbon::parse($nomination->date_debut ?? now())->startOfDay();
        $precedente = $this->repository->getActive((int) $nomination->agent_id);
        $modifiable = $salaire !== null && ! Fonction::estNomHorsGrille($nomination->poste);

        return [
            'nomination_precedente_id' => $precedente?->id,
            'snapshot_carriere'        => [
                'fonction_id'              => $agent->fonction_id,
                'grade_id'                 => $agent->grade_id,
                'categorie_id'             => $agent->categorie_id,
                'echelon_id'               => $agent->echelon_id,
                'classegrillesalariale_id' => $salaire?->classegrillesalariale_id,
                'echelon'                  => $salaire?->echelon,
                'salaire_agent_id'         => $salaire?->id,
                'salaire_modifiable'       => $modifiable,
            ],
            'essai_statut'             => StatutEssai::EN_COURS,
            'duree_essai_mois'         => $duree,
            'date_debut_essai'         => $debut->toDateString(),
            'date_fin_essai'           => $debut->copy()->addMonths($duree)->subDay()->toDateString(),
        ];
    }

    private function appliquerSalaireEssai(Nomination $nomination): void
    {
        if (! $nomination->soumis_a_essai || $nomination->classegrillesalariale_id === null) {
            return;
        }

        if (! ($nomination->snapshot_carriere['salaire_modifiable'] ?? false)) {
            return;
        }

        $this->salaireAgentService->changerClasse(
            (int) $nomination->agent_id,
            (int) $nomination->classegrillesalariale_id,
            1,
            TypeChangementSalaireAgent::ESSAI_EMPLOI_SUPERIEUR,
            'Essai emploi supérieur art. 50 — traitement minimum de la classe cible.',
        );
    }

    private function restaurerSalaireDepuisSnapshot(Nomination $nomination): void
    {
        $snapshot = $nomination->snapshot_carriere ?? [];
        if (! ($snapshot['salaire_modifiable'] ?? false)) {
            return;
        }

        $classePrecedente = $snapshot['classegrillesalariale_id'] ?? null;
        if ($classePrecedente === null) {
            return;
        }

        $this->salaireAgentService->changerClasse(
            (int) $nomination->agent_id,
            (int) $classePrecedente,
            (int) ($snapshot['echelon'] ?? 1),
            TypeChangementSalaireAgent::RESTAURATION_ESSAI,
            'Rupture essai emploi supérieur art. 50 — rétablissement du traitement précédent (pas une rétrogradation).',
        );
    }

    private function classeAvecGrade(int $id): Classegrillesalariale
    {
        return $this->classesParId[$id] ??= $this->classeRepository->findAvecGrade($id);
    }

    public function rejeter(int $id, string $commentaire): Nomination
    {
        return DB::transaction(function () use ($id, $commentaire) {
            $nomination = $this->repository->findById($id);

            abort_if(
                $nomination->lot_nomination_id,
                422,
                'Cette nomination appartient à un lot : rejetez le lot, pas la ligne.'
            );

            abort_unless(
                $nomination->statut->peutTransitionnerVers(StatutNomination::REJETEE),
                422,
                "La nomination ne peut pas être rejetée depuis le statut « {$nomination->statut->label()} »."
            );

            $ancienStatut = $nomination->statut;
            $nomination->update(['statut' => StatutNomination::REJETEE]);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_rejetee',
                ['statut' => $ancienStatut->value],
                ['statut' => StatutNomination::REJETEE->value],
                $commentaire
            );

            $nomination = $nomination->fresh();
            $this->notifier($nomination, 'rejetee');

            return $nomination;
        });
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    public function getActive(int $agentId): ?Nomination
    {
        return $this->repository->getActive($agentId);
    }

    public function getHistoriqueByAgent(int $agentId): Collection
    {
        return $this->repository->getHistoriqueByAgent($agentId);
    }

    public function postesVacants(): Collection
    {
        return $this->repository->postesVacants();
    }

    /**
     * @return array{chef: \App\Models\Agent, nomination_active: ?Nomination, affectations: Collection}
     */
    public function agentsSousAutorite(int $chefId): array
    {
        $chef = $this->agentRepository->findById($chefId);

        return [
            'chef'              => $chef,
            'nomination_active' => $this->repository->getActive($chefId),
            'affectations'      => $this->affectationRepository->getActivesParSuperieur($chefId),
        ];
    }

    /** @return string Chemin du PDF sur le disque local */
    public function genererActePdf(int $id): string
    {
        $nomination = $this->repository->findById($id);
        $nomination->load(['agent.grade', 'agent.categorie', 'agent.echelon', 'structure']);

        $structure = $nomination->structure;
        if ($structure) {
            match ($nomination->structurable_type) {
                Bureau::class    => $structure->loadMissing('service.direction'),
                Service::class   => $structure->loadMissing('direction'),
                Direction::class => null,
                default          => null,
            };
        }

        $typeActe = $nomination->type_acte ?? TypeActeNomination::DECISION;

        $pdf = Pdf::loadView('pdf.acte-nomination', [
            'nomination' => $nomination,
            'structure'  => $structure,
            'typeActe'   => $typeActe,
            'reference'  => $this->referenceActe($id, $typeActe),
        ])->setPaper('a4');

        $path = "nominations/{$nomination->agent_id}/actes/{$this->nomFichierActe($id)}";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    public function nomFichierActe(int $id): string
    {
        $nomination = $this->repository->findById($id);
        $typeActe   = $nomination->type_acte ?? TypeActeNomination::DECISION;

        return $this->referenceActe($id, $typeActe).'.pdf';
    }

    private function referenceActe(int $id, TypeActeNomination $typeActe): string
    {
        return $typeActe->prefixeNumero().'-NOM-'.date('Y').'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    private function notifier(Nomination $nomination, string $action): void
    {
        $destinataires = collect();

        if ($nomination->created_by) {
            $auteur = $this->userRepository->findById((int) $nomination->created_by);
            if ($auteur instanceof User) {
                $destinataires->push($auteur);
            }
        }

        $compteAgent = $this->userRepository->findByAgentId((int) $nomination->agent_id);
        if ($compteAgent instanceof User) {
            $destinataires->push($compteAgent);
        }

        $destinataires->unique('id')->each(
            fn (User $user) => $user->notify(new NominationEvenementNotification($nomination, $action))
        );
    }
}
