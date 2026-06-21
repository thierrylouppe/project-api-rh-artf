<?php

namespace App\Services;

use App\Interfaces\DocumentDossierInterface;
use App\Models\DocumentDossier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DocumentDossierService extends BaseService
{
    public function __construct(DocumentDossierInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getByDossier(int $dossierId): Collection
    {
        return $this->repository->getByDossier($dossierId);
    }

    public function valider(int $id, ?string $commentaire = null): DocumentDossier
    {
        return $this->repository->validerDocument($id, Auth::id(), $commentaire);
    }

    public function tousObligatoiresValides(int $dossierId): bool
    {
        return $this->repository->tousObligatoiresValides($dossierId);
    }
}
