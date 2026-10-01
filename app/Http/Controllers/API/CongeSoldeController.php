<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\CongeSoldeResource;
use App\Services\CongeSoldeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CongeSoldeController extends BaseController
{
    public function __construct(CongeSoldeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return CongeSoldeResource::class;
    }

    protected function showRelations(): array
    {
        return ['typeConge', 'agent'];
    }

    #[OA\Get(path: '/api/conges/soldes', operationId: 'listSoldesConges', tags: ['Congés'], summary: 'Soldes de congés', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(path: '/api/conges/agents/{agent}/soldes', operationId: 'soldesParAgent', tags: ['Congés'], summary: 'Soldes d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Soldes')])]
    public function byAgent(Request $request, int $agent): JsonResponse
    {
        $annee = $request->query('annee') ? (int) $request->query('annee') : null;
        $items = $this->service->getByAgent($agent, $annee);
        $items->load('typeConge');

        return response()->json([
            'data'    => CongeSoldeResource::collection($items),
            'message' => 'Soldes récupérés',
        ]);
    }
}
