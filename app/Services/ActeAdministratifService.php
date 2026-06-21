<?php

namespace App\Services;

use App\Enums\TypeActeAdministratif;
use App\Interfaces\ActeAdministratifInterface;
use App\Models\ActeAdministratif;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActeAdministratifService extends BaseService
{
    public function __construct(ActeAdministratifInterface $repository)
    {
        parent::__construct($repository);
    }

    public function generer(int $dossierId, TypeActeAdministratif $typeActe, ?string $contenu = null): ActeAdministratif
    {
        return DB::transaction(function () use ($dossierId, $typeActe, $contenu) {
            $numero = $this->repository->genererNumero($typeActe);

            return $this->repository->create([
                'dossier_integration_id' => $dossierId,
                'type_acte'              => $typeActe->value,
                'numero'                 => $numero,
                'contenu'                => $contenu,
            ]);
        });
    }

    public function signer(int $id): ActeAdministratif
    {
        return $this->repository->signer($id, Auth::id());
    }

    public function getByDossier(int $dossierId): Collection
    {
        return $this->repository->getByDossier($dossierId);
    }
}
