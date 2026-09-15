<?php

namespace App\Services;

use App\Enums\CodeTypeSanction;
use App\Enums\StatutAgent;
use App\Enums\StatutSanction;
use App\Interfaces\AgentInterface;
use App\Interfaces\AvertissementInterface;
use App\Interfaces\SanctionInterface;
use App\Interfaces\SanctionPieceInterface;
use App\Interfaces\TypeSanctionInterface;
use App\Models\Agent;
use App\Models\Sanction;
use App\Models\SanctionPiece;
use App\Models\TypeSanction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** @property SanctionInterface $repository */
class SanctionService extends BaseService
{
    private const CONSERVATION_ANNEES = 5;

    public function __construct(
        SanctionInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly TypeSanctionInterface $typeSanctionRepository,
        private readonly AvertissementInterface $avertissementRepository,
        private readonly SanctionPieceInterface $pieceRepository,
        private readonly NotificationService $notificationService,
        private readonly SuperieurHierarchiqueService $superieurService,
        private readonly AgentService $agentService,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId);
    }

    /**
     * @return array{sanctions: Collection, avertissements: Collection, recidive: bool}
     */
    public function historique(int $agentId): array
    {
        $this->agentRepository->findById($agentId);

        $sanctions = $this->repository->getByAgent($agentId);

        return [
            'sanctions' => $sanctions,
            'avertissements' => $this->avertissementRepository->getByAgent($agentId),
            'recidive' => $this->estRecidive((int) $agentId),
        ];
    }

    public function mesSanctions(): Collection
    {
        return $this->repository->getByAgent($this->agentConnecteId());
    }

    public function maSanction(int $id): Sanction
    {
        $sanction = $this->repository->findById($id);

        abort_unless(
            (int) $sanction->agent_id === $this->agentConnecteId(),
            404,
            'Sanction introuvable.'
        );

        return $this->charger($sanction, true);
    }

    public function mesRapports(): Collection
    {
        $this->assertPermission('proposer-discipline');

        return $this->repository->getByCreatedBy($this->utilisateur()->id);
    }

    public function voir(int $id): Sanction
    {
        $sanction = $this->charger($this->repository->findById($id));
        $this->assertPeutConsulterDossier($sanction);

        return $this->enrichirAntecedents($sanction);
    }

    /**
     * @return array{sanctions: Collection, avertissements: Collection}
     */
    public function monHistorique(): array
    {
        return $this->historique($this->agentConnecteId());
    }

    public function aInstruire(): Collection
    {
        return $this->repository->getByStatut(StatutSanction::EN_ATTENTE->value);
    }

    public function aValider(): Collection
    {
        return $this->repository->getByStatut(StatutSanction::INSTRUITE->value);
    }

    public function aPrononcer(): Collection
    {
        return $this->aValider();
    }

    public function create(array $data): Sanction
    {
        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        $this->assertAgentPeutEtreSanctionne($agent);
        $this->assertPeutProposer((int) $agent->id);

        $type = $this->typeSanctionRepository->findById((int) $data['type_sanction_id']);
        abort_unless(
            $type instanceof TypeSanction && $type->actif,
            422,
            'Ce type de sanction n\'est plus actif.'
        );

        $data = $this->appliquerReglesType($type, $data);
        $data['statut'] = StatutSanction::EN_ATTENTE;
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['conservee_jusqu_au'] = $this->dateConservation();

        $sanction = $this->repository->create($data);
        $sanction = $this->charger($sanction, true);

        $this->notifier($sanction, 'creee', 'Un rapport disciplinaire a été soumis.');

        return $sanction;
    }

    public function update(int $id, array $data): Sanction
    {
        $sanction = $this->repository->findById($id);
        $this->assertOuverte($sanction, 'modifié');
        $this->assertPeutModifierRapport($sanction);

        if (isset($data['agent_id'])) {
            $agent = $this->agentRepository->findById((int) $data['agent_id']);
            $this->assertAgentPeutEtreSanctionne($agent);
            $this->assertPeutProposer((int) $agent->id);
        }
        $type = $sanction->typeSanction;
        if (isset($data['type_sanction_id'])) {
            $type = $this->typeSanctionRepository->findById((int) $data['type_sanction_id']);
            abort_unless($type instanceof TypeSanction && $type->actif, 422, 'Ce type de sanction n\'est plus actif.');
        }

        $data = $this->appliquerReglesType($type, $data, $sanction);
        unset($data['statut'], $data['validateur_id'], $data['created_by']);

        return $this->charger($this->repository->update($id, $data));
    }

    public function instruire(int $id, array $data): Sanction
    {
        $sanction = $this->repository->findById($id);

        abort_unless(
            $sanction->statut === StatutSanction::EN_ATTENTE,
            422,
            'Seule une sanction en attente peut être instruite.'
        );

        abort_unless(
            $this->pieceRepository->getBySanction($id)->isNotEmpty(),
            422,
            'Toute proposition de sanction doit comprendre les pièces s\'y rapportant (CCN art. 91).'
        );

        $payload = [
            'statut' => StatutSanction::INSTRUITE,
            'notes_instruction' => $data['notes_instruction'],
        ];
        if (! empty($data['decision'])) {
            $payload['decision'] = $data['decision'];
        }

        $sanction = $this->charger($this->repository->update($id, $payload), true);

        $this->notifier($sanction, 'instruite', 'Le rapport disciplinaire a été instruit et transmis au directeur général.');

        return $sanction;
    }

    public function valider(int $id, array $data): Sanction
    {
        return DB::transaction(function () use ($id, $data) {
            $sanction = $this->charger($this->repository->findById($id));
            $user = $this->utilisateur();

            abort_unless(
                $sanction->statut === StatutSanction::INSTRUITE,
                422,
                'Seul un dossier instruit peut être prononcé par le directeur général (CCN art. 91).'
            );

            $dateDecision = $data['date_decision'] ?? now()->toDateString();
            $payload = [
                'statut' => StatutSanction::VALIDEE,
                'decision' => $data['decision'],
                'date_decision' => $dateDecision,
                'commentaire_validation' => $data['commentaire'] ?? null,
                'validateur_id' => $user->id,
                'conservee_jusqu_au' => $this->dateConservation($dateDecision, $sanction->conservee_jusqu_au),
            ];

            $payload = array_merge($payload, $this->effetsPrononciation($sanction, $data, $dateDecision));
            $sanction = $this->charger($this->repository->update($id, $payload), true);

            $this->appliquerEffetsAgent($sanction);
            $sanction->unsetRelation('agent');
            $sanction->load('agent:id,matricule,nom,prenom,statut');
            $this->notifier($sanction, 'validee', 'La sanction a été prononcée par le directeur général.');

            return $sanction;
        });
    }

    public function rejeter(int $id, string $commentaire): Sanction
    {
        $sanction = $this->repository->findById($id);
        $user = $this->utilisateur();

        abort_unless(
            $sanction->statut === StatutSanction::INSTRUITE,
            422,
            'Seul un dossier instruit peut être classé sans suite par le directeur général (CCN art. 91).'
        );

        $dateDecision = now()->toDateString();
        $sanction = $this->charger($this->repository->update($id, [
            'statut' => StatutSanction::REJETEE,
            'commentaire_validation' => $commentaire,
            'date_decision' => $dateDecision,
            'validateur_id' => $user->id,
            'conservee_jusqu_au' => $this->dateConservation($dateDecision, $sanction->conservee_jusqu_au),
        ]), true);

        $this->notifier($sanction, 'rejetee', 'Le rapport disciplinaire a été classé sans suite.');

        return $sanction;
    }

    public function delete(int $id): bool
    {
        $sanction = $this->repository->findById($id);

        abort_unless(
            $sanction->statut === StatutSanction::EN_ATTENTE,
            422,
            'Seule une sanction en attente peut être supprimée. Un rapport instruit ou prononcé est conservé au moins cinq ans (CCN art. 91).'
        );

        $this->assertPeutModifierRapport($sanction);

        foreach ($this->pieceRepository->getBySanction($id) as $piece) {
            $this->supprimerFichier($piece);
            $this->pieceRepository->delete($piece->id);
        }

        return $this->repository->delete($id);
    }

    public function pieces(int $id): Collection
    {
        $this->assertPeutConsulterDossier($this->repository->findById($id));

        return $this->pieceRepository->getBySanction($id);
    }

    public function ajouterPiece(int $id, UploadedFile $fichier): SanctionPiece
    {
        $sanction = $this->repository->findById($id);
        $this->assertOuverte($sanction, 'complété');
        $this->assertPeutJoindrePiece($sanction);

        $path = $fichier->store("discipline/sanctions/{$id}", 'local');

        return $this->pieceRepository->create([
            'sanction_id' => $id,
            'fichier_path' => $path,
            'nom_original' => $fichier->getClientOriginalName(),
            'mime_type' => $fichier->getClientMimeType(),
            'taille' => $fichier->getSize(),
            'uploaded_by' => Auth::id(),
        ])->load('uploader:id,name');
    }

    public function telechargerPiece(int $id, int $pieceId): StreamedResponse
    {
        $this->assertPeutConsulterDossier($this->repository->findById($id));
        $piece = $this->pieceRepository->findForSanction($id, $pieceId);

        abort_unless(
            Storage::disk('local')->exists($piece->fichier_path),
            404,
            'Pièce introuvable.'
        );

        return Storage::disk('local')->download($piece->fichier_path, $piece->nom_original);
    }

    public function supprimerPiece(int $id, int $pieceId): void
    {
        $sanction = $this->repository->findById($id);
        $this->assertOuverte($sanction, 'modifié');
        $this->assertPeutJoindrePiece($sanction);

        $piece = $this->pieceRepository->findForSanction($id, $pieceId);
        $this->supprimerFichier($piece);
        $this->pieceRepository->delete($piece->id);
    }

    public function rapportPdf(int $id): Response
    {
        $sanction = $this->voir($id);

        return Pdf::loadView('pdf.rapport-disciplinaire', ['sanction' => $sanction])
            ->stream("rapport-disciplinaire-{$sanction->id}.pdf");
    }

    public function decisionPdf(int $id): Response
    {
        $sanction = $this->voir($id);

        abort_unless(
            in_array($sanction->statut, [StatutSanction::VALIDEE, StatutSanction::REJETEE], true),
            422,
            'La décision n\'est disponible qu\'après prononcé du directeur général.'
        );

        return Pdf::loadView('pdf.decision-disciplinaire', ['sanction' => $sanction])
            ->stream("decision-disciplinaire-{$sanction->id}.pdf");
    }

    public function maDecisionPdf(int $id): Response
    {
        $sanction = $this->maSanction($id);

        abort_unless(
            in_array($sanction->statut, [StatutSanction::VALIDEE, StatutSanction::REJETEE], true),
            422,
            'La décision n\'est disponible qu\'après prononcé du directeur général.'
        );

        return Pdf::loadView('pdf.decision-disciplinaire', ['sanction' => $sanction])
            ->stream("decision-disciplinaire-{$sanction->id}.pdf");
    }

    private function agentConnecteId(): int
    {
        $user = $this->utilisateur();
        abort_unless($user->agent_id, 403, 'Aucun agent associé à ce compte utilisateur.');

        return (int) $user->agent_id;
    }

    private function utilisateur(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401, 'Non authentifié.');

        return $user;
    }

    private function aPermission(string $permission): bool
    {
        $user = $this->utilisateur();

        return $user->hasRole('admin') || $user->hasPermissionTo($permission, 'api');
    }

    private function assertPermission(string $permission): void
    {
        abort_unless($this->aPermission($permission), 403, 'Accès refusé.');
    }

    private function assertPeutProposer(int $agentId): void
    {
        if ($this->aPermission('gerer-discipline')) {
            return;
        }

        $this->assertPermission('proposer-discipline');
        $this->superieurService->assertEstN1($this->utilisateur(), $agentId);
    }

    private function assertPeutConsulterDossier(Sanction $sanction): void
    {
        if (
            $this->aPermission('consulter-discipline')
            || $this->aPermission('gerer-discipline')
            || $this->aPermission('prononcer-discipline')
        ) {
            return;
        }

        abort_unless(
            $this->aPermission('proposer-discipline')
            && (int) $sanction->created_by === (int) $this->utilisateur()->id,
            403,
            'Accès refusé.'
        );
    }

    private function assertPeutModifierRapport(Sanction $sanction): void
    {
        if ($this->aPermission('gerer-discipline')) {
            return;
        }

        abort_unless(
            $this->aPermission('proposer-discipline')
            && (int) $sanction->created_by === (int) $this->utilisateur()->id
            && $sanction->statut === StatutSanction::EN_ATTENTE,
            403,
            'Seul l\'auteur du rapport peut le modifier tant qu\'il n\'est pas instruit.'
        );
    }

    private function assertPeutJoindrePiece(Sanction $sanction): void
    {
        if ($this->aPermission('gerer-discipline')) {
            return;
        }

        abort_unless(
            $this->aPermission('proposer-discipline')
            && (int) $sanction->created_by === (int) $this->utilisateur()->id
            && $sanction->statut === StatutSanction::EN_ATTENTE,
            403,
            'Seuls l\'auteur du rapport (avant instruction) ou la RH peuvent joindre une pièce.'
        );
    }

    private function appliquerReglesType(TypeSanction $type, array $data, ?Sanction $existante = null): array
    {
        if ($type->exige_nb_jours) {
            $nbJours = $data['nb_jours'] ?? $existante?->nb_jours;
            $min = $type->nb_jours_min ?? 1;
            $max = $type->nb_jours_max ?? 8;
            abort_unless(
                $nbJours !== null && $nbJours >= $min && $nbJours <= $max,
                422,
                "La mise à pied est de {$min} à {$max} jours (CCN art. 90)."
            );
            $data['nb_jours'] = (int) $nbJours;
        } else {
            unset($data['nb_jours']);
        }

        if ($type->code === CodeTypeSanction::LICENCIEMENT) {
            if (! array_key_exists('avec_indemnite', $data)) {
                $data['avec_indemnite'] = $existante?->avec_indemnite ?? true;
            }
        } else {
            unset($data['avec_indemnite']);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function effetsPrononciation(Sanction $sanction, array $data, string $dateDecision): array
    {
        $type = $sanction->typeSanction;
        if (! $type instanceof TypeSanction) {
            return [];
        }

        $effets = [];

        if ($type->code === CodeTypeSanction::MISE_A_PIED) {
            $nbJours = (int) ($data['nb_jours'] ?? $sanction->nb_jours);
            $min = $type->nb_jours_min ?? 1;
            $max = $type->nb_jours_max ?? 8;
            abort_unless(
                $nbJours >= $min && $nbJours <= $max,
                422,
                "La mise à pied est de {$min} à {$max} jours (CCN art. 90)."
            );
            $debut = $data['date_debut_effet'] ?? $dateDecision;
            $effets['nb_jours'] = $nbJours;
            $effets['date_debut_effet'] = $debut;
            $effets['date_fin_effet'] = Carbon::parse($debut)->addDays($nbJours - 1)->toDateString();
        }

        if ($type->code === CodeTypeSanction::LICENCIEMENT) {
            $effets['avec_indemnite'] = array_key_exists('avec_indemnite', $data)
                ? (bool) $data['avec_indemnite']
                : ($sanction->avec_indemnite ?? true);
        }

        return $effets;
    }

    public function appliquerEffetsMiseAPied(?string $jour = null): array
    {
        $jour = $jour ?? now()->toDateString();
        $suspendus = 0;
        $leves = 0;

        foreach ($this->repository->getMisesAPiedEnCours($jour) as $sanction) {
            if ($sanction->agent?->statut !== StatutAgent::ACTIF->value) {
                continue;
            }

            $this->agentService->suspendrePourDiscipline((int) $sanction->agent_id);
            $suspendus++;
        }

        foreach ($this->repository->getMisesAPiedEchues($jour) as $sanction) {
            if ($sanction->agent?->statut !== StatutAgent::SUSPENDU->value) {
                continue;
            }

            if ($this->repository->agentAUneMiseAPiedEnCours((int) $sanction->agent_id, $jour)) {
                continue;
            }

            $this->agentService->leverSuspensionDisciplinaire((int) $sanction->agent_id);
            $leves++;
        }

        return ['suspendus' => $suspendus, 'leves' => $leves];
    }

    private function appliquerEffetsAgent(Sanction $sanction): void
    {
        $type = $sanction->typeSanction;
        if (! $type instanceof TypeSanction) {
            return;
        }

        if ($type->code === CodeTypeSanction::MISE_A_PIED) {
            $debut = $sanction->date_debut_effet?->toDateString();
            if ($debut && $debut <= now()->toDateString()) {
                $this->agentService->suspendrePourDiscipline((int) $sanction->agent_id);
            }
        }

        if ($type->code === CodeTypeSanction::LICENCIEMENT) {
            $motif = sprintf(
                'Licenciement disciplinaire n°%d%s',
                $sanction->id,
                $sanction->decision ? ' : '.$sanction->decision : ''
            );
            $this->agentService->archiver((int) $sanction->agent_id, $motif);
        }
    }

    private function dateConservation(?string $ancre = null, mixed $existante = null): string
    {
        $cible = Carbon::parse($ancre ?? now())->addYears(self::CONSERVATION_ANNEES);

        if ($existante) {
            $actuelle = Carbon::parse($existante);
            if ($actuelle->greaterThan($cible)) {
                $cible = $actuelle;
            }
        }

        return $cible->toDateString();
    }

    private function estRecidive(int $agentId, ?int $exclureId = null): bool
    {
        return $this->repository
            ->getPrononceesDepuis($agentId, now()->subYears(self::CONSERVATION_ANNEES)->toDateString(), $exclureId)
            ->isNotEmpty();
    }

    private function enrichirAntecedents(Sanction $sanction): Sanction
    {
        $depuis = now()->subYears(self::CONSERVATION_ANNEES)->toDateString();
        $items = $this->repository->getPrononceesDepuis((int) $sanction->agent_id, $depuis, $sanction->id);

        $sanction->setAttribute('recidive', $items->isNotEmpty());
        $sanction->setAttribute('antecedents_5_ans', $items->map(fn (Sanction $item) => [
            'id' => $item->id,
            'type' => $item->typeSanction?->nom,
            'date_decision' => $item->date_decision?->format('Y-m-d'),
            'motif' => $item->motif,
        ])->values()->all());

        return $sanction;
    }

    private function charger(Sanction $sanction, bool $avecAntecedents = false): Sanction
    {
        $sanction->load([
            'agent:id,matricule,nom,prenom,statut',
            'typeSanction',
            'validateur:id,name',
            'createur:id,name',
            'pieces.uploader:id,name',
        ]);

        return $avecAntecedents ? $this->enrichirAntecedents($sanction) : $sanction;
    }

    private function assertOuverte(Sanction $sanction, string $action): void
    {
        abort_unless(
            $sanction->statut instanceof StatutSanction && $sanction->statut->estOuvert(),
            422,
            "Seule une sanction ouverte peut être {$action}."
        );
    }

    private function assertAgentPeutEtreSanctionne(Agent $agent): void
    {
        abort_if(
            $agent->archived_at !== null || $agent->statut === StatutAgent::ARCHIVE->value,
            422,
            'Impossible d\'ouvrir un dossier disciplinaire pour un agent archivé.'
        );
    }

    private function supprimerFichier(SanctionPiece $piece): void
    {
        if ($piece->fichier_path && Storage::disk('local')->exists($piece->fichier_path)) {
            Storage::disk('local')->delete($piece->fichier_path);
        }
    }

    private function notifier(Sanction $sanction, string $action, string $message): void
    {
        $meta = [
            'sanction_id' => $sanction->id,
            'agent_id' => $sanction->agent_id,
        ];

        $this->notificationService->notifierEvenementGroupe(
            $this->destinatairesNotification($sanction, $action),
            'discipline',
            $action,
            $message,
            $meta
        );
    }

    /**
     * @return Collection<int, User>
     */
    private function destinatairesNotification(Sanction $sanction, string $action): Collection
    {
        $exclure = Auth::id();

        $destinataires = match ($action) {
            'instruite' => $this->notificationService
                ->destinatairesRoleEtAgent('directeur-general', (int) $sanction->agent_id, $exclure)
                ->concat($this->notificationService->destinatairesAuteurEtAgent((int) $sanction->created_by, null)),
            default => $this->notificationService
                ->destinatairesRoleEtAgent('rh', (int) $sanction->agent_id, $exclure)
                ->concat($this->notificationService->destinatairesAuteurEtAgent((int) $sanction->created_by, null)),
        };

        return $destinataires
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $exclure)
            ->values();
    }
}
