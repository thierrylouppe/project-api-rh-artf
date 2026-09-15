<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Avertissement\CreateRequest;
use App\Http\Requests\Avertissement\UpdateRequest;
use App\Http\Resources\AvertissementResource;
use App\Services\AvertissementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AvertissementController extends BaseController
{
    public function __construct(AvertissementService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AvertissementResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent:id,matricule,nom,prenom', 'emetteur:id,name'];
    }

    #[OA\Get(path: '/api/discipline/avertissements', operationId: 'listAvertissements', tags: ['Discipline'], summary: 'Liste des avertissements', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/discipline/avertissements', operationId: 'storeAvertissement', tags: ['Discipline'], summary: 'Émettre un avertissement', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créé')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Avertissement émis', 201);
    }

    #[OA\Get(path: '/api/discipline/moi/avertissements', operationId: 'mesAvertissements', tags: ['Discipline'], summary: 'Avertissements de l\'agent connecté', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function mesAvertissements(): JsonResponse
    {
        return $this->collectionResponse(
            AvertissementResource::collection($this->service->mesAvertissements()),
            'Avertissements récupérés'
        );
    }

    #[OA\Get(path: '/api/discipline/moi/avertissements/{id}', operationId: 'monAvertissement', tags: ['Discipline'], summary: 'Détail d\'un avertissement de l\'agent connecté', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function monAvertissement(int $id): JsonResponse
    {
        return $this->respond($this->service->monAvertissement($id), 'Avertissement récupéré');
    }

    #[OA\Get(path: '/api/discipline/agents/{agent}/avertissements', operationId: 'avertissementsParAgent', tags: ['Discipline'], summary: 'Avertissements d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return response()->json([
            'data' => AvertissementResource::collection($this->service->getByAgent($agent)),
            'message' => 'Avertissements récupérés',
        ]);
    }

    #[OA\Get(path: '/api/discipline/avertissements/{id}', operationId: 'showAvertissement', tags: ['Discipline'], summary: 'Détail d\'un avertissement', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Put(path: '/api/discipline/avertissements/{id}', operationId: 'updateAvertissement', tags: ['Discipline'], summary: 'Modifier un avertissement', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mis à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Avertissement mis à jour');
    }

    #[OA\Delete(path: '/api/discipline/avertissements/{id}', operationId: 'destroyAvertissement', tags: ['Discipline'], summary: 'Supprimer un avertissement', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
