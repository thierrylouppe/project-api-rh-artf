<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CircuitValidation\AjouterNiveauRequest;
use App\Http\Requests\CircuitValidation\RemplacerCircuitRequest;
use App\Http\Resources\CircuitValidationResource;
use App\Services\CircuitValidationService;
use Illuminate\Http\JsonResponse;

/** @property CircuitValidationService $service */
class CircuitValidationController extends BaseController
{
    public function __construct(CircuitValidationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return CircuitValidationResource::class;
    }

    /**
     * Liste les niveaux du circuit pour un type d'intégration.
     * GET /types-integrations/{type}/circuit
     */
    public function lister(int $typeIntegrationId): JsonResponse
    {
        $steps = $this->service->getPourType($typeIntegrationId);

        return response()->json([
            'data' => CircuitValidationResource::collection($steps),
        ]);
    }

    /**
     * Remplace intégralement le circuit (pour réordonner ou modifier les niveaux).
     * PUT /types-integrations/{type}/circuit
     *
     * Body : { "niveaux": ["chef_bureau", "directeur", "drh"] }
     */
    public function remplacer(RemplacerCircuitRequest $request, int $typeIntegrationId): JsonResponse
    {
        $steps = $this->service->remplacerCircuit(
            $typeIntegrationId,
            $request->validated('niveaux')
        );

        return response()->json([
            'data'    => CircuitValidationResource::collection($steps),
            'message' => 'Circuit mis à jour avec succès.',
        ]);
    }

    /**
     * Ajoute un niveau au circuit existant.
     * POST /types-integrations/{type}/circuit
     *
     * Body : { "niveau": "chef_service", "ordre": 2 }
     */
    public function store(AjouterNiveauRequest $request, int $typeIntegrationId): JsonResponse
    {
        $step = $this->service->ajouterNiveau(
            $typeIntegrationId,
            $request->validated('niveau'),
            $request->validated('ordre')
        );

        return response()->json([
            'data'    => new CircuitValidationResource($step),
            'message' => 'Niveau ajouté au circuit.',
        ], 201);
    }

    /**
     * Supprime un niveau du circuit.
     * DELETE /types-integrations/{type}/circuit/{circuitStep}
     */
    public function retirerNiveau(int $typeIntegrationId, int $circuitStepId): JsonResponse
    {
        $this->service->supprimerNiveau($circuitStepId);

        return response()->json(['message' => 'Niveau retiré du circuit.']);
    }
}
