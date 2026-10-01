<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface AvancementExceptionnelInterface extends BaseInterface
{
    public function parAgent(int $agentId): Collection;
    public function enAttente(): Collection;
}
