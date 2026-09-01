<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\RegleAcquisitionConge\CreateRequest;
use App\Http\Requests\RegleAcquisitionConge\UpdateRequest;
use App\Http\Resources\RegleAcquisitionCongeResource;
use App\Services\RegleAcquisitionCongeService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class RegleAcquisitionCongeController extends BaseController
{
    public function __construct(RegleAcquisitionCongeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return RegleAcquisitionCongeResource::class;
    }

    protected function showRelations(): array
    {
        return ['typeConge'];
    }

    #[OA\Get(path: '/api/conges/regles-acquisition', operationId: 'listReglesAcquisition', tags: ['Congés'], summary: 'Règles d\'acquisition', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/conges/regles-acquisition', operationId: 'storeRegleAcquisition', tags: ['Congés'], summary: 'Créer une règle d\'acquisition', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        $regle = $this->service->create($request->validated());
        $regle->load('typeConge');

        return $this->respond($regle, 'Règle d\'acquisition créée', 201);
    }

    #[OA\Put(path: '/api/conges/regles-acquisition/{id}', operationId: 'updateRegleAcquisition', tags: ['Congés'], summary: 'Mettre à jour une règle', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mise à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        $regle = $this->service->update($id, $request->validated());
        $regle->load('typeConge');

        return $this->respond($regle, 'Règle d\'acquisition mise à jour');
    }
}
