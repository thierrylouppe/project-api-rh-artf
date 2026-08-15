<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Agent\CreateRequest;
use App\Http\Requests\Agent\ModifierMatriculeRequest;
use App\Http\Requests\Agent\UpdateRequest;
use App\Http\Resources\AgentResource;
use App\Http\Resources\DossierIntegrationResource;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/** @property AgentService $service */
class AgentController extends BaseController
{
    public function __construct(AgentService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AgentResource::class;
    }

    protected function showRelations(): array
    {
        return ['grade', 'categorie', 'echelon', 'fonction', 'typeIntegration', 'affectationActive', 'nominationActive', 'contratActif'];
    }

    #[OA\Get(
        path: '/api/integration/agents',
        operationId: 'listAgents',
        tags: ['Intégration — Agents'],
        summary: 'Liste des agents',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste', content: new OA\JsonContent(ref: '#/components/schemas/AgentListResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(
        path: '/api/integration/agents/{id}',
        operationId: 'showAgent',
        tags: ['Intégration — Agents'],
        summary: 'Détail d\'un agent',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail', content: new OA\JsonContent(ref: '#/components/schemas/AgentResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404')),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(
        path: '/api/integration/agents',
        operationId: 'storeAgent',
        tags: ['Intégration — Agents'],
        summary: 'Créer un agent (+ dossier d\'intégration auto)',
        description: 'Crée la fiche agent et initialise automatiquement un dossier d\'intégration.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AgentRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Créé', content: new OA\JsonContent(ref: '#/components/schemas/AgentCreateResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        $result = $this->service->creerAvecDossier($request->validated());

        return response()->json([
            'data' => [
                'agent'   => new AgentResource($result['agent']),
                'dossier' => new DossierIntegrationResource($result['dossier']),
            ],
            'message' => 'Fiche agent créée — dossier d\'intégration initialisé automatiquement (réf. ' . $result['dossier']->reference . ')',
        ], 201);
    }

    #[OA\Put(
        path: '/api/integration/agents/{id}',
        operationId: 'updateAgent',
        tags: ['Intégration — Agents'],
        summary: 'Mettre à jour un agent',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/AgentRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mis à jour', content: new OA\JsonContent(ref: '#/components/schemas/AgentResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Agent mis à jour avec succès');
    }

    #[OA\Patch(
        path: '/api/integration/agents/{id}/matricule',
        operationId: 'modifierMatriculeAgent',
        tags: ['Intégration — Agents'],
        summary: 'Modifier le matricule d\'un agent',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AssignerMatriculeRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/AgentResponse')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function modifierMatricule(ModifierMatriculeRequest $request, int $id): JsonResponse
    {
        $agent = $this->service->modifierMatricule($id, $request->validated('matricule'));

        return $this->respond($agent, "Matricule modifié : {$agent->matricule}");
    }
}
