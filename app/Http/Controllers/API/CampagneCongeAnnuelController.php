<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CampagneCongeAnnuel\CreateRequest;
use App\Http\Resources\AgentIdentiteResource;
use App\Http\Resources\CampagneCongeAnnuelResource;
use App\Services\CampagneCongeAnnuelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CampagneCongeAnnuelController extends BaseController
{
    public function __construct(CampagneCongeAnnuelService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return CampagneCongeAnnuelResource::class;
    }

    #[OA\Get(path: '/api/conges-annuels/campagnes', operationId: 'listCampagnesCongeAnnuel', tags: ['Congé annuel'], summary: 'Campagnes de congé annuel', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/conges-annuels/campagnes', operationId: 'storeCampagneCongeAnnuel', tags: ['Congé annuel'], summary: 'Créer une campagne', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Campagne créée', 201);
    }

    #[OA\Get(path: '/api/conges-annuels/campagnes/{id}', operationId: 'showCampagneCongeAnnuel', tags: ['Congé annuel'], summary: 'Détail d\'une campagne', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(path: '/api/conges-annuels/campagnes/{id}/ouvrir', operationId: 'ouvrirCampagneCongeAnnuel', tags: ['Congé annuel'], summary: 'Ouvrir la campagne', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Ouverte')])]
    public function ouvrir(int $id): JsonResponse
    {
        return $this->respond($this->service->ouvrir($id), 'Campagne ouverte');
    }

    #[OA\Post(path: '/api/conges-annuels/campagnes/{id}/cloturer', operationId: 'cloturerCampagneCongeAnnuel', tags: ['Congé annuel'], summary: 'Clôturer la campagne', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Clôturée')])]
    public function cloturer(int $id): JsonResponse
    {
        return $this->respond($this->service->cloturer($id), 'Campagne clôturée');
    }

    #[OA\Get(path: '/api/conges-annuels/campagnes/{id}/sans-proposition', operationId: 'sansPropositionCampagneCongeAnnuel', tags: ['Congé annuel'], summary: 'Agents sans proposition', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function sansProposition(int $id): JsonResponse
    {
        return $this->collectionResponse(
            AgentIdentiteResource::collection($this->service->sansProposition($id)),
            'Agents sans proposition'
        );
    }
}
