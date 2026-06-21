<?php

namespace App\Repositories;

use App\Interfaces\HistoriqueIntegrationInterface;
use App\Models\HistoriqueIntegration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class HistoriqueIntegrationRepository extends BaseRepository implements HistoriqueIntegrationInterface
{
    protected function model(): string
    {
        return HistoriqueIntegration::class;
    }

    public function enregistrer(
        string $type,
        int $id,
        int $utilisateurId,
        string $action,
        ?array $ancienneValeur,
        ?array $nouvelleValeur,
        ?string $commentaire
    ): HistoriqueIntegration {
        return HistoriqueIntegration::create([
            'historiable_type' => $type,
            'historiable_id'   => $id,
            'utilisateur_id'   => $utilisateurId,
            'action'           => $action,
            'ancienne_valeur'  => $ancienneValeur,
            'nouvelle_valeur'  => $nouvelleValeur,
            'commentaire'      => $commentaire,
        ]);
    }

    public function getHistorique(string $type, int $id): Collection
    {
        return HistoriqueIntegration::where('historiable_type', $type)
            ->where('historiable_id', $id)
            ->with('utilisateur')
            ->latest()
            ->get();
    }

    public function getAll(array $filters = []): Collection
    {
        return HistoriqueIntegration::latest()->get();
    }

    public function create(array $data): Model
    {
        return HistoriqueIntegration::create($data);
    }
}
