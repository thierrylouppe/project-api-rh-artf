<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface ContratInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function getActif(int $agentId): mixed;

    public function resilier(int $id): mixed;
}
