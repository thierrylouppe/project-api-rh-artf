<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\StructureSanitaire\CreateRequest;
use App\Http\Requests\StructureSanitaire\UpdateRequest;
use App\Http\Resources\StructureSanitaireResource;
use App\Services\StructureSanitaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StructureSanitaireController extends BaseController
{
    public function __construct(StructureSanitaireService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return StructureSanitaireResource::class;
    }

    #[OA\Get(path: '/api/affaires-sociales/structures-sanitaires', operationId: 'listStructuresSanitaires', tags: ['Affaires sociales'], summary: 'Liste des structures sanitaires agréées', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/affaires-sociales/structures-sanitaires', operationId: 'storeStructureSanitaire', tags: ['Affaires sociales'], summary: 'Créer une structure sanitaire agréée', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Structure sanitaire créée', 201);
    }

    #[OA\Get(path: '/api/affaires-sociales/structures-sanitaires/{id}', operationId: 'showStructureSanitaire', tags: ['Affaires sociales'], summary: 'Détail d\'une structure sanitaire', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Put(path: '/api/affaires-sociales/structures-sanitaires/{id}', operationId: 'updateStructureSanitaire', tags: ['Affaires sociales'], summary: 'Modifier une structure sanitaire', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mise à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Structure sanitaire mise à jour');
    }

    #[OA\Delete(path: '/api/affaires-sociales/structures-sanitaires/{id}', operationId: 'destroyStructureSanitaire', tags: ['Affaires sociales'], summary: 'Supprimer une structure sanitaire', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
