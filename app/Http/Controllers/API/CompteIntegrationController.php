<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CompteIntegration\ProvisionnerRequest;
use App\Http\Resources\CompteIntegrationResource;
use App\Services\AgentService;
use App\Services\CompteIntegrationService;
use App\Services\DossierIntegrationService;
use Illuminate\Http\JsonResponse;

class CompteIntegrationController extends BaseController
{
    public function __construct(
        CompteIntegrationService $service,
        private readonly AgentService $agentService,
        private readonly DossierIntegrationService $dossierService,
    ) {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return CompteIntegrationResource::class;
    }

    public function provisionner(ProvisionnerRequest $request): JsonResponse
    {
        $agent  = $this->agentService->findById($request->input('agent_id'));
        $compte = $this->service->provisionner($agent);

        if ($request->filled('dossier_integration_id')) {
            $this->dossierService->marquerCompteCree($request->input('dossier_integration_id'));
        }

        return $this->respond($compte, "Compte créé — login : {$compte->login}, email : {$compte->email_professionnel}", 201);
    }

    public function byAgent(int $agentId): JsonResponse
    {
        $compte = $this->service->findByAgent($agentId);

        if (! $compte) {
            return response()->json(['message' => 'Aucun compte trouvé pour cet agent'], 404);
        }

        return $this->respond($compte);
    }
}
