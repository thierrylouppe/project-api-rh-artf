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
        return $this->trouverPourDossierEtType($dossierId, $typeActe) !== null;
    }

    public function trouverPourDossierEtType(int $dossierId, string $typeActe): ?ActeAdministratif
    {
        return ActeAdministratif::where('dossier_integration_id', $dossierId)
            ->where('type_acte', $typeActe)
            ->first();
    }

    public function genererNumero(TypeActeAdministratif $type): string
    {
        $annee   = now()->year;
        $prefixe = $type->prefixeNumero();

        $dernierNumero = ActeAdministratif::where('type_acte', $type->value)
            ->whereYear('created_at', $annee)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('numero');

        $sequence = 1;
        if (is_string($dernierNumero) && preg_match('/(\d+)$/', $dernierNumero, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('ARTF-%s-%d-%s', $prefixe, $annee, str_pad((string) $sequence, 4, '0', STR_PAD_LEFT));
    }
}
