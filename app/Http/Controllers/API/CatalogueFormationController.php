<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CatalogueFormation\CreateRequest;
use App\Http\Requests\CatalogueFormation\UpdateRequest;
use App\Http\Resources\CatalogueFormationResource;
use App\Services\CatalogueFormationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CatalogueFormationController extends BaseController
{
    public function __construct(CatalogueFormationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return CatalogueFormationResource::class;
    }

    #[OA\Get(path: '/api/formations/catalogue', operationId: 'listCatalogueFormations', tags: ['Formations'], summary: 'Catalogue des formations', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(path: '/api/formations/catalogue/{id}', operationId: 'showCatalogueFormation', tags: ['Formations'], summary: 'Détail d\'une formation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(path: '/api/formations/catalogue', operationId: 'storeCatalogueFormation', tags: ['Formations'], summary: 'Créer une formation au catalogue', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créé')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Formation créée', 201);
    }

    #[OA\Put(path: '/api/formations/catalogue/{id}', operationId: 'updateCatalogueFormation', tags: ['Formations'], summary: 'Modifier une formation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mis à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Formation mise à jour');
    }

    #[OA\Delete(path: '/api/formations/catalogue/{id}', operationId: 'destroyCatalogueFormation', tags: ['Formations'], summary: 'Supprimer une formation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
