<?php

namespace App\Services;

use App\Interfaces\AgentInterface;
use App\Interfaces\DocumentAgentInterface;
use App\Interfaces\TypeDocumentInterface;
use App\Models\DocumentAgent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentAgentService extends BaseService
{
    public function __construct(
        DocumentAgentInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly TypeDocumentInterface $typeDocumentRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId, array $filters = []): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId, $filters);
    }

    public function arborescence(int $agentId): array
    {
        $docs = $this->getByAgent($agentId);

        return $docs
            ->groupBy(fn (DocumentAgent $doc) => $doc->sous_dossier ?: 'general')
            ->map(fn (Collection $groupe, string $dossier) => [
                'sous_dossier' => $dossier,
                'documents'    => $groupe->values(),
            ])
            ->values()
            ->all();
    }

    public function upload(int $agentId, array $data, UploadedFile $fichier): DocumentAgent
    {
        $this->assertModifiable($agentId);
        $this->typeDocumentRepository->findById((int) $data['type_document_id']);

        $data['agent_id']      = $agentId;
        $data['chemin_fichier'] = $fichier->store("agents/{$agentId}/documents", 'local');
        $data['nom_original']  = $fichier->getClientOriginalName();
        $data['taille']        = $fichier->getSize();
        $data['mime_type']     = $fichier->getClientMimeType();
        $data['sous_dossier']  = $data['sous_dossier'] ?? 'general';

        $document = $this->repository->create($data);

        return $document->load('typeDocument');
    }

    public function findForAgent(int $agentId, int $id): DocumentAgent
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->findForAgent($agentId, $id);
    }

    public function telecharger(int $agentId, int $id): StreamedResponse
    {
        $document = $this->findForAgent($agentId, $id);

        abort_unless(
            Storage::disk('local')->exists($document->chemin_fichier),
            404,
            'Fichier introuvable.'
        );

        return Storage::disk('local')->download($document->chemin_fichier, $document->nom_original);
    }

    public function supprimer(int $agentId, int $id): void
    {
        $this->assertModifiable($agentId);
        $document = $this->repository->findForAgent($agentId, $id);
        $this->repository->delete($document->id);
    }

    private function assertModifiable(int $agentId): void
    {
        $agent = $this->agentRepository->findById($agentId);
        abort_if($agent->statut === 'archive', 422, 'Cet agent est archivé : dossier en lecture seule.');
    }
}
