<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TypeSanction\CreateRequest;
use App\Http\Requests\TypeSanction\UpdateRequest;
use App\Http\Resources\TypeSanctionListResource;
use App\Http\Resources\TypeSanctionResource;
use App\Services\TypeSanctionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TypeSanctionController extends BaseController
{
    public function __construct(TypeSanctionService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return TypeSanctionResource::class;
    }

    protected function listResource(): string
    {
        return TypeSanctionListResource::class;
    }

    #[OA\Get(path: '/api/discipline/types-sanctions', operationId: 'listTypesSanctions', tags: ['Discipline'], summary: 'Liste des types de sanctions', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(path: '/api/discipline/types-sanctions/{id}', operationId: 'showTypeSanction', tags: ['Discipline'], summary: 'Détail d\'un type de sanction', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(path: '/api/discipline/types-sanctions', operationId: 'storeTypeSanction', tags: ['Discipline'], summary: 'Créer un type de sanction', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créé')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Type de sanction créé', 201);
    }

    #[OA\Put(path: '/api/discipline/types-sanctions/{id}', operationId: 'updateTypeSanction', tags: ['Discipline'], summary: 'Modifier un type de sanction', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mis à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Type de sanction mis à jour');
    }

    #[OA\Delete(path: '/api/discipline/types-sanctions/{id}', operationId: 'destroyTypeSanction', tags: ['Discipline'], summary: 'Supprimer un type de sanction', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
