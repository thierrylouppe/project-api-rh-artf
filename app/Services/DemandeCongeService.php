<?php

namespace App\Services;

use App\Enums\StatutDemandeConge;
use App\Interfaces\AffectationInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\DemandeCongeInterface;
use App\Interfaces\TypeCongeInterface;
use App\Interfaces\UserInterface;
use App\Models\DemandeConge;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** @property DemandeCongeInterface $repository */
class DemandeCongeService extends BaseService
{
    public function __construct(
        DemandeCongeInterface $repository,
        private readonly JourFerieService $jourFerieService,
        private readonly CongeSoldeService $congeSoldeService,
        private readonly NotificationService $notificationService,
        private readonly UserInterface $userRepository,
        private readonly AffectationInterface $affectationRepository,
        private readonly AgentInterface $agentRepository,
        private readonly TypeCongeInterface $typeCongeRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    public function create(array $data): DemandeConge
    {
        return DB::transaction(function () use ($data) {
            $this->agentRepository->findById((int) $data['agent_id']);
            $type = $this->typeCongeRepository->findById((int) $data['type_conge_id']);

            abort_unless(
                $type->necessite_n1 || $type->necessite_rh || $type->necessite_dg,
                422,
                'Ce type de congé n\'a aucun circuit de validation configuré.'
            );

            $nbJours = $this->jourFerieService->calculerJoursOuvrables($data['date_debut'], $data['date_fin']);
            abort_if($nbJours < 1, 422, 'La période ne contient aucun jour ouvrable.');

            $chevauche = $this->repository->chevauchements(
                (int) $data['agent_id'],
                $data['date_debut'],
                $data['date_fin']
            );
            abort_if($chevauche->isNotEmpty(), 422, 'Une demande de congé chevauche déjà cette période.');

            if ($type->debite_solde) {
                $annee = (int) substr($data['date_debut'], 0, 4);
                $this->congeSoldeService->verifierSolde(
                    (int) $data['agent_id'],
                    (int) $data['type_conge_id'],
                    $annee,
                    $nbJours
                );
            }

            $fichier = $data['justificatif'] ?? null;
            unset($data['justificatif']);

            abort_if(
                $type->justificatif_requis && ! $fichier instanceof UploadedFile,
                422,
                'Un justificatif est obligatoire pour ce type de congé.'
            );

            if ($fichier instanceof UploadedFile) {
                $data['justificatif_path']          = $fichier->store("conges/{$data['agent_id']}", 'local');
                $data['justificatif_nom_original']  = $fichier->getClientOriginalName();
            }

            $data['nb_jours']   = $nbJours;
            $data['statut']     = StatutDemandeConge::SOUMISE;
            $data['created_by'] = $data['created_by'] ?? Auth::id();

            $demande = $this->repository->create($data);
            $demande->load(['agent', 'typeConge']);

            $this->notifierDemande($demande, 'soumise', "Une demande de congé ({$demande->nb_jours} j.) a été soumise.");

            return $demande;
        });
    }

    public function validerN1(int $id, ?string $commentaire = null): DemandeConge
    {
        $demande = $this->charger($id);
        $type    = $demande->typeConge;

        abort_unless($type->necessite_n1, 422, 'Ce type de congé ne passe pas par le N+1.');
        $this->assertEstN1((int) $demande->agent_id);
        $this->assertStatutAttendu($demande, $type->statutAttenduPourN1());

        return $this->appliquer($demande, StatutDemandeConge::VALIDEE_N1, [
            'valideur_n1_id'     => Auth::id(),
            'commentaire_n1'     => $commentaire,
            'date_validation_n1' => now(),
        ], 'validee_n1', 'La demande de congé a été validée par le N+1.');
    }

    public function rejeterN1(int $id, string $commentaire): DemandeConge
    {
        $demande = $this->charger($id);
        $type    = $demande->typeConge;

        abort_unless($type->necessite_n1, 422, 'Ce type de congé ne passe pas par le N+1.');
        $this->assertEstN1((int) $demande->agent_id);
        $this->assertStatutAttendu($demande, $type->statutAttenduPourN1());

        return $this->appliquer($demande, StatutDemandeConge::REJETEE_N1, [
            'valideur_n1_id'     => Auth::id(),
            'commentaire_n1'     => $commentaire,
            'date_validation_n1' => now(),
        ], 'rejetee_n1', 'La demande de congé a été rejetée par le N+1.');
    }

    public function validerRH(int $id, ?string $commentaire = null): DemandeConge
    {
        return DB::transaction(function () use ($id, $commentaire) {
            $demande = $this->charger($id);
            $type    = $demande->typeConge;

            abort_unless($type->necessite_rh, 422, 'Ce type de congé ne passe pas par les RH.');
            $this->assertEstRh();
            $this->assertStatutAttendu($demande, $type->statutAttenduPourRH());

            $demande = $this->appliquer($demande, StatutDemandeConge::VALIDEE_RH, [
                'valideur_rh_id'     => Auth::id(),
                'commentaire_rh'     => $commentaire,
                'date_validation_rh' => now(),
            ], 'validee_rh', 'La demande de congé a été validée par les RH.');

            if ($type->debite_solde && $type->estAccordee(StatutDemandeConge::VALIDEE_RH)) {
                $this->debiter($demande);
            }

            return $demande->fresh(['agent', 'typeConge']);
        });
    }

    public function rejeterRH(int $id, string $commentaire): DemandeConge
    {
        $demande = $this->charger($id);
        $type    = $demande->typeConge;

        abort_unless($type->necessite_rh, 422, 'Ce type de congé ne passe pas par les RH.');
        $this->assertEstRh();
        $this->assertStatutAttendu($demande, $type->statutAttenduPourRH());

        return $this->appliquer($demande, StatutDemandeConge::REJETEE_RH, [
            'valideur_rh_id'     => Auth::id(),
            'commentaire_rh'     => $commentaire,
            'date_validation_rh' => now(),
        ], 'rejetee_rh', 'La demande de congé a été rejetée par les RH.');
    }

    public function validerDG(int $id, ?string $commentaire = null): DemandeConge
    {
        return DB::transaction(function () use ($id, $commentaire) {
            $demande = $this->charger($id);
            $type    = $demande->typeConge;

            abort_unless($type->necessite_dg, 422, 'Ce type de congé ne passe pas par le Directeur Général.');
            $this->assertEstDg();
            $this->assertStatutAttendu($demande, $type->statutAttenduPourDG());

            $demande = $this->appliquer($demande, StatutDemandeConge::VALIDEE_DG, [
                'valideur_dg_id'     => Auth::id(),
                'commentaire_dg'     => $commentaire,
                'date_validation_dg' => now(),
            ], 'validee_dg', 'La demande de congé a été validée par le Directeur Général.');

            if ($type->debite_solde && $type->estAccordee(StatutDemandeConge::VALIDEE_DG)) {
                $this->debiter($demande);
            }

            return $demande->fresh(['agent', 'typeConge']);
        });
    }

    public function rejeterDG(int $id, string $commentaire): DemandeConge
    {
        $demande = $this->charger($id);
        $type    = $demande->typeConge;

        abort_unless($type->necessite_dg, 422, 'Ce type de congé ne passe pas par le Directeur Général.');
        $this->assertEstDg();
        $this->assertStatutAttendu($demande, $type->statutAttenduPourDG());

        return $this->appliquer($demande, StatutDemandeConge::REJETEE_DG, [
            'valideur_dg_id'     => Auth::id(),
            'commentaire_dg'     => $commentaire,
            'date_validation_dg' => now(),
        ], 'rejetee_dg', 'La demande de congé a été rejetée par le Directeur Général.');
    }

    public function statistiques(array $filters = []): array
    {
        $items = $this->repository->getAll($filters);

        $parStatut = [];
        foreach (StatutDemandeConge::cases() as $statut) {
            $parStatut[$statut->value] = $items->where('statut', $statut)->count();
        }

        $accordees = $items->filter(function ($demande) {
            $demande->loadMissing('typeConge');

            return $demande->typeConge?->estAccordee($demande->statut) ?? false;
        });

        return [
            'total'            => $items->count(),
            'par_statut'       => $parStatut,
            'jours_accordes'   => $accordees->sum('nb_jours'),
        ];
    }

    public function fichePdf(int $id): Response
    {
        $demande = $this->charger($id);

        return Pdf::loadView('pdf.fiche-conge', ['demande' => $demande])
            ->stream("fiche-conge-{$demande->id}.pdf");
    }

    public function attestationPdf(int $id): Response
    {
        $demande = $this->charger($id);

        abort_unless(
            $demande->typeConge->estAccordee($demande->statut),
            422,
            'L\'attestation n\'est disponible qu\'après la validation finale du circuit.'
        );

        return Pdf::loadView('pdf.attestation-conge', ['demande' => $demande])
            ->stream("attestation-conge-{$demande->id}.pdf");
    }

    private function charger(int $id): DemandeConge
    {
        $demande = $this->repository->findById($id);
        $demande->load(['agent', 'typeConge']);

        return $demande;
    }

    private function assertStatutAttendu(DemandeConge $demande, StatutDemandeConge $attendu): void
    {
        abort_unless(
            $demande->statut === $attendu,
            422,
            "Transition invalide : {$demande->statut->label()} (attendu : {$attendu->label()})."
        );
    }

    private function appliquer(DemandeConge $demande, StatutDemandeConge $cible, array $extra, string $action, string $message): DemandeConge
    {
        $demande = $this->repository->update($demande->id, array_merge($extra, ['statut' => $cible]));
        $demande->load(['agent', 'typeConge']);
        $this->notifierDemande($demande, $action, $message);

        return $demande;
    }

    private function debiter(DemandeConge $demande): void
    {
        $annee = (int) $demande->date_debut->format('Y');
        $this->congeSoldeService->debiter(
            (int) $demande->agent_id,
            (int) $demande->type_conge_id,
            $annee,
            (int) $demande->nb_jours
        );
    }

    private function utilisateurConnecte(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401, 'Non authentifié.');

        return $user;
    }

    private function assertEstN1(int $agentId): void
    {
        $user = $this->utilisateurConnecte();
        if ($user->hasRole('admin')) {
            return;
        }

        $n1 = $this->compteN1($agentId);
        abort_unless(
            (int) $n1->id === (int) $user->id,
            403,
            'Seul le supérieur hiérarchique de l\'agent (affectation active) peut valider au niveau N+1.'
        );
    }

    private function compteN1(int $agentId): User
    {
        $affectation = $this->affectationRepository->getActive($agentId);

        abort_unless(
            $affectation?->superieur_hierarchique_id,
            422,
            'Aucune affectation active avec supérieur hiérarchique : le N+1 ne peut pas être déterminé.'
        );

        $compte = $this->userRepository->findByAgentId((int) $affectation->superieur_hierarchique_id);
        abort_unless(
            $compte instanceof User,
            422,
            'Le supérieur hiérarchique n\'a pas de compte utilisateur.'
        );

        return $compte;
    }

    private function assertEstRh(): void
    {
        $user = $this->utilisateurConnecte();
        abort_unless(
            $user->hasRole('rh') || $user->hasRole('admin'),
            403,
            'Seuls les RH (DRHL) peuvent valider à ce niveau.'
        );
    }

    private function assertEstDg(): void
    {
        $user = $this->utilisateurConnecte();
        abort_unless(
            $user->hasRole('directeur-general') || $user->hasRole('admin'),
            403,
            'Seul le Directeur Général peut valider à ce niveau.'
        );
    }

    private function notifierDemande(DemandeConge $demande, string $action, string $message): void
    {
        $destinataires = collect();

        $compteAgent = $this->userRepository->findByAgentId((int) $demande->agent_id);
        if ($compteAgent instanceof User) {
            $destinataires->push($compteAgent);
        }

        $affectation = $this->affectationRepository->getActive((int) $demande->agent_id);
        if ($affectation?->superieur_hierarchique_id) {
            $n1 = $this->userRepository->findByAgentId((int) $affectation->superieur_hierarchique_id);
            if ($n1 instanceof User) {
                $destinataires->push($n1);
            }
        }

        $this->notificationService->notifierRole(
            'rh',
            'conge',
            $action,
            $message,
            ['demande_id' => $demande->id, 'agent_id' => $demande->agent_id]
        );

        $this->notificationService->notifierEvenementGroupe(
            $destinataires,
            'conge',
            $action,
            $message,
            ['demande_id' => $demande->id, 'agent_id' => $demande->agent_id]
        );
    }
}
