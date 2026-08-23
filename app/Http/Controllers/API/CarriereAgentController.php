<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\CarriereAgentResource;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CarriereAgentController extends BaseController
{
    public function __construct(AgentService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return CarriereAgentResource::class;
    }

    #[OA\Get(
        path: '/api/carriere/agents/{id}',
        operationId: 'syntheseCarriereAgent',
        tags: ['Carrière — Synthèse'],
        summary: 'Situation carrière d\'un agent',
        description: 'Agrège contrat actif, affectation active, nomination active et salaire actuel. Route nouvelle : pas d\'alias /integration (conflit avec la fiche agent).',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Synthèse'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Introuvable'),
        ]
    )]
    public function synthese(int $id): JsonResponse
    {
        return $this->respond(
            $this->service->syntheseCarriere($id),
            'Situation de carrière'
        );
    }
}
