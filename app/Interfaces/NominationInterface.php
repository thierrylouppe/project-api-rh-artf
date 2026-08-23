<?php

namespace App\Interfaces;

use App\Models\Nomination;
use Illuminate\Support\Collection;

interface NominationInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function getActive(int $agentId): ?Nomination;

    public function cloturer(int $id, ?string $dateFin): Nomination;

    public function cloturerActivesPourStructure(string $structurableType, int $structurableId, ?int $saufId = null): void;

    public function cloturerActivePourAgent(int $agentId, ?int $saufId = null): void;
}
