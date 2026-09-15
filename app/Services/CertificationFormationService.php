<?php

namespace App\Services;

use App\Interfaces\AgentInterface;
use App\Interfaces\CatalogueFormationInterface;
use App\Interfaces\CertificationFormationInterface;
use App\Interfaces\DiplomeInterface;
use App\Interfaces\InscriptionFormationInterface;
use App\Models\CertificationFormation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** @property CertificationFormationInterface $repository */
class CertificationFormationService extends BaseService
{
    public function __construct(
        CertificationFormationInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly CatalogueFormationInterface $formationRepository,
        private readonly InscriptionFormationInterface $inscriptionRepository,
        private readonly DiplomeInterface $diplomeRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId);
    }

    public function findById(int $id): CertificationFormation
    {
        return $this->repository->findById($id)
            ->load(['agent:id,matricule,nom,prenom,statut', 'formation', 'diplome', 'uploader:id,name']);
    }

    public function create(array $data, ?UploadedFile $fichier = null): CertificationFormation
    {
        $this->agentRepository->findById((int) $data['agent_id']);
        $this->formationRepository->findById((int) $data['formation_id']);

        if (! empty($data['inscription_id'])) {
            $inscription = $this->inscriptionRepository->findById((int) $data['inscription_id']);
            abort_unless(
                (int) $inscription->agent_id === (int) $data['agent_id']
                    && (int) $inscription->formation_id === (int) $data['formation_id'],
                422,
                'L\'inscription ne correspond pas à cet agent et cette formation.'
            );
        }

        if (! empty($data['diplome_id'])) {
            $this->diplomeRepository->findById((int) $data['diplome_id']);
        }

        $data['uploaded_by'] = Auth::id();

        $certif = parent::create($data);

        if ($fichier) {
            $path = $fichier->store("formations/certifications/{$certif->id}", 'local');
            $certif = $this->repository->update($certif->id, [
                'fichier_path' => $path,
                'nom_original' => $fichier->getClientOriginalName(),
                'mime_type' => $fichier->getClientMimeType(),
                'taille' => $fichier->getSize(),
            ]);
        }

        return $certif->load(['agent:id,matricule,nom,prenom,statut', 'formation', 'diplome', 'uploader:id,name']);
    }

    public function telecharger(int $id): StreamedResponse
    {
        $certif = $this->repository->findById($id);
        abort_if(! $certif->fichier_path, 404, 'Aucune pièce jointe.');
        abort_unless(
            Storage::disk('local')->exists($certif->fichier_path),
            404,
            'Pièce introuvable.'
        );

        return Storage::disk('local')->download($certif->fichier_path, $certif->nom_original);
    }

    public function delete(int $id): bool
    {
        $certif = $this->repository->findById($id);
        if ($certif->fichier_path && Storage::disk('local')->exists($certif->fichier_path)) {
            Storage::disk('local')->delete($certif->fichier_path);
        }

        return $this->repository->delete($id);
    }
}
