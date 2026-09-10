<?php

namespace App\Interfaces;

use App\Models\BonificationStage;
use Illuminate\Support\Collection;

interface BonificationStageInterface extends BaseInterface
{
    public function parAgent(int $agentId): Collection;
    public function enAttente(): Collection;
}
