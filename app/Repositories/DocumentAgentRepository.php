<?php

namespace App\Repositories;

use App\Interfaces\DocumentAgentInterface;
use App\Models\DocumentAgent;
use Illuminate\Support\Collection;

class DocumentAgentRepository extends BaseRepository implements DocumentAgentInterface
{
    protected function model(): string
    {
        return DocumentAgent::class;
    }

    public function getByAgent(int $agentId, array $filters = []): Collection
    {
        $filters['agent_id'] = $agentId;

        return DocumentAgent::query()
            ->filter($filters)
            ->with('typeDocument')
            ->orderBy('sous_dossier')
            ->orderByDesc('id')
            ->get();
    }

    public function findForAgent(int $agentId, int $id): DocumentAgent
    {
        return DocumentAgent::query()
            ->where('agent_id', $agentId)
            ->with('typeDocument')
            ->findOrFail($id);
    }
}
