<?php

namespace App\Services;

use App\Interfaces\DocumentDossierInterface;
use App\Interfaces\DossierIntegrationInterface;
use App\Interfaces\TypeDocumentInterface;
use App\Models\DocumentDossier;
use App\Models\TypeDocument;
use App\Models\TypeIntegration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DocumentDossierService extends BaseService
{
    public function __construct(
        DocumentDossierInterface $repository,
        private readonly DossierIntegrationInterface $dossierRepository,
        private readonly TypeDocumentInterface $typeDocumentRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByDossier(int $dossierId): Collection
    {
        return $this->repository->getByDossier($dossierId);
    }

    /**
     * @return array{deposes: Collection, manquants: Collection, resume: array<string, int|bool>}
     */
    public function getEtatDocuments(int $dossierId): array
    {
        $dossier = $this->dossierRepository->findById($dossierId);
        $dossier->load('typeIntegration');

        $deposes  = $this->repository->getByDossier($dossierId);
        $attendus = $this->resoudreDocumentsAttendus($dossier->typeIntegration);

        $idsDeposes = $deposes->pluck('type_document_id')->unique();

        $manquants = $attendus
            ->reject(fn (TypeDocument $type) => $idsDeposes->contains($type->id))
            ->map(fn (TypeDocument $type) => [
                'type_document'   => $type,
                'est_obligatoire' => $this->estObligatoirePourType($type, $dossier->typeIntegration),
            ])
            ->values();

        return [
            'deposes'   => $deposes,
            'manquants' => $manquants,
            'resume'    => $this->calculerResume($attendus, $deposes, $dossier->typeIntegration),
        ];
    }

    public function valider(int $id, ?string $commentaire = null): DocumentDossier
    {
        return $this->repository->validerDocument($id, Auth::id(), $commentaire);
    }

    public function tousObligatoiresValides(int $dossierId): bool
    {
        return $this->tousObligatoiresDeposes($dossierId)
            && $this->getDocumentsObligatoiresNonValides($dossierId)->isEmpty();
    }

    public function tousObligatoiresDeposes(int $dossierId): bool
    {
        return $this->getEtatDocuments($dossierId)['resume']['tous_obligatoires_deposes'];
    }

    public function getDocumentsObligatoiresManquants(int $dossierId): Collection
    {
        $etat = $this->getEtatDocuments($dossierId);

        return collect($etat['manquants'])
            ->filter(fn (array $item) => $item['est_obligatoire'])
            ->values();
    }

    public function getDocumentsObligatoiresNonValides(int $dossierId): Collection
    {
        return $this->getDocumentsObligatoiresDeposes($dossierId)
            ->reject(fn (DocumentDossier $document) => $document->est_valide)
            ->values();
    }

    private function getDocumentsObligatoiresDeposes(int $dossierId): Collection
    {
        $idsObligatoires = $this->getIdsObligatoiresAttendus($dossierId);

        return $this->repository->getByDossier($dossierId)
            ->filter(fn (DocumentDossier $document) => in_array($document->type_document_id, $idsObligatoires, true))
            ->values();
    }

    /**
     * @return list<int>
     */
    private function getIdsObligatoiresAttendus(int $dossierId): array
    {
        $dossier = $this->dossierRepository->findById($dossierId);
        $dossier->load('typeIntegration');

        return $this->resoudreDocumentsAttendus($dossier->typeIntegration)
            ->filter(fn (TypeDocument $type) => $this->estObligatoirePourType($type, $dossier->typeIntegration))
            ->pluck('id')
            ->all();
    }

    private function resoudreDocumentsAttendus(TypeIntegration $typeIntegration): Collection
    {
        $typeIntegration->loadMissing('documentsObligatoires');

        if ($typeIntegration->documentsObligatoires->isNotEmpty()) {
            return $typeIntegration->documentsObligatoires->values();
        }

        return $this->typeDocumentRepository->getAll()
            ->filter(fn (TypeDocument $type) => $type->obligatoire)
            ->values();
    }

    private function estObligatoirePourType(TypeDocument $type, TypeIntegration $typeIntegration): bool
    {
        $typeIntegration->loadMissing('documentsObligatoires');
        $ids = $typeIntegration->documentsObligatoires->pluck('id')->all();

        if ($ids !== []) {
            return in_array($type->id, $ids, true);
        }

        return $type->obligatoire;
    }

    /**
     * @return array<string, int|bool>
     */
    private function calculerResume(Collection $attendus, Collection $deposes, TypeIntegration $typeIntegration): array
    {
        $idsDeposes = $deposes->pluck('type_document_id')->unique();

        $obligatoires = $attendus->filter(
            fn (TypeDocument $type) => $this->estObligatoirePourType($type, $typeIntegration)
        );
        $optionnels = $attendus->reject(
            fn (TypeDocument $type) => $this->estObligatoirePourType($type, $typeIntegration)
        );

        $obligatoiresDeposes = $obligatoires->filter(fn (TypeDocument $type) => $idsDeposes->contains($type->id));
        $optionnelsDeposes   = $optionnels->filter(fn (TypeDocument $type) => $idsDeposes->contains($type->id));

        return [
            'total_deposes'             => $deposes->count(),
            'types_deposes'             => $idsDeposes->count(),
            'obligatoires_attendus'     => $obligatoires->count(),
            'obligatoires_deposes'      => $obligatoiresDeposes->count(),
            'obligatoires_manquants'    => $obligatoires->count() - $obligatoiresDeposes->count(),
            'optionnels_attendus'       => $optionnels->count(),
            'optionnels_deposes'        => $optionnelsDeposes->count(),
            'optionnels_manquants'      => $optionnels->count() - $optionnelsDeposes->count(),
            'tous_obligatoires_deposes' => $obligatoires->count() === $obligatoiresDeposes->count(),
        ];
    }
}
