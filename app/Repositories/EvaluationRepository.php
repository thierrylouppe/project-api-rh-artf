<?php

namespace App\Repositories;

use App\Enums\StatutEvaluation;
use App\Interfaces\EvaluationInterface;
use App\Models\Evaluation;
use Illuminate\Support\Collection;

class EvaluationRepository extends BaseRepository implements EvaluationInterface
{
    protected function model(): string
    {
        return Evaluation::class;
    }

    public function getAll(array $filters = []): Collection
    {
        return Evaluation::query()
            ->with(['agent', 'superieur', 'session', 'affectationNotation.structure'])
            ->filter($filters)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getBySession(int $sessionId): Collection
    {
        return Evaluation::query()
            ->with(['agent', 'superieur', 'affectationNotation.structure'])
            ->where('session_id', $sessionId)
            ->orderBy('agent_id')
            ->get();
    }

    public function getTableau(int $sessionId): Collection
    {
        return Evaluation::query()
            ->with(['agent', 'superieur', 'session', 'affectationNotation.structure'])
            ->where('session_id', $sessionId)
            ->where('statut', StatutEvaluation::FINALISEE)
            ->where('inscrit_tableau', true)
            ->orderBy('agent_id')
            ->get();
    }

    public function getBySuperieur(int $superieurId): Collection
    {
        return Evaluation::query()
            ->with(['agent', 'session', 'affectationNotation.structure'])
            ->where('superieur_id', $superieurId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return Evaluation::query()
            ->with(['superieur', 'session', 'affectationNotation.structure'])
            ->where('agent_id', $agentId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function trouverParAgentSession(int $agentId, int $sessionId): ?Evaluation
    {
        return Evaluation::query()
            ->where('agent_id', $agentId)
            ->where('session_id', $sessionId)
            ->with(['notes.question'])
            ->first();
    }

    public function dateDerniereEvaluationFinalisee(int $agentId): ?\Illuminate\Support\Carbon
    {
        $fiche = Evaluation::query()
            ->where('agent_id', $agentId)
            ->where('statut', 'finalisee')
            ->orderByDesc('date_validation_rh')
            ->first();

        return $fiche?->date_validation_rh;
    }
}
