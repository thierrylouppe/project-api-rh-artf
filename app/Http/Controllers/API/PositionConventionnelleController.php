<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\PositionConventionnelle\CloturerRequest;
use App\Http\Requests\PositionConventionnelle\RenouvelerRequest;
use App\Http\Requests\PositionConventionnelle\StoreRequest;
use App\Http\Requests\PositionConventionnelle\TraiterRequest;
use App\Http\Resources\PositionConventionnelleResource;
use App\Services\PositionConventionnelleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PositionConventionnelleController extends BaseController
{
    public function __construct(private readonly PositionConventionnelleService $positionService)
    {
        parent::__construct($positionService);
    }

    protected function resource(): string
    {
        return PositionConventionnelleResource::class;
    }

    #[OA\Get(
        path: '/api/carriere/positions',
        operationId: 'listPositionsConventionnelles',
        tags: ['Carrière — Positions'],
        summary: 'Liste des positions conventionnelles (art. 76–80)',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste'),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        return $this->collectionResponse(
            PositionConventionnelleResource::collection($this->positionService->getAll($request->query()))
        );
    }

    #[OA\Get(
        path: '/api/carriere/positions/{id}',
        operationId: 'showPositionConventionnelle',
        tags: ['Carrière — Positions'],
        summary: 'Détail d\'une position conventionnelle',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail'),
            new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404')),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $id = (int) collect($request->route()->parameters())->first();

        return $this->respond($this->positionService->detail($id));
    }

    #[OA\Get(
        path: '/api/carriere/agents/{id}/positions',
        operationId: 'listPositionsConventionnellesParAgent',
        tags: ['Carrière — Positions'],
        summary: 'Historique des positions d\'un agent',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Liste')]
    )]
    public function parAgent(int $id): JsonResponse
    {
        return $this->collectionResponse(
            PositionConventionnelleResource::collection($this->positionService->parAgent($id))
        );
    }

    #[OA\Post(
        path: '/api/carriere/positions',
        operationId: 'storePositionConventionnelle',
        tags: ['Carrière — Positions'],
        summary: 'Soumettre une position (détachement, disponibilité, exceptionnelle, drapeau)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PositionConventionnelleRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Soumise'),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function store(StoreRequest $request): JsonResponse
    {
        $position = $this->positionService->soumettre($request->validated(), $request->user());

        return $this->respond($position, 'Demande de position soumise.', 201);
    }

    #[OA\Post(
        path: '/api/carriere/positions/{id}/approuver',
        operationId: 'approuverPositionConventionnelle',
        tags: ['Carrière — Positions'],
        summary: 'Approuver (DG) — active la position et applique les effets CCN',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Approuvée')]
    )]
    public function approuver(TraiterRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->positionService->approuver($id, $request->user(), $request->validated('commentaire')),
            'Position approuvée.'
        );
    }

    #[OA\Post(
        path: '/api/carriere/positions/{id}/rejeter',
        operationId: 'rejeterPositionConventionnelle',
        tags: ['Carrière — Positions'],
        summary: 'Rejeter (DG)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Rejetée')]
    )]
    public function rejeter(TraiterRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->positionService->rejeter($id, $request->user(), $request->validated('commentaire')),
            'Position rejetée.'
        );
    }

    #[OA\Post(
        path: '/api/carriere/positions/{id}/cloturer',
        operationId: 'cloturerPositionConventionnelle',
        tags: ['Carrière — Positions'],
        summary: 'Clôturer — réintègre l\'agent (préavis 3 mois détachement/dispo)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Clôturée')]
    )]
    public function cloturer(CloturerRequest $request, int $id): JsonResponse
    {
        $position = $this->positionService->cloturer($id, $request->validated());
        $payload  = new PositionConventionnelleResource($position);
        $meta     = $position->reintegration ?? null;

        if ($meta === null) {
            return $this->successResponse($payload, 'Position clôturée. Agent réintégré.');
        }

        return response()->json([
            'success' => true,
            'data'    => $payload,
            'message' => 'Position clôturée. Agent réintégré.',
            'meta'    => ['reintegration' => $meta],
        ]);
    }

    #[OA\Post(
        path: '/api/carriere/positions/{id}/renouveler',
        operationId: 'renouvelerPositionConventionnelle',
        tags: ['Carrière — Positions'],
        summary: 'Renouveler (DG) — disponibilité max 2 fois',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Renouvelée')]
    )]
    public function renouveler(RenouvelerRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->positionService->renouveler($id, $request->validated(), $request->user()),
            'Position renouvelée.'
        );
    }
}
