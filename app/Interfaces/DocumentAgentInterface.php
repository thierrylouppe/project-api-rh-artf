<?php

namespace App\Interfaces;

use App\Models\DocumentAgent;
use Illuminate\Support\Collection;

interface DocumentAgentInterface extends BaseInterface
{
    public function getByAgent(int $agentId, array $filters = []): Collection;

    public function findForAgent(int $agentId, int $id): DocumentAgent;
}
