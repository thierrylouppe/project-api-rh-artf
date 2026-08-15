<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\AgentResource;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/** @property AgentService $service */
class PersonnelController extends BaseController
{
    public function __construct(AgentService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AgentResource::class;
    }

    #[OA\Get(
        path: '/api/personnel/agents',
        operationId: 'listPersonnelAgents',
        tags: ['Personnel'],
        summary: 'Liste des agents déjà intégrés',
        description: 'Agents dont le dossier est INTEGRE, hors stagiaires.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'nom', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'prenom', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'matricule', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'statut', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'actif')),
            new OA\Parameter(name: 'type_integration_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste', content: new OA\JsonContent(ref: '#/components/schemas/AgentListResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function agents(Request $request): JsonResponse
    {
        $items = $this->service->listerIntegres($request->query());

        return $this->collectionResponse(AgentResource::collection($items));
    }

    #[OA\Get(
        path: '/api/personnel/stagiaires',
        operationId: 'listPersonnelStagiaires',
        tags: ['Personnel'],
        summary: 'Liste des stagiaires',
        description: 'Agents au statut stagiaire (convention de stage en cours).',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'nom', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'prenom', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'matricule', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type_integration_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste', content: new OA\JsonContent(ref: '#/components/schemas/AgentListResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function stagiaires(Request $request): JsonResponse
    {
        $items = $this->service->listerStagiaires($request->query());

        return $this->collectionResponse(AgentResource::collection($items));
    }
}
