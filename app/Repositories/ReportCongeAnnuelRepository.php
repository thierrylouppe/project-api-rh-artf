<?php

namespace App\Repositories;

use App\Enums\StatutReportCongeAnnuel;
use App\Interfaces\ReportCongeAnnuelInterface;
use App\Models\ReportCongeAnnuel;

class ReportCongeAnnuelRepository extends BaseRepository implements ReportCongeAnnuelInterface
{
    protected function model(): string
    {
        return ReportCongeAnnuel::class;
    }

    public function getAll(array $filters = []): \Illuminate\Support\Collection
    {
        $query = ReportCongeAnnuel::query()->with('agent');

        if (method_exists(ReportCongeAnnuel::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('id')->get();
    }

    public function findActif(int $agentId, int $anneeSource): ?ReportCongeAnnuel
    {
        return ReportCongeAnnuel::query()
            ->where('agent_id', $agentId)
            ->where('annee_source', $anneeSource)
            ->whereIn('statut', [
                StatutReportCongeAnnuel::PROPOSE->value,
                StatutReportCongeAnnuel::ACCORDE->value,
            ])
            ->first();
    }
}
