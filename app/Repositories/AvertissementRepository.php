<?php

namespace App\Repositories;

use App\Interfaces\AvertissementInterface;
use App\Models\Avertissement;
use Illuminate\Support\Collection;

class AvertissementRepository extends BaseRepository implements AvertissementInterface
{
    /** @var list<string> */
    private const RELATIONS = ['agent:id,matricule,nom,prenom', 'emetteur:id,name'];

    protected function model(): string
    {
        return Avertissement::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Avertissement::query()->with(self::RELATIONS);

        if (method_exists(Avertissement::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('date')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return Avertissement::query()
            ->where('agent_id', $agentId)
            ->with(self::RELATIONS)
            ->orderByDesc('date')
            ->get();
    }
}
