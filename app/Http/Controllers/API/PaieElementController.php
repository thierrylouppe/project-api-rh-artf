<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\PaieElement\CreateRequest;
use App\Http\Requests\PaieElement\UpdateRequest;
use App\Http\Resources\PaieElementResource;
use App\Services\PaieElementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PaieElementController extends BaseController
{
    public function __construct(PaieElementService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return PaieElementResource::class;
    }

    #[OA\Get(
        path: '/api/paie/elements',
        operationId: 'listPaieElements',
        tags: ['Paie'],
        summary: 'Liste des éléments de paie',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'nature', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'actif', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'code', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Liste')]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(
        path: '/api/paie/elements/{id}',
        operationId: 'showPaieElement',
        tags: ['Paie'],
        summary: 'Détail d\'un élément de paie',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Détail')]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(
        path: '/api/paie/elements',
        operationId: 'storePaieElement',
        tags: ['Paie'],
        summary: 'Créer un élément de paie (hors codes CCN)',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Créé')]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Élément de paie créé', 201);
    }

    #[OA\Put(
        path: '/api/paie/elements/{id}',
        operationId: 'updatePaieElement',
        tags: ['Paie'],
        summary: 'Modifier un élément de paie',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Mis à jour')]
    )]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Élément de paie mis à jour');
    }

    #[OA\Delete(
        path: '/api/paie/elements/{id}',
        operationId: 'destroyPaieElement',
        tags: ['Paie'],
        summary: 'Supprimer un élément de paie non système',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Supprimé')]
    )]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
