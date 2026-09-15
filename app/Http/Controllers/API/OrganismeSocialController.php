<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\OrganismeSocial\CreateRequest;
use App\Http\Requests\OrganismeSocial\UpdateRequest;
use App\Http\Resources\OrganismeSocialListResource;
use App\Http\Resources\OrganismeSocialResource;
use App\Services\OrganismeSocialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OrganismeSocialController extends BaseController
{
    public function __construct(OrganismeSocialService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return OrganismeSocialResource::class;
    }

    protected function listResource(): string
    {
        return OrganismeSocialListResource::class;
    }

    #[OA\Get(path: '/api/affaires-sociales/organismes', operationId: 'listOrganismesSociaux', tags: ['Affaires sociales'], summary: 'Liste des organismes sociaux', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(path: '/api/affaires-sociales/organismes/{id}', operationId: 'showOrganismeSocial', tags: ['Affaires sociales'], summary: 'Détail d\'un organisme', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(path: '/api/affaires-sociales/organismes', operationId: 'storeOrganismeSocial', tags: ['Affaires sociales'], summary: 'Créer un organisme', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créé')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Organisme créé', 201);
    }

    #[OA\Put(path: '/api/affaires-sociales/organismes/{id}', operationId: 'updateOrganismeSocial', tags: ['Affaires sociales'], summary: 'Modifier un organisme', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mis à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Organisme mis à jour');
    }

    #[OA\Delete(path: '/api/affaires-sociales/organismes/{id}', operationId: 'destroyOrganismeSocial', tags: ['Affaires sociales'], summary: 'Supprimer un organisme', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
