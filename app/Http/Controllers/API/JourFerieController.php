<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\JourFerie\CreateRequest;
use App\Http\Requests\JourFerie\UpdateRequest;
use App\Http\Resources\JourFerieResource;
use App\Services\JourFerieService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class JourFerieController extends BaseController
{
    public function __construct(JourFerieService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return JourFerieResource::class;
    }

    #[OA\Get(path: '/api/conges/jours-feries', operationId: 'listJoursFeries', tags: ['Congés'], summary: 'Liste des jours fériés', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/conges/jours-feries', operationId: 'storeJourFerie', tags: ['Congés'], summary: 'Créer un jour férié', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créé')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Jour férié créé', 201);
    }

    #[OA\Put(path: '/api/conges/jours-feries/{id}', operationId: 'updateJourFerie', tags: ['Congés'], summary: 'Mettre à jour un jour férié', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mis à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Jour férié mis à jour');
    }
}
