<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Contrat\CreateRequest;
use App\Http\Resources\ContratResource;
use App\Services\ContratService;
use App\Services\DossierIntegrationService;
use Illuminate\Http\JsonResponse;

class ContratController extends BaseController
{
    public function __construct(
        ContratService $service,
        private readonly DossierIntegrationService $dossierService,
    ) {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ContratResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'typeContrat', 'fonction'];
    }

    public function store(CreateRequest $request): JsonResponse
    {
        $contrat = $this->service->create($request->validated());

        if ($request->filled('dossier_integration_id')) {
            $this->dossierService->marquerContratSigne($request->input('dossier_integration_id'));
        }

        return $this->respond($contrat, 'Contrat créé avec succès', 201);
    }

    public function byAgent(int $agentId): JsonResponse
    {
        $contrats = $this->service->getByAgent($agentId);

        return response()->json(['data' => ContratResource::collection($contrats)]);
    }

    public function resilier(int $id): JsonResponse
    {
        return $this->respond($this->service->resilier($id), 'Contrat résilié');
    }
}
