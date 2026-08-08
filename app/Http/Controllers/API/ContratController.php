<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Contrat\CreateRequest;
use App\Http\Resources\ContratResource;
use App\Services\ContratService;
use Illuminate\Http\JsonResponse;

class ContratController extends BaseController
{
    public function __construct(ContratService $service)
    {
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

        // La signature du contrat est une étape post-validation (ACTE_GENERE → CONTRAT_SIGNE),
        // pas un effet de bord de la création initiale (souvent en BROUILLON).
        // Endpoint dédié : POST /integration/dossiers/{id}/marquer-contrat-signe

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
