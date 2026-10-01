<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\PalierAncienneteConge\CreateRequest;
use App\Http\Requests\PalierAncienneteConge\UpdateRequest;
use App\Http\Resources\PalierAncienneteCongeResource;
use App\Services\PalierAncienneteCongeService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PalierAncienneteCongeController extends BaseController
{
    public function __construct(PalierAncienneteCongeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return PalierAncienneteCongeResource::class;
    }

    #[OA\Get(path: '/api/conges/paliers-anciennete', operationId: 'listPaliersAnciennete', tags: ['Congés'], summary: 'Paliers d\'ancienneté', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/conges/paliers-anciennete', operationId: 'storePalierAnciennete', tags: ['Congés'], summary: 'Créer un palier', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créé')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Palier d\'ancienneté créé', 201);
    }

    #[OA\Put(path: '/api/conges/paliers-anciennete/{id}', operationId: 'updatePalierAnciennete', tags: ['Congés'], summary: 'Mettre à jour un palier', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mis à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Palier d\'ancienneté mis à jour');
    }

    #[OA\Delete(path: '/api/conges/paliers-anciennete/{id}', operationId: 'destroyPalierAnciennete', tags: ['Congés'], summary: 'Supprimer un palier', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
