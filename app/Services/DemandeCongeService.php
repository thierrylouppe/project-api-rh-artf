<?php

namespace App\Services;

use App\Enums\StatutDemandeConge;
use App\Interfaces\AbsenceInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\DemandeCongeInterface;
use App\Interfaces\TypeCongeInterface;
use App\Interfaces\UserInterface;
use App\Models\DemandeConge;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        private readonly SuperieurHierarchiqueService $superieurService,
        private readonly AgentInterface $agentRepository,
        private readonly TypeCongeInterface $typeCongeRepository,
        private readonly AbsenceInterface $absenceRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    public function aValider(): Collection
    {
        $user = $this->utilisateurConnecte();

        return $this->repository->getEnAttenteValidation()
            ->filter(function (DemandeConge $demande) use ($user) {
                $etape = $demande->typeConge?->prochaineEtape($demande->statut);
                if ($etape === null) {
                    return false;
                }

                if ($user->hasRole('admin')) {
                    return true;
                }

                return match ($etape) {
                    'valider-n1' => $this->superieurService->estN1($user, (int) $demande->agent_id),
                    'valider-rh' => $user->hasRole('rh'),
                    'valider-dg' => $user->hasRole('directeur-general'),
                    default      => false,
                };
            })
            ->values();
    }

    public function create(array $data): DemandeConge
    {
        return DB::transaction(function () use ($data) {
            $agent = $this->agentRepository->findById((int) $data['agent_id']);
            $type  = $this->typeCongeRepository->findById((int) $data['type_conge_id']);

            abort_unless(
                $type->necessite_n1 || $type->necessite_rh || $type->necessite_dg,
                422,
                'Ce type de congé n\'a aucun circuit de validation configuré.'
            );

            $nbJours = $this->jourFerieService->calculerJoursOuvrables($data['date_debut'], $data['date_fin']);
            abort_if($nbJours < 1, 422, 'La période ne contient aucun jour ouvrable.');

            // ── Règles CCN ARTF art. 77 ─────────────────────────────────────────

            // Congé annuel : droit acquis après 12 mois de service effectif
            if (str_starts_with(strtolower($type->nom), 'congé annuel')) {
                abort_unless(
                    $agent->date_prise_service !== null,
                    422,
                    'La date de prise de service est absente du dossier agent. Le droit au congé annuel ne peut être vérifié.'
                );
                $moisService = (int) Carbon::parse($agent->date_prise_service)
                    ->diffInMonths(Carbon::parse($data['date_debut']));
                abort_unless(
                    $moisService >= 12,
                    422,
                    "Le congé annuel est acquis après 12 mois de service effectif. L'agent en a {$moisService} mois."
                );
            }

            // Congé pour convenances personnelles : minimum 15 jours ouvrables (CCN art. 77)
            if (str_contains(strtolower($type->nom), 'convenances personnelles')) {
                abort_unless(
                    $nbJours >= 15,
                    422,
                    "Le congé pour convenances personnelles ne peut être inférieur à 15 jours ouvrables (CCN art. 77). Période saisie : {$nbJours} j."
                );
            }

            // ────────────────────────────────────────────────────────────────────

            $this->assertPeriodeLibre((int) $data['agent_id'], $data['date_debut'], $data['date_fin']);

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

    public function annuler(int $id): DemandeConge
    {
        $demande = $this->charger($id);
        $user    = $this->utilisateurConnecte();

        abort_unless(
            $demande->statut === StatutDemandeConge::SOUMISE,
            422,
            'Seule une demande encore soumise peut être annulée.'
        );

        $estTitulaire = (int) $user->agent_id === (int) $demande->agent_id
            || (int) $user->id === (int) $demande->created_by
            || $user->hasRole('admin');

        abort_unless($estTitulaire, 403, 'Seul le demandeur peut annuler cette demande.');

        return $this->appliquer($demande, StatutDemandeConge::ANNULEE, [], 'annulee', 'La demande de congé a été annulée.');
    }

    public function justificatif(int $id): StreamedResponse
    {
        $demande = $this->charger($id);

        abort_unless(
            $demande->justificatif_path && Storage::disk('local')->exists($demande->justificatif_path),
            404,
            'Aucun justificatif déposé.'
        );

        return Storage::disk('local')->download(
            $demande->justificatif_path,
            $demande->justificatif_nom_original ?: 'justificatif'
        );
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

    private function assertPeriodeLibre(int $agentId, string $debut, string $fin, ?int $exclureDemandeId = null): void
    {
        abort_if(
            $this->repository->chevauchements($agentId, $debut, $fin, $exclureDemandeId)->isNotEmpty(),
            422,
            'Une demande de congé chevauche déjà cette période.'
        );

        abort_if(
            $this->absenceRepository->chevauchements($agentId, $debut, $fin)->isNotEmpty(),
            422,
            'Une absence chevauche déjà cette période.'
        );
    }

    private function assertEstN1(int $agentId): void
    {
        $this->superieurService->assertEstN1($this->utilisateurConnecte(), $agentId);
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

        $n1 = $this->superieurService->trouverCompteN1((int) $demande->agent_id);
        if ($n1 instanceof User) {
            $destinataires->push($n1);
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
