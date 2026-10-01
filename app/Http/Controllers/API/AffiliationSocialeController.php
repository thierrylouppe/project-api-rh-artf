<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\AffiliationSociale\CreateRequest;
use App\Http\Requests\AffiliationSociale\UpdateRequest;
use App\Http\Resources\AffiliationSocialeResource;
use App\Services\AffiliationSocialeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AffiliationSocialeController extends BaseController
{
    public function __construct(AffiliationSocialeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AffiliationSocialeResource::class;
    }

    #[OA\Get(path: '/api/affaires-sociales/affiliations', operationId: 'listAffiliationsSociales', tags: ['Affaires sociales'], summary: 'Liste des affiliations', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/affaires-sociales/affiliations', operationId: 'storeAffiliationSociale', tags: ['Affaires sociales'], summary: 'Créer une affiliation', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Affiliation créée', 201);
    }

    #[OA\Get(path: '/api/affaires-sociales/alertes/sans-affiliation-cnss', operationId: 'alertesSansAffiliationCnss', tags: ['Affaires sociales'], summary: 'Agents sans affiliation CNSS active', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function sansAffiliationCnss(): JsonResponse
    {
        $agents = $this->service->agentsSansAffiliationCnss()->map(fn ($agent) => [
            'id' => $agent->id,
            'matricule' => $agent->matricule,
            'nom' => $agent->nom,
            'prenom' => $agent->prenom,
            'nom_complet' => $agent->nom_complet,
            'numero_cnss' => $agent->numero_cnss,
            'statut' => $agent->statut,
        ]);

        return $this->collectionResponse($agents, 'Agents sans affiliation CNSS');
    }

    #[OA\Get(path: '/api/affaires-sociales/agents/{agent}/affiliations', operationId: 'affiliationsParAgent', tags: ['Affaires sociales'], summary: 'Affiliations d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return $this->collectionResponse(
            AffiliationSocialeResource::collection($this->service->getByAgent($agent)),
            'Affiliations récupérées'
        );
    }

    #[OA\Get(path: '/api/affaires-sociales/affiliations/{id}', operationId: 'showAffiliationSociale', tags: ['Affaires sociales'], summary: 'Détail d\'une affiliation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Put(path: '/api/affaires-sociales/affiliations/{id}', operationId: 'updateAffiliationSociale', tags: ['Affaires sociales'], summary: 'Modifier une affiliation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mise à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Affiliation mise à jour');
    }

    #[OA\Delete(path: '/api/affaires-sociales/affiliations/{id}', operationId: 'destroyAffiliationSociale', tags: ['Affaires sociales'], summary: 'Supprimer une affiliation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
