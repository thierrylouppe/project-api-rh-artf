<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\PaieAffectation\CreateRequest;
use App\Http\Requests\PaieAffectation\UpdateRequest;
use App\Http\Resources\PaieAffectationResource;
use App\Services\PaieAffectationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PaieAffectationController extends BaseController
{
    public function __construct(PaieAffectationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return PaieAffectationResource::class;
    }

    protected function showRelations(): array
    {
        return [
            'agent:id,matricule,nom,prenom,statut,fonction_id',
            'agent.fonction:id,nom,sigle',
            'element',
        ];
    }

    #[OA\Get(
        path: '/api/paie/affectations',
        operationId: 'listPaieAffectations',
        tags: ['Paie'],
        summary: 'Liste des affectations d\'éléments de paie',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'agent_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'element_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'actives', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Liste')]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(
        path: '/api/paie/affectations',
        operationId: 'storePaieAffectation',
        tags: ['Paie'],
        summary: 'Affecter un élément de paie à un agent',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Créée')]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Affectation créée', 201);
    }

    #[OA\Get(
        path: '/api/paie/agents/{agent}/affectations',
        operationId: 'paieAffectationsParAgent',
        tags: ['Paie'],
        summary: 'Affectations de paie d\'un agent',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Liste')]
    )]
    public function byAgent(int $agent): JsonResponse
    {
        return $this->collectionResponse(
            PaieAffectationResource::collection($this->service->getByAgent($agent))
        );
    }

    #[OA\Get(
        path: '/api/paie/affectations/{id}',
        operationId: 'showPaieAffectation',
        tags: ['Paie'],
        summary: 'Détail d\'une affectation de paie',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Détail')]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Put(
        path: '/api/paie/affectations/{id}',
        operationId: 'updatePaieAffectation',
        tags: ['Paie'],
        summary: 'Modifier une affectation de paie',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Mise à jour')]
    )]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Affectation mise à jour');
    }

    #[OA\Delete(
        path: '/api/paie/affectations/{id}',
        operationId: 'destroyPaieAffectation',
        tags: ['Paie'],
        summary: 'Supprimer une affectation de paie',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Supprimé')]
    )]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
