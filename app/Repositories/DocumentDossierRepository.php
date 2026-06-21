<?php

namespace App\Repositories;

use App\Interfaces\DocumentDossierInterface;
use App\Models\DocumentDossier;
use Illuminate\Support\Collection;

class DocumentDossierRepository extends BaseRepository implements DocumentDossierInterface
{
    protected function model(): string
    {
        return DocumentDossier::class;
    }

    public function getByDossier(int $dossierId): Collection
    {
        return DocumentDossier::where('dossier_integration_id', $dossierId)->get();
    }

    public function validerDocument(int $id, int $validateurId, ?string $commentaire): DocumentDossier
    {
        $doc = $this->findById($id);
        $doc->update([
            'est_valide'      => true,
            'valide_par'      => $validateurId,
            'date_validation' => now(),
            'commentaire'     => $commentaire,
        ]);

        return $doc->fresh();
    }

    public function tousObligatoiresValides(int $dossierId): bool
    {
        return ! DocumentDossier::where('dossier_integration_id', $dossierId)
            ->where('est_obligatoire', true)
            ->where('est_valide', false)
            ->exists();
    }
}
