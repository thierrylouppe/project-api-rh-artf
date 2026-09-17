<?php

namespace App\Services;

use App\Enums\NatureArretSante;
use App\Enums\StatutDossierSante;
use App\Enums\TypeStructureSanitaire;
use App\Interfaces\AgentInterface;
use App\Interfaces\ArretSanteInterface;
use App\Interfaces\ArretSantePieceInterface;
use App\Interfaces\DemandeCongeInterface;
use App\Interfaces\StructureSanitaireInterface;
use App\Models\ArretSante;
use App\Models\ArretSantePiece;
use App\Models\DemandeConge;
use App\Models\StructureSanitaire;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** @property ArretSanteInterface $repository */
class ArretSanteService extends BaseService
{
    public function __construct(
        ArretSanteInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly StructureSanitaireInterface $structureRepository,
        private readonly ArretSantePieceInterface $pieceRepository,
        private readonly DemandeCongeInterface $demandeCongeRepository,
        private readonly SanteCalculService $calcul,
        private readonly PaieAffectationService $affectationService,
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId);
    }

    public function findById(int $id): ArretSante
    {
        return $this->charger($this->repository->findById($id));
    }

    /**
     * @return array<string, mixed>
     */
    public function simuler(int $id): array
    {
        $arret = $this->repository->findById($id);
        $agent = $this->agentRepository->findById((int) $arret->agent_id);

        return $this->calcul->simulerArret(
            $arret->nature,
            $agent,
            $arret->date_fait,
            $arret->date_notification,
        );
    }

    protected function beforeCreate(array $data): array
    {
        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        $this->assertStructureMedecin((int) $data['structure_sanitaire_id']);
        $this->assertConge($data, (int) $agent->id);

        $snapshot = $this->calcul->simulerArret(
            NatureArretSante::from((string) $data['nature']),
            $agent,
            Carbon::parse((string) $data['date_fait']),
            isset($data['date_notification']) ? Carbon::parse((string) $data['date_notification']) : null,
        );

        $data['statut'] = StatutDossierSante::BROUILLON->value;
        $data['alerte_72h'] = (bool) $snapshot['alerte_72h'];
        $data['nb_mois'] = $snapshot['nb_mois'];
        $data['nb_mois_majoration'] = $snapshot['nb_mois_majoration'];
        $data['montant_mensuel'] = $snapshot['montant_mensuel'];
        $data['montant_mensuel_demi'] = $snapshot['montant_mensuel_demi'];
        $data['calcul_snapshot'] = $snapshot;
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate($model): ArretSante
    {
        return $this->charger($model);
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $arret = $this->repository->findById($id);
        abort_unless($arret->statut->estModifiable(), 422, 'Seul un arrêt en brouillon ou soumis peut être modifié.');

        unset(
            $data['statut'],
            $data['created_by'],
            $data['instruite_by'],
            $data['decideur_id'],
            $data['date_decision'],
            $data['paie_element_affectation_id'],
        );

        if ($arret->statut !== StatutDossierSante::BROUILLON) {
            unset($data['agent_id'], $data['nature']);
        }

        $agentId = (int) ($data['agent_id'] ?? $arret->agent_id);
        $agent = $this->agentRepository->findById($agentId);
        if (isset($data['structure_sanitaire_id'])) {
            $this->assertStructureMedecin((int) $data['structure_sanitaire_id']);
        }
        $fusionConge = array_merge($arret->toArray(), $data);
        $this->assertConge($fusionConge, $agentId);

        $nature = NatureArretSante::from((string) ($data['nature'] ?? $arret->nature->value));
        $dateFait = Carbon::parse((string) ($data['date_fait'] ?? $arret->date_fait?->toDateString()));
        $notif = $data['date_notification'] ?? $arret->date_notification?->toDateString();
        $snapshot = $this->calcul->simulerArret(
            $nature,
            $agent,
            $dateFait,
            $notif ? Carbon::parse((string) $notif) : null,
        );

        $data['alerte_72h'] = (bool) $snapshot['alerte_72h'];
        $data['nb_mois'] = $snapshot['nb_mois'];
        $data['nb_mois_majoration'] = $snapshot['nb_mois_majoration'];
        $data['montant_mensuel'] = $snapshot['montant_mensuel'];
        $data['montant_mensuel_demi'] = $snapshot['montant_mensuel_demi'];
        $data['calcul_snapshot'] = $snapshot;

        return $data;
    }

    protected function afterUpdate($model): ArretSante
    {
        return $this->charger($model);
    }

    public function delete(int $id): bool
    {
        $arret = $this->repository->findById($id);
        abort_unless($arret->statut === StatutDossierSante::BROUILLON, 422, 'Seul un arrêt en brouillon peut être supprimé.');

        foreach ($this->pieceRepository->getByArret($id) as $piece) {
            $this->supprimerFichier($piece);
            $this->pieceRepository->delete($piece->id);
        }

        return parent::delete($id);
    }

    public function soumettre(int $id): ArretSante
    {
        $arret = $this->charger($this->repository->findById($id));
        abort_unless($arret->statut === StatutDossierSante::BROUILLON, 422, 'Seul un arrêt en brouillon peut être soumis.');
        $payload = $this->recalculer($arret);
        $payload['statut'] = StatutDossierSante::SOUMISE->value;
        $arret = $this->charger($this->repository->update($id, $payload));
        $this->notifier($arret, 'soumis', 'Un arrêt maladie / accident a été soumis.', true);

        return $arret;
    }

    public function instruire(int $id, array $data): ArretSante
    {
        $arret = $this->charger($this->repository->findById($id));
        abort_unless($arret->statut === StatutDossierSante::SOUMISE, 422, 'Seul un arrêt soumis peut être instruit.');
        abort_unless(
            $this->pieceRepository->getByArret($id)->isNotEmpty(),
            422,
            'Joignez le certificat du médecin agréé avant d\'instruire (CCN art. 131).'
        );

        $payload = $this->recalculer($arret);
        $payload['statut'] = StatutDossierSante::INSTRUITE->value;
        $payload['notes_instruction'] = $data['notes_instruction'];
        $payload['instruite_by'] = Auth::id();
        $arret = $this->charger($this->repository->update($id, $payload));
        $this->notifier($arret, 'instruit', 'L\'arrêt a été instruit et transmis au directeur général.', true);

        return $arret;
    }

    public function accorder(int $id, array $data): ArretSante
    {
        return DB::transaction(function () use ($id, $data) {
            $arret = $this->charger($this->repository->findById($id));
            abort_unless($arret->statut === StatutDossierSante::INSTRUITE, 422, 'Seul un arrêt instruit peut être accordé.');

            $recalc = $this->recalculer($arret);
            abort_if((int) $recalc['montant_mensuel'] <= 0, 422, 'Impossible d\'accorder une allocation d\'un montant nul.');

            $agent = $this->agentRepository->findById((int) $arret->agent_id);
            $debut = $arret->date_debut->copy()->startOfMonth();
            $premiere = null;

            if ($arret->nature === NatureArretSante::ACCIDENT_NON_PROFESSIONNEL) {
                $finPlein = $debut->copy()->addMonths(5)->endOfMonth();
                $debutDemi = $debut->copy()->addMonths(6)->startOfMonth();
                $finDemi = $debut->copy()->addMonths(11)->endOfMonth();

                $premiere = $this->affectationService->creerDepuisPrestation(
                    $agent,
                    $arret->nature->codePaie(),
                    (int) $recalc['montant_mensuel'],
                    $debut->toDateString(),
                    $finPlein->toDateString(),
                    sprintf('Arrêt #%d — 6 mois plein (CCN art. 135)', $arret->id),
                    ['arret_sante_id' => $arret->id, 'phase' => 'plein'],
                );
                $this->affectationService->creerDepuisPrestation(
                    $agent,
                    $arret->nature->codePaie(),
                    (int) $recalc['montant_mensuel_demi'],
                    $debutDemi->toDateString(),
                    $finDemi->toDateString(),
                    sprintf('Arrêt #%d — 6 mois demi (CCN art. 135)', $arret->id),
                    ['arret_sante_id' => $arret->id, 'phase' => 'demi'],
                );
            } else {
                $nb = max(1, (int) $recalc['nb_mois']);
                $fin = $debut->copy()->addMonths($nb - 1)->endOfMonth();
                $premiere = $this->affectationService->creerDepuisPrestation(
                    $agent,
                    $arret->nature->codePaie(),
                    (int) $recalc['montant_mensuel'],
                    $debut->toDateString(),
                    $fin->toDateString(),
                    sprintf('Arrêt #%d — %s (CCN art. %s)', $arret->id, $arret->nature->label(), $arret->nature->articleCcn()),
                    ['arret_sante_id' => $arret->id],
                );
            }

            $arret = $this->charger($this->repository->update($id, array_merge($recalc, [
                'statut' => StatutDossierSante::ACCORDEE->value,
                'date_decision' => $data['date_decision'] ?? now()->toDateString(),
                'commentaire_decision' => $data['commentaire'] ?? null,
                'decideur_id' => Auth::id(),
                'paie_element_affectation_id' => $premiere->id,
            ])));

            $this->notifier($arret, 'accorde', 'L\'arrêt a été accordé par le directeur général.');

            return $arret;
        });
    }

    public function refuser(int $id, string $commentaire): ArretSante
    {
        $arret = $this->charger($this->repository->findById($id));
        abort_unless($arret->statut === StatutDossierSante::INSTRUITE, 422, 'Seul un arrêt instruit peut être refusé.');
        $arret = $this->charger($this->repository->update($id, [
            'statut' => StatutDossierSante::REFUSEE->value,
            'commentaire_decision' => $commentaire,
            'date_decision' => now()->toDateString(),
            'decideur_id' => Auth::id(),
        ]));
        $this->notifier($arret, 'refuse', 'L\'arrêt a été refusé.');

        return $arret;
    }

    public function classer(int $id, ?string $commentaire = null): ArretSante
    {
        $arret = $this->charger($this->repository->findById($id));
        abort_unless(
            in_array($arret->statut, [StatutDossierSante::SOUMISE, StatutDossierSante::INSTRUITE], true),
            422,
            'Seul un arrêt soumis ou instruit peut être classé.'
        );
        $arret = $this->charger($this->repository->update($id, [
            'statut' => StatutDossierSante::CLASSEE->value,
            'commentaire_decision' => $commentaire,
            'date_decision' => now()->toDateString(),
        ]));
        $this->notifier($arret, 'classe', 'L\'arrêt a été classé sans suite.');

        return $arret;
    }

    public function pieces(int $id): Collection
    {
        $this->repository->findById($id);

        return $this->pieceRepository->getByArret($id);
    }

    public function ajouterPiece(int $id, UploadedFile $fichier, string $typePiece): ArretSantePiece
    {
        $arret = $this->repository->findById($id);
        abort_unless($arret->statut->estOuverte(), 422, 'Impossible de joindre une pièce après décision.');
        $path = $fichier->store("affaires-sociales/arrets/{$id}", 'local');

        return $this->pieceRepository->create([
            'arret_sante_id' => $id,
            'type_piece' => $typePiece,
            'fichier_path' => $path,
            'nom_original' => $fichier->getClientOriginalName(),
            'mime_type' => $fichier->getClientMimeType(),
            'taille' => $fichier->getSize(),
            'uploaded_by' => Auth::id(),
        ])->load('uploader:id,name');
    }

    public function telechargerPiece(int $id, int $pieceId): StreamedResponse
    {
        $this->repository->findById($id);
        $piece = $this->pieceRepository->findForArret($id, $pieceId);
        abort_unless(Storage::disk('local')->exists($piece->fichier_path), 404, 'Pièce introuvable.');

        return Storage::disk('local')->download($piece->fichier_path, $piece->nom_original);
    }

    public function supprimerPiece(int $id, int $pieceId): void
    {
        $arret = $this->repository->findById($id);
        abort_unless($arret->statut->estOuverte(), 422, 'Impossible de retirer une pièce après décision.');
        $piece = $this->pieceRepository->findForArret($id, $pieceId);
        $this->supprimerFichier($piece);
        $this->pieceRepository->delete($piece->id);
    }

    public function decisionPdf(int $id): Response
    {
        $arret = $this->charger($this->repository->findById($id));
        abort_unless($arret->statut->aDecision(), 422, 'La décision n\'est disponible qu\'après accord ou refus.');

        return Pdf::loadView('pdf.decision-arret-sante', ['arret' => $arret])
            ->stream("decision-arret-sante-{$arret->id}.pdf");
    }

    /**
     * @return array<string, mixed>
     */
    private function recalculer(ArretSante $arret): array
    {
        $agent = $this->agentRepository->findById((int) $arret->agent_id);
        $snapshot = $this->calcul->simulerArret(
            $arret->nature,
            $agent,
            $arret->date_fait,
            $arret->date_notification,
        );

        return [
            'alerte_72h' => (bool) $snapshot['alerte_72h'],
            'nb_mois' => $snapshot['nb_mois'],
            'nb_mois_majoration' => $snapshot['nb_mois_majoration'],
            'montant_mensuel' => $snapshot['montant_mensuel'],
            'montant_mensuel_demi' => $snapshot['montant_mensuel_demi'],
            'calcul_snapshot' => $snapshot,
        ];
    }

    private function assertStructureMedecin(int $id): void
    {
        $structure = $this->structureRepository->findById($id);
        abort_unless($structure instanceof StructureSanitaire && $structure->actif, 422, 'Cette structure sanitaire n\'est plus agréée.');
        abort_unless(
            in_array($structure->type, [TypeStructureSanitaire::MEDECIN, TypeStructureSanitaire::FORMATION_SANITAIRE], true),
            422,
            'La maladie doit être constatée par un médecin ou une formation sanitaire agréée (CCN art. 131).'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertConge(array $data, int $agentId): void
    {
        if (empty($data['demande_conge_id'])) {
            return;
        }

        $demande = $this->demandeCongeRepository->findById((int) $data['demande_conge_id']);
        abort_unless(
            $demande instanceof DemandeConge && (int) $demande->agent_id === $agentId,
            422,
            'La demande de congé indiquée n\'appartient pas à cet agent.'
        );
    }

    private function charger(ArretSante $arret): ArretSante
    {
        return $arret->load([
            'agent:id,matricule,nom,prenom,statut',
            'structure:id,nom,type',
            'createur:id,name',
            'instructeur:id,name',
            'decideur:id,name',
            'pieces.uploader:id,name',
        ]);
    }

    private function supprimerFichier(ArretSantePiece $piece): void
    {
        if ($piece->fichier_path && Storage::disk('local')->exists($piece->fichier_path)) {
            Storage::disk('local')->delete($piece->fichier_path);
        }
    }

    private function notifier(ArretSante $arret, string $action, string $message, bool $notifierDg = false): void
    {
        $meta = ['arret_sante_id' => $arret->id, 'nature' => $arret->nature->value, 'agent_id' => $arret->agent_id];
        $this->notificationService->notifierEvenementGroupe(
            $this->notificationService->destinatairesAuteurEtAgent($arret->created_by, null),
            'affaires-sociales',
            $action,
            $message,
            $meta,
        );
        $this->notificationService->notifierRole('rh', 'affaires-sociales', $action, $message, $meta);
        if ($notifierDg) {
            $this->notificationService->notifierRole('directeur-general', 'affaires-sociales', $action, $message, $meta);
        }
    }
}
