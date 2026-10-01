<?php

namespace App\Services;

use App\Enums\StatutPrestation;
use App\Enums\TypePrestation;
use App\Interfaces\AgentInterface;
use App\Interfaces\AyantDroitInterface;
use App\Interfaces\PrestationInterface;
use App\Interfaces\PrestationPieceInterface;
use App\Models\Agent;
use App\Models\AyantDroit;
use App\Models\Prestation;
use App\Models\PrestationPiece;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** @property PrestationInterface $repository */
class PrestationService extends BaseService
{
    public function __construct(
        PrestationInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly AyantDroitInterface $ayantDroitRepository,
        private readonly PrestationPieceInterface $pieceRepository,
        private readonly PrestationCalculService $calcul,
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

    public function findById(int $id): Prestation
    {
        return $this->charger($this->repository->findById($id));
    }

    /**
     * @return array<string, mixed>
     */
    public function simuler(int $id): array
    {
        $prestation = $this->repository->findById($id);
        $agent = $this->agentRepository->findById((int) $prestation->agent_id);

        return $this->calcul->assertEligible(
            $prestation->type,
            $agent,
            $prestation->date_fait,
            (bool) $prestation->transport_corps,
            $prestation->montant_demande,
        );
    }

    protected function beforeCreate(array $data): array
    {
        $data = $this->normaliser($data);
        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        $this->assertBeneficiaire($data, $agent);
        $this->assertMontantDemande($data);

        $type = TypePrestation::from((string) $data['type']);
        $dateFait = Carbon::parse((string) $data['date_fait']);
        $snapshot = $this->calcul->assertEligible(
            $type,
            $agent,
            $dateFait,
            (bool) ($data['transport_corps'] ?? false),
            isset($data['montant_demande']) ? (int) $data['montant_demande'] : null,
        );

        $data['statut'] = StatutPrestation::BROUILLON->value;
        $data['montant_calcule'] = $snapshot['montant'];
        $data['calcul_snapshot'] = $snapshot;
        $data['created_by'] = Auth::id();
        $data['transport_corps'] = (bool) ($data['transport_corps'] ?? false);

        return $data;
    }

    protected function afterCreate($model): Prestation
    {
        return $this->charger($model);
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $prestation = $this->repository->findById($id);
        abort_unless(
            $prestation->statut->estModifiable(),
            422,
            'Seule une prestation en brouillon ou soumise peut être modifiée.'
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

        if ($prestation->statut !== StatutPrestation::BROUILLON) {
            unset($data['agent_id'], $data['type']);
        }

        $fusion = array_merge($prestation->toArray(), $data);
        $fusion['type'] = $data['type'] ?? $prestation->type->value;
        $fusion['agent_id'] = $data['agent_id'] ?? $prestation->agent_id;
        $fusion['date_fait'] = $data['date_fait'] ?? $prestation->date_fait?->toDateString();
        $fusion['montant_demande'] = array_key_exists('montant_demande', $data)
            ? $data['montant_demande']
            : $prestation->montant_demande;
        $fusion['transport_corps'] = array_key_exists('transport_corps', $data)
            ? (bool) $data['transport_corps']
            : (bool) $prestation->transport_corps;

        $data = $this->normaliser($data);
        $fusion = $this->normaliser($fusion);

        $agent = $this->agentRepository->findById((int) $fusion['agent_id']);
        $this->assertBeneficiaire($fusion, $agent);
        $this->assertMontantDemande($fusion);

        $type = TypePrestation::from((string) $fusion['type']);
        $dateFait = Carbon::parse((string) $fusion['date_fait']);
        $snapshot = $this->calcul->assertEligible(
            $type,
            $agent,
            $dateFait,
            (bool) $fusion['transport_corps'],
            isset($fusion['montant_demande']) ? (int) $fusion['montant_demande'] : null,
        );

        $data['montant_calcule'] = $snapshot['montant'];
        $data['calcul_snapshot'] = $snapshot;
        if (array_key_exists('transport_corps', $data)) {
            $data['transport_corps'] = (bool) $data['transport_corps'];
        }

        return $data;
    }

    protected function afterUpdate($model): Prestation
    {
        return $this->charger($model);
    }

    public function delete(int $id): bool
    {
        $prestation = $this->repository->findById($id);
        abort_unless(
            $prestation->statut === StatutPrestation::BROUILLON,
            422,
            'Seule une prestation en brouillon peut être supprimée.'
        );

        foreach ($this->pieceRepository->getByPrestation($id) as $piece) {
            $this->supprimerFichier($piece);
            $this->pieceRepository->delete($piece->id);
        }

        return parent::delete($id);
    }

    public function soumettre(int $id): Prestation
    {
        $prestation = $this->charger($this->repository->findById($id));
        abort_unless(
            $prestation->statut === StatutPrestation::BROUILLON,
            422,
            'Seule une prestation en brouillon peut être soumise.'
        );

        $payload = $this->recalculer($prestation);
        $payload['statut'] = StatutPrestation::SOUMISE->value;
        $prestation = $this->charger($this->repository->update($id, $payload));

        $this->notifier(
            $prestation,
            'soumise',
            'Une demande de prestation a été soumise.',
            notifierDg: true,
        );

        return $prestation;
    }

    public function instruire(int $id, array $data): Prestation
    {
        $prestation = $this->charger($this->repository->findById($id));
        abort_unless(
            $prestation->statut === StatutPrestation::SOUMISE,
            422,
            'Seule une prestation soumise peut être instruite.'
        );

        $payload = $this->recalculer($prestation);
        $payload['statut'] = StatutPrestation::INSTRUITE->value;
        $payload['notes_instruction'] = $data['notes_instruction'];
        $payload['instruite_by'] = Auth::id();
        $prestation = $this->charger($this->repository->update($id, $payload));

        $this->notifier(
            $prestation,
            'instruite',
            'La demande de prestation a été instruite et transmise au directeur général.',
            notifierDg: true,
        );

        return $prestation;
    }

    public function accorder(int $id, array $data): Prestation
    {
        return DB::transaction(function () use ($id, $data) {
            $prestation = $this->charger($this->repository->findById($id));
            abort_unless(
                $prestation->statut === StatutPrestation::INSTRUITE,
                422,
                'Seule une prestation instruite peut être accordée par le directeur général.'
            );

            $snapshot = $this->recalculer($prestation);
            abort_if(
                (int) $snapshot['montant_calcule'] <= 0,
                422,
                'Impossible d\'accorder une prestation d\'un montant nul.'
            );

            $annee = (int) ($data['paie_annee'] ?? now()->year);
            $mois = (int) ($data['paie_mois'] ?? now()->month);
            abort_unless($mois >= 1 && $mois <= 12, 422, 'Le mois de pose paie est invalide.');

            $debut = Carbon::create($annee, $mois, 1)->startOfMonth()->toDateString();
            $fin = Carbon::create($annee, $mois, 1)->endOfMonth()->toDateString();
            $agent = $this->agentRepository->findById((int) $prestation->agent_id);

            $affectation = $this->affectationService->creerDepuisPrestation(
                $agent,
                $prestation->type->codePaie(),
                (int) $snapshot['montant_calcule'],
                $debut,
                $fin,
                sprintf('Prestation #%d — %s (CCN art. %s)', $prestation->id, $prestation->type->label(), $prestation->type->articleCcn()),
                ['prestation_id' => $prestation->id],
            );

            $prestation = $this->charger($this->repository->update($id, array_merge($snapshot, [
                'statut' => StatutPrestation::ACCORDEE->value,
                'montant_accorde' => (int) $snapshot['montant_calcule'],
                'date_decision' => $data['date_decision'] ?? now()->toDateString(),
                'commentaire_decision' => $data['commentaire'] ?? null,
                'decideur_id' => Auth::id(),
                'paie_annee' => $annee,
                'paie_mois' => $mois,
                'paie_element_affectation_id' => $affectation->id,
            ])));

            $this->notifier(
                $prestation,
                'accordee',
                'La prestation a été accordée par le directeur général.',
            );

            return $prestation;
        });
    }

    public function refuser(int $id, string $commentaire): Prestation
    {
        $prestation = $this->charger($this->repository->findById($id));
        abort_unless(
            $prestation->statut === StatutPrestation::INSTRUITE,
            422,
            'Seule une prestation instruite peut être refusée.'
        );

        $prestation = $this->charger($this->repository->update($id, [
            'statut' => StatutPrestation::REFUSEE->value,
            'commentaire_decision' => $commentaire,
            'date_decision' => now()->toDateString(),
            'decideur_id' => Auth::id(),
        ]));

        $this->notifier($prestation, 'refusee', 'La prestation a été refusée par le directeur général.');

        return $prestation;
    }

    public function classer(int $id, ?string $commentaire = null): Prestation
    {
        $prestation = $this->charger($this->repository->findById($id));
        abort_unless(
            in_array($prestation->statut, [StatutPrestation::SOUMISE, StatutPrestation::INSTRUITE], true),
            422,
            'Seule une prestation soumise ou instruite peut être classée sans suite.'
        );

        $prestation = $this->charger($this->repository->update($id, [
            'statut' => StatutPrestation::CLASSEE->value,
            'commentaire_decision' => $commentaire,
            'date_decision' => now()->toDateString(),
        ]));

        $this->notifier($prestation, 'classee', 'La demande de prestation a été classée sans suite.');

        return $prestation;
    }

    public function pieces(int $id): Collection
    {
        $this->repository->findById($id);

        return $this->pieceRepository->getByPrestation($id);
    }

    public function ajouterPiece(int $id, UploadedFile $fichier, string $typePiece): PrestationPiece
    {
        $prestation = $this->repository->findById($id);
        abort_unless(
            $prestation->statut->estOuverte(),
            422,
            'Impossible de joindre une pièce après décision.'
        );

        $path = $fichier->store("affaires-sociales/prestations/{$id}", 'local');

        return $this->pieceRepository->create([
            'prestation_id' => $id,
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
        $piece = $this->pieceRepository->findForPrestation($id, $pieceId);

        abort_unless(
            Storage::disk('local')->exists($piece->fichier_path),
            404,
            'Pièce introuvable.'
        );

        return Storage::disk('local')->download($piece->fichier_path, $piece->nom_original);
    }

    public function supprimerPiece(int $id, int $pieceId): void
    {
        $prestation = $this->repository->findById($id);
        abort_unless(
            $prestation->statut->estOuverte(),
            422,
            'Impossible de retirer une pièce après décision.'
        );

        $piece = $this->pieceRepository->findForPrestation($id, $pieceId);
        $this->supprimerFichier($piece);
        $this->pieceRepository->delete($piece->id);
    }

    public function decisionPdf(int $id): Response
    {
        $prestation = $this->charger($this->repository->findById($id));
        abort_unless(
            $prestation->statut->aDecision(),
            422,
            'La décision n\'est disponible qu\'après accord ou refus du directeur général.'
        );

        return Pdf::loadView('pdf.decision-prestation', ['prestation' => $prestation])
            ->stream("decision-prestation-{$prestation->id}.pdf");
    }

    /**
     * @return array<string, mixed>
     */
    private function recalculer(Prestation $prestation): array
    {
        $agent = $this->agentRepository->findById((int) $prestation->agent_id);
        $snapshot = $this->calcul->assertEligible(
            $prestation->type,
            $agent,
            $prestation->date_fait,
            (bool) $prestation->transport_corps,
            $prestation->montant_demande,
        );

        return [
            'montant_calcule' => $snapshot['montant'],
            'calcul_snapshot' => $snapshot,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normaliser(array $data): array
    {
        if (array_key_exists('transport_corps', $data)) {
            $data['transport_corps'] = (bool) $data['transport_corps'];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertBeneficiaire(array $data, Agent $agent): void
    {
        $type = TypePrestation::from((string) $data['type']);
        $ayantId = $data['ayant_droit_id'] ?? null;

        if ($ayantId) {
            $ayant = $this->ayantDroitRepository->findById((int) $ayantId);
            abort_unless(
                $ayant instanceof AyantDroit && (int) $ayant->agent_id === (int) $agent->id,
                422,
                'L\'ayant droit indiqué n\'appartient pas à cet agent.'
            );
        }

        abort_if(
            $type->estDeces() && blank($ayantId) && blank($data['beneficiaire_libelle'] ?? null),
            422,
            'Indiquez un ayant droit ou le libellé du bénéficiaire.'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertMontantDemande(array $data): void
    {
        $type = TypePrestation::from((string) $data['type']);
        if (! $type->exigeMontantDemande()) {
            return;
        }

        $montant = $data['montant_demande'] ?? null;
        abort_if(
            $montant === null || (int) $montant < 1,
            422,
            'Indiquez le montant des frais funéraires justifiés.'
        );
        abort_if(
            (int) $montant > TypePrestation::PLAFOND_FUNERAIRES,
            422,
            'Les frais funéraires ne peuvent excéder 2 000 000 F (CCN art. 121).'
        );
    }

    private function charger(Prestation $prestation): Prestation
    {
        return $prestation->load([
            'agent:id,matricule,nom,prenom,statut',
            'ayantDroit:id,nom,prenom,type',
            'createur:id,name',
            'instructeur:id,name',
            'decideur:id,name',
            'affectationPaie',
            'pieces.uploader:id,name',
        ]);
    }

    private function notifier(Prestation $prestation, string $action, string $message, bool $notifierDg = false): void
    {
        $meta = [
            'prestation_id' => $prestation->id,
            'type' => $prestation->type->value,
            'agent_id' => $prestation->agent_id,
        ];

        $destinataires = $this->notificationService->destinatairesAuteurEtAgent(
            $prestation->created_by,
            null,
        );
        $this->notificationService->notifierEvenementGroupe(
            $destinataires,
            'affaires-sociales',
            $action,
            $message,
            $meta,
        );
        $this->notificationService->notifierRole('rh', 'affaires-sociales', $action, $message, $meta);

        if ($notifierDg) {
            $this->notificationService->notifierRole(
                'directeur-general',
                'affaires-sociales',
                $action,
                $message,
                $meta,
            );
        }
    }
}
