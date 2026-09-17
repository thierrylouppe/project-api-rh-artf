<?php

namespace App\Services;

use App\Enums\CodePaieElement;
use App\Enums\StatutDossierSante;
use App\Enums\TypePriseEnCharge;
use App\Interfaces\AgentInterface;
use App\Interfaces\PriseEnChargeInterface;
use App\Interfaces\PriseEnChargePieceInterface;
use App\Interfaces\StructureSanitaireInterface;
use App\Models\PriseEnCharge;
use App\Models\PriseEnChargePiece;
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

/** @property PriseEnChargeInterface $repository */
class PriseEnChargeService extends BaseService
{
    public function __construct(
        PriseEnChargeInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly StructureSanitaireInterface $structureRepository,
        private readonly PriseEnChargePieceInterface $pieceRepository,
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

    public function findById(int $id): PriseEnCharge
    {
        return $this->charger($this->repository->findById($id));
    }

    /**
     * @return array<string, mixed>
     */
    public function simuler(int $id): array
    {
        $dossier = $this->repository->findById($id);

        return $this->calculer($dossier->toArray() + [
            'type' => $dossier->type->value,
            'date_soins' => $dossier->date_soins?->toDateString(),
            'date_debut' => $dossier->date_debut?->toDateString(),
            'date_fin' => $dossier->date_fin?->toDateString(),
        ]);
    }

    protected function beforeCreate(array $data): array
    {
        $snapshot = $this->calculer($data);
        $data['statut'] = StatutDossierSante::BROUILLON->value;
        $data['montant_calcule'] = $snapshot['montant'];
        $data['calcul_snapshot'] = $snapshot;
        $data['at_mp'] = (bool) ($data['at_mp'] ?? false);
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate($model): PriseEnCharge
    {
        return $this->charger($model);
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $dossier = $this->repository->findById($id);
        abort_unless(
            $dossier->statut->estModifiable(),
            422,
            'Seule une prise en charge en brouillon ou soumise peut être modifiée.'
        );

        unset(
            $data['statut'],
            $data['created_by'],
            $data['instruite_by'],
            $data['decideur_id'],
            $data['montant_accorde'],
            $data['date_decision'],
            $data['paie_element_affectation_id'],
        );

        if ($dossier->statut !== StatutDossierSante::BROUILLON) {
            unset($data['agent_id'], $data['type']);
        }

        $fusion = array_merge($dossier->toArray(), $data);
        $fusion['type'] = $data['type'] ?? $dossier->type->value;
        $fusion['date_soins'] = $data['date_soins'] ?? $dossier->date_soins?->toDateString();
        $fusion['date_debut'] = array_key_exists('date_debut', $data) ? $data['date_debut'] : $dossier->date_debut?->toDateString();
        $fusion['date_fin'] = array_key_exists('date_fin', $data) ? $data['date_fin'] : $dossier->date_fin?->toDateString();
        $fusion['at_mp'] = array_key_exists('at_mp', $data) ? (bool) $data['at_mp'] : (bool) $dossier->at_mp;

        $snapshot = $this->calculer($fusion);
        $data['montant_calcule'] = $snapshot['montant'];
        $data['calcul_snapshot'] = $snapshot;
        if (array_key_exists('at_mp', $data)) {
            $data['at_mp'] = (bool) $data['at_mp'];
        }

        return $data;
    }

    protected function afterUpdate($model): PriseEnCharge
    {
        return $this->charger($model);
    }

    public function delete(int $id): bool
    {
        $dossier = $this->repository->findById($id);
        abort_unless(
            $dossier->statut === StatutDossierSante::BROUILLON,
            422,
            'Seule une prise en charge en brouillon peut être supprimée.'
        );

        foreach ($this->pieceRepository->getByPriseEnCharge($id) as $piece) {
            $this->supprimerFichier($piece);
            $this->pieceRepository->delete($piece->id);
        }

        return parent::delete($id);
    }

    public function soumettre(int $id): PriseEnCharge
    {
        $dossier = $this->charger($this->repository->findById($id));
        abort_unless($dossier->statut === StatutDossierSante::BROUILLON, 422, 'Seule une prise en charge en brouillon peut être soumise.');

        $payload = $this->recalculer($dossier);
        $payload['statut'] = StatutDossierSante::SOUMISE->value;
        $dossier = $this->charger($this->repository->update($id, $payload));
        $this->notifier($dossier, 'soumise', 'Une prise en charge santé a été soumise.', true);

        return $dossier;
    }

    public function instruire(int $id, array $data): PriseEnCharge
    {
        $dossier = $this->charger($this->repository->findById($id));
        abort_unless($dossier->statut === StatutDossierSante::SOUMISE, 422, 'Seule une prise en charge soumise peut être instruite.');

        $payload = $this->recalculer($dossier);
        $payload['statut'] = StatutDossierSante::INSTRUITE->value;
        $payload['notes_instruction'] = $data['notes_instruction'];
        $payload['instruite_by'] = Auth::id();
        $dossier = $this->charger($this->repository->update($id, $payload));
        $this->notifier($dossier, 'instruite', 'La prise en charge a été instruite et transmise au directeur général.', true);

        return $dossier;
    }

    public function accorder(int $id, array $data): PriseEnCharge
    {
        return DB::transaction(function () use ($id, $data) {
            $dossier = $this->charger($this->repository->findById($id));
            abort_unless(
                $dossier->statut === StatutDossierSante::INSTRUITE,
                422,
                'Seule une prise en charge instruite peut être accordée.'
            );

            $recalc = $this->recalculer($dossier);
            $montant = (int) $recalc['montant_calcule'];
            $affectationId = null;

            if ($montant > 0) {
                $annee = (int) ($data['paie_annee'] ?? now()->year);
                $mois = (int) ($data['paie_mois'] ?? now()->month);
                $debut = Carbon::create($annee, $mois, 1)->startOfMonth()->toDateString();
                $fin = Carbon::create($annee, $mois, 1)->endOfMonth()->toDateString();
                $agent = $this->agentRepository->findById((int) $dossier->agent_id);
                $affectation = $this->affectationService->creerDepuisPrestation(
                    $agent,
                    CodePaieElement::REMBOURSEMENT_SANTE,
                    $montant,
                    $debut,
                    $fin,
                    sprintf('Prise en charge #%d — %s (CCN art. %s)', $dossier->id, $dossier->type->label(), $dossier->type->articleCcn()),
                    ['prise_en_charge_id' => $dossier->id],
                );
                $affectationId = $affectation->id;
                $recalc['paie_annee'] = $annee;
                $recalc['paie_mois'] = $mois;
                $recalc['paie_element_affectation_id'] = $affectationId;
            }

            $dossier = $this->charger($this->repository->update($id, array_merge($recalc, [
                'statut' => StatutDossierSante::ACCORDEE->value,
                'montant_accorde' => $montant,
                'date_decision' => $data['date_decision'] ?? now()->toDateString(),
                'commentaire_decision' => $data['commentaire'] ?? null,
                'decideur_id' => Auth::id(),
            ])));

            $this->notifier($dossier, 'accordee', 'La prise en charge a été accordée par le directeur général.');

            return $dossier;
        });
    }

    public function refuser(int $id, string $commentaire): PriseEnCharge
    {
        $dossier = $this->charger($this->repository->findById($id));
        abort_unless($dossier->statut === StatutDossierSante::INSTRUITE, 422, 'Seule une prise en charge instruite peut être refusée.');

        $dossier = $this->charger($this->repository->update($id, [
            'statut' => StatutDossierSante::REFUSEE->value,
            'commentaire_decision' => $commentaire,
            'date_decision' => now()->toDateString(),
            'decideur_id' => Auth::id(),
        ]));
        $this->notifier($dossier, 'refusee', 'La prise en charge a été refusée.');

        return $dossier;
    }

    public function classer(int $id, ?string $commentaire = null): PriseEnCharge
    {
        $dossier = $this->charger($this->repository->findById($id));
        abort_unless(
            in_array($dossier->statut, [StatutDossierSante::SOUMISE, StatutDossierSante::INSTRUITE], true),
            422,
            'Seule une prise en charge soumise ou instruite peut être classée.'
        );

        $dossier = $this->charger($this->repository->update($id, [
            'statut' => StatutDossierSante::CLASSEE->value,
            'commentaire_decision' => $commentaire,
            'date_decision' => now()->toDateString(),
        ]));
        $this->notifier($dossier, 'classee', 'La prise en charge a été classée sans suite.');

        return $dossier;
    }

    public function pieces(int $id): Collection
    {
        $this->repository->findById($id);

        return $this->pieceRepository->getByPriseEnCharge($id);
    }

    public function ajouterPiece(int $id, UploadedFile $fichier, string $typePiece): PriseEnChargePiece
    {
        $dossier = $this->repository->findById($id);
        abort_unless($dossier->statut->estOuverte(), 422, 'Impossible de joindre une pièce après décision.');
        $path = $fichier->store("affaires-sociales/prises-en-charge/{$id}", 'local');

        return $this->pieceRepository->create([
            'prise_en_charge_id' => $id,
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
        $piece = $this->pieceRepository->findForPriseEnCharge($id, $pieceId);
        abort_unless(Storage::disk('local')->exists($piece->fichier_path), 404, 'Pièce introuvable.');

        return Storage::disk('local')->download($piece->fichier_path, $piece->nom_original);
    }

    public function supprimerPiece(int $id, int $pieceId): void
    {
        $dossier = $this->repository->findById($id);
        abort_unless($dossier->statut->estOuverte(), 422, 'Impossible de retirer une pièce après décision.');
        $piece = $this->pieceRepository->findForPriseEnCharge($id, $pieceId);
        $this->supprimerFichier($piece);
        $this->pieceRepository->delete($piece->id);
    }

    public function decisionPdf(int $id): Response
    {
        $dossier = $this->charger($this->repository->findById($id));
        abort_unless($dossier->statut->aDecision(), 422, 'La décision n\'est disponible qu\'après accord ou refus.');

        return Pdf::loadView('pdf.decision-prise-en-charge', ['dossier' => $dossier])
            ->stream("decision-prise-en-charge-{$dossier->id}.pdf");
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function calculer(array $data): array
    {
        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        $structure = $this->structureRepository->findById((int) $data['structure_sanitaire_id']);
        abort_unless($structure instanceof StructureSanitaire, 404, 'Structure sanitaire introuvable.');

        return $this->calcul->simulerPriseEnCharge(
            TypePriseEnCharge::from((string) $data['type']),
            $agent,
            $structure,
            Carbon::parse((string) $data['date_soins']),
            isset($data['montant_facture']) ? (int) $data['montant_facture'] : null,
            isset($data['ayant_droit_id']) ? (int) $data['ayant_droit_id'] : null,
            (bool) ($data['at_mp'] ?? false),
            $data['date_debut'] ?? null,
            $data['date_fin'] ?? null,
            $data['lieu'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function recalculer(PriseEnCharge $dossier): array
    {
        $snapshot = $this->calculer([
            'agent_id' => $dossier->agent_id,
            'type' => $dossier->type->value,
            'date_soins' => $dossier->date_soins?->toDateString(),
            'structure_sanitaire_id' => $dossier->structure_sanitaire_id,
            'montant_facture' => $dossier->montant_facture,
            'ayant_droit_id' => $dossier->ayant_droit_id,
            'at_mp' => $dossier->at_mp,
            'date_debut' => $dossier->date_debut?->toDateString(),
            'date_fin' => $dossier->date_fin?->toDateString(),
            'lieu' => $dossier->lieu,
        ]);

        return [
            'montant_calcule' => $snapshot['montant'],
            'calcul_snapshot' => $snapshot,
        ];
    }

    private function charger(PriseEnCharge $dossier): PriseEnCharge
    {
        return $dossier->load([
            'agent:id,matricule,nom,prenom,statut',
            'ayantDroit:id,nom,prenom,type',
            'structure:id,nom,type',
            'createur:id,name',
            'instructeur:id,name',
            'decideur:id,name',
            'pieces.uploader:id,name',
        ]);
    }

    private function supprimerFichier(PriseEnChargePiece $piece): void
    {
        if ($piece->fichier_path && Storage::disk('local')->exists($piece->fichier_path)) {
            Storage::disk('local')->delete($piece->fichier_path);
        }
    }

    private function notifier(PriseEnCharge $dossier, string $action, string $message, bool $notifierDg = false): void
    {
        $meta = [
            'prise_en_charge_id' => $dossier->id,
            'type' => $dossier->type->value,
            'agent_id' => $dossier->agent_id,
        ];
        $this->notificationService->notifierEvenementGroupe(
            $this->notificationService->destinatairesAuteurEtAgent($dossier->created_by, null),
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
