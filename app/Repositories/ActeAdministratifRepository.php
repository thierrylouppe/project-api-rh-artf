<?php

namespace App\Repositories;

use App\Enums\TypeActeAdministratif;
use App\Interfaces\ActeAdministratifInterface;
use App\Models\ActeAdministratif;
use Illuminate\Support\Collection;

class ActeAdministratifRepository extends BaseRepository implements ActeAdministratifInterface
{
    protected function model(): string
    {
        return ActeAdministratif::class;
    }

    public function getByDossier(int $dossierId): Collection
    {
        return ActeAdministratif::where('dossier_integration_id', $dossierId)->get();
    }

    public function signer(int $id, int $signataire): ActeAdministratif
    {
        $acte = $this->findById($id);
        $acte->update([
            'signe'          => true,
            'signe_par'      => $signataire,
            'date_signature' => now(),
        ]);

        return $acte->fresh();
    }

    public function acteExistePourType(int $dossierId, string $typeActe): bool
    {
        return ActeAdministratif::where('dossier_integration_id', $dossierId)
            ->where('type_acte', $typeActe)
            ->exists();
    }

    public function genererNumero(TypeActeAdministratif $type): string
    {
        $annee = now()->year;
        $prefixe = $type->prefixeNumero();

        $dernier = ActeAdministratif::where('type_acte', $type->value)
            ->whereYear('created_at', $annee)
            ->lockForUpdate()
            ->count();

        $sequence = str_pad($dernier + 1, 4, '0', STR_PAD_LEFT);

        return "ARTF-{$prefixe}-{$annee}-{$sequence}";
    }
}
