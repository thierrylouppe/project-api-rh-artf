<?php

namespace App\Interfaces;

use App\Models\ValidationWorkflow;
use Illuminate\Support\Collection;

interface ValidationWorkflowInterface extends BaseInterface
{
    public function initialiserCircuit(string $type, int $id): Collection;

    public function getProchainEnAttente(string $type, int $id): ?ValidationWorkflow;

    public function approuver(int $id, int $validateurId, ?string $commentaire): ValidationWorkflow;

    public function rejeter(int $id, int $validateurId, string $commentaire): ValidationWorkflow;

    public function renvoyer(int $id, int $validateurId, string $commentaire): ValidationWorkflow;

    public function circuitTermine(string $type, int $id): bool;

    public function circuitRejete(string $type, int $id): bool;
}
