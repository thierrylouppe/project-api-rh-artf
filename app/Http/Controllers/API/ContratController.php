<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Contrat\CreateRequest;
use App\Http\Resources\ContratResource;
use App\Services\ContratService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

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

    #[OA\Get(
        path: '/api/integration/contrats',
        operationId: 'listContrats',
        tags: ['Intégration — Contrats'],
        summary: 'Liste des contrats',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste'),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(
        path: '/api/integration/contrats/{id}',
        operationId: 'showContrat',
        tags: ['Intégration — Contrats'],
        summary: 'Détail d\'un contrat',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail', content: new OA\JsonContent(ref: '#/components/schemas/ContratResponse')),
            new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404')),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(
        path: '/api/integration/contrats',
        operationId: 'storeContrat',
        tags: ['Intégration — Contrats'],
        summary: 'Créer un contrat',
        description: 'La signature (transition dossier) se fait via `POST /api/integration/dossiers/{id}/marquer-contrat-signe`.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ContratRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Créé', content: new OA\JsonContent(ref: '#/components/schemas/ContratResponse')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        $contrat = $this->service->create($request->validated());

        // La signature du contrat est une étape post-validation (ACTE_GENERE → CONTRAT_SIGNE),
        // pas un effet de bord de la création initiale (souvent en BROUILLON).
        // Endpoint dédié : POST /integration/dossiers/{id}/marquer-contrat-signe

        return $this->respond($contrat, 'Contrat créé avec succès', 201);
    }

    #[OA\Get(
        path: '/api/integration/agents/{agent}/contrats',
        operationId: 'listContratsByAgent',
        tags: ['Intégration — Contrats'],
        summary: 'Contrats d\'un agent',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste'),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function byAgent(int $agentId): JsonResponse
    {
        $contrats = $this->service->getByAgent($agentId);

        return response()->json(['data' => ContratResource::collection($contrats)]);
    }

    #[OA\Post(
        path: '/api/integration/contrats/{contrat}/resilier',
        operationId: 'resilierContrat',
        tags: ['Intégration — Contrats'],
        summary: 'Résilier un contrat',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'contrat', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Résilié', content: new OA\JsonContent(ref: '#/components/schemas/ContratResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function resilier(int $id): JsonResponse
    {
        return $this->respond($this->service->resilier($id), 'Contrat résilié');
    }
}
