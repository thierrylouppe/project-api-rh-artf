<?php

namespace App\Interfaces;

use App\Models\Nomination;
use Illuminate\Support\Collection;

interface NominationInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function getActive(int $agentId): ?Nomination;

    public function cloturerNominationsActives(string $structurableType, int $structurableId): void;
}
