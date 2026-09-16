<?php

namespace App\Services;

use App\Models\PaieElement;
use App\Models\PaieElementAffectation;
use App\Models\SalaireAgent;
use App\Models\Sanction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaieLotContexte
{
    /**
     * @param  Collection<int, SalaireAgent>  $salaires
     * @param  Collection<int, Collection<int, PaieElementAffectation>>  $affectations
     * @param  Collection<int, Collection<int, mixed>>  $ayants
     * @param  Collection<int, Collection<int, Sanction>>  $sanctions
     * @param  Collection<int, Collection<int, Sanction>>  $misesAPied
     * @param  Collection<string, PaieElement>  $elements
     */
    public function __construct(
        public readonly Collection $salaires,
        public readonly Collection $affectations,
        public readonly Collection $ayants,
        public readonly Collection $sanctions,
        public readonly Collection $misesAPied,
        public readonly Collection $elements,
        public readonly Carbon $debut,
        public readonly Carbon $fin,
    ) {}

    public function salaire(int $agentId): ?SalaireAgent
    {
        $salaire = $this->salaires->get($agentId);

        return $salaire instanceof SalaireAgent ? $salaire : null;
    }

    /** @return Collection<int, PaieElementAffectation> */
    public function affectationsAgent(int $agentId): Collection
    {
        return collect($this->affectations->get($agentId, collect()));
    }

    public function ayantsAgent(int $agentId): Collection
    {
        return collect($this->ayants->get($agentId, collect()));
    }

    /** @return Collection<int, Sanction> */
    public function sanctionsAgent(int $agentId): Collection
    {
        return collect($this->sanctions->get($agentId, collect()));
    }

    /** @return Collection<int, Sanction> */
    public function misesAPiedAgent(int $agentId): Collection
    {
        return collect($this->misesAPied->get($agentId, collect()));
    }
}
