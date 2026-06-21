<?php

namespace App\Interfaces;

interface PriseDeServiceInterface extends BaseInterface
{
    public function findByAgent(int $agentId): mixed;
}
