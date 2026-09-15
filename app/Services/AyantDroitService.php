<?php

namespace App\Services;

use App\Enums\LienJuridiqueAyantDroit;
use App\Enums\QualiteAgeAyantDroit;
use App\Enums\TypeAyantDroit;
use App\Interfaces\AgentInterface;
use App\Interfaces\AyantDroitInterface;
use App\Interfaces\AyantDroitPieceInterface;
use App\Interfaces\SituationFamilialeInterface;
use App\Models\Agent;
use App\Models\AyantDroit;
use App\Models\AyantDroitPiece;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** @property AyantDroitInterface $repository */
class AyantDroitService extends BaseService
{
    public function __construct(
        AyantDroitInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly SituationFamilialeInterface $situationFamilialeRepository,
        private readonly AyantDroitPieceInterface $pieceRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId);
    }

    public function findById(int $id): AyantDroit
    {
        return $this->repository->findById($id)->load(['agent:id,matricule,nom,prenom,statut', 'pieces.uploader:id,name']);
    }

    protected function beforeCreate(array $data): array
    {
        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        $this->assertAgentModifiable($agent);
        $data = $this->normaliser($data);
        $this->assertReglesCcn($data);

        return $data;
    }

    protected function afterCreate($model): AyantDroit
    {
        $this->synchroniserNbEnfants((int) $model->agent_id);

        return $model->load(['agent:id,matricule,nom,prenom,statut', 'pieces']);
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $ayantDroit = $this->repository->findById($id);
        $this->assertAgentModifiable($this->agentRepository->findById($ayantDroit->agent_id));

        $data['agent_id'] = $ayantDroit->agent_id;
        $fusion = array_merge($ayantDroit->toArray(), $data);
        $fusion['type'] = $data['type'] ?? $ayantDroit->type?->value;
        $fusion['lien_juridique'] = $data['lien_juridique'] ?? $ayantDroit->lien_juridique?->value;
        $fusion['qualite_age'] = $data['qualite_age'] ?? $ayantDroit->qualite_age?->value;
        $fusion['actif'] = $data['actif'] ?? $ayantDroit->actif;

        $data = $this->normaliser($data);
        $this->assertReglesCcn($this->normaliser($fusion), $id);

        return $data;
    }

    protected function afterUpdate($model): AyantDroit
    {
        $this->synchroniserNbEnfants((int) $model->agent_id);

        return $model->load(['agent:id,matricule,nom,prenom,statut', 'pieces']);
    }

    public function delete(int $id): bool
    {
        $ayantDroit = $this->repository->findById($id);
        $this->assertAgentModifiable($this->agentRepository->findById($ayantDroit->agent_id));

        foreach ($this->pieceRepository->getByAyantDroit($id) as $piece) {
            $this->supprimerFichier($piece);
            $this->pieceRepository->delete($piece->id);
        }

        $ok = $this->repository->delete($id);
        $this->synchroniserNbEnfants((int) $ayantDroit->agent_id);

        return $ok;
    }

    public function pieces(int $id): Collection
    {
        $this->repository->findById($id);

        return $this->pieceRepository->getByAyantDroit($id);
    }

    public function ajouterPiece(int $id, UploadedFile $fichier, string $typePiece): AyantDroitPiece
    {
        $ayantDroit = $this->repository->findById($id);
        $this->assertAgentModifiable($this->agentRepository->findById($ayantDroit->agent_id));

        $path = $fichier->store("affaires-sociales/ayants-droit/{$id}", 'local');

        return $this->pieceRepository->create([
            'ayant_droit_id' => $id,
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
        $piece = $this->pieceRepository->findForAyantDroit($id, $pieceId);

        abort_unless(
            Storage::disk('local')->exists($piece->fichier_path),
            404,
            'Pièce introuvable.'
        );

        return Storage::disk('local')->download($piece->fichier_path, $piece->nom_original);
    }

    public function supprimerPiece(int $id, int $pieceId): void
    {
        $ayantDroit = $this->repository->findById($id);
        $this->assertAgentModifiable($this->agentRepository->findById($ayantDroit->agent_id));

        $piece = $this->pieceRepository->findForAyantDroit($id, $pieceId);
        $this->supprimerFichier($piece);
        $this->pieceRepository->delete($piece->id);
    }

    public function synchroniserNbEnfants(int $agentId): void
    {
        $nb = $this->repository->getByAgent($agentId)
            ->filter(fn (AyantDroit $item) => $item->type === TypeAyantDroit::ENFANT && $item->estACharge())
            ->count();

        $existant = $this->situationFamilialeRepository->findByAgent($agentId);

        if ($existant === null && $nb === 0) {
            return;
        }

        $this->situationFamilialeRepository->upsertForAgent($agentId, [
            'nb_enfants' => $nb,
        ]);
    }

    private function normaliser(array $data): array
    {
        if (($data['type'] ?? null) === TypeAyantDroit::CONJOINT->value) {
            $data['qualite_age'] = null;
        } elseif (($data['type'] ?? null) === TypeAyantDroit::ENFANT->value) {
            $data['qualite_age'] = $data['qualite_age'] ?? QualiteAgeAyantDroit::STANDARD->value;
        }

        if (isset($data['date_debut'], $data['date_fin']) && $data['date_fin'] !== null) {
            abort_if(
                $data['date_fin'] < $data['date_debut'],
                422,
                'La date de fin doit être postérieure ou égale à la date de début.'
            );
        }

        return $data;
    }

    private function assertReglesCcn(array $data, ?int $excludeId = null): void
    {
        $type = TypeAyantDroit::from($data['type']);
        $lien = LienJuridiqueAyantDroit::from($data['lien_juridique']);
        $agentId = (int) $data['agent_id'];
        $actif = filter_var($data['actif'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if ($type === TypeAyantDroit::CONJOINT) {
            abort_unless(
                in_array($lien, LienJuridiqueAyantDroit::pourConjoint(), true),
                422,
                'Le conjoint doit être lié par mariage ou union libre.'
            );

            if ($actif) {
                abort_if(
                    $this->repository->findConjointActif($agentId, $excludeId) !== null,
                    422,
                    'Un conjoint actif est déjà déclaré pour cet agent.'
                );
            }
        }

        if ($type === TypeAyantDroit::ENFANT) {
            abort_unless(
                in_array($lien, LienJuridiqueAyantDroit::pourEnfant(), true),
                422,
                'Lien juridique invalide pour un enfant (CCN art. 59).'
            );

            if ($actif && $lien === LienJuridiqueAyantDroit::TUTELLE) {
                abort_if(
                    $this->repository->countTutelleActives($agentId, $excludeId) >= 2,
                    422,
                    'Le nombre d\'enfants sous tutelle est limité à deux (CCN art. 59).'
                );
            }
        }
    }

    private function assertAgentModifiable(Agent $agent): void
    {
        abort_if($agent->statut === 'archive', 422, 'Cet agent est archivé : dossier en lecture seule.');
    }

    private function supprimerFichier(AyantDroitPiece $piece): void
    {
        if ($piece->fichier_path && Storage::disk('local')->exists($piece->fichier_path)) {
            Storage::disk('local')->delete($piece->fichier_path);
        }
    }
}
