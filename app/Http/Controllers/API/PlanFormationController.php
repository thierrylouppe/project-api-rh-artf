<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\PlanFormation\CreateRequest;
use App\Http\Requests\PlanFormation\StoreLigneRequest;
use App\Http\Requests\PlanFormation\UpdateRequest;
use App\Http\Resources\PlanFormationLigneResource;
use App\Http\Resources\PlanFormationResource;
use App\Services\PlanFormationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PlanFormationController extends BaseController
{
    public function __construct(PlanFormationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return PlanFormationResource::class;
    }

    #[OA\Get(path: '/api/formations/plans', operationId: 'listPlansFormation', tags: ['Formations'], summary: 'Plans de formation annuels', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/formations/plans', operationId: 'storePlanFormation', tags: ['Formations'], summary: 'Créer un plan annuel (brouillon)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créé')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Plan créé', 201);
    }

    #[OA\Get(path: '/api/formations/plans/{id}', operationId: 'showPlanFormation', tags: ['Formations'], summary: 'Détail d\'un plan', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Put(path: '/api/formations/plans/{id}', operationId: 'updatePlanFormation', tags: ['Formations'], summary: 'Modifier un plan en brouillon', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mis à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Plan mis à jour');
    }

    #[OA\Delete(path: '/api/formations/plans/{id}', operationId: 'destroyPlanFormation', tags: ['Formations'], summary: 'Supprimer un plan en brouillon', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }

    #[OA\Post(path: '/api/formations/plans/{id}/lignes', operationId: 'storeLignePlanFormation', tags: ['Formations'], summary: 'Ajouter une formation au plan', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function storeLigne(StoreLigneRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            new PlanFormationLigneResource($this->service->ajouterLigne($id, $request->validated())),
            'Ligne ajoutée',
            201
        );
    }

    #[OA\Delete(path: '/api/formations/plans/{id}/lignes/{ligneId}', operationId: 'destroyLignePlanFormation', tags: ['Formations'], summary: 'Retirer une formation du plan', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'ligneId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroyLigne(int $id, int $ligneId): JsonResponse
    {
        $this->service->supprimerLigne($id, $ligneId);

        return $this->messageResponse('Ligne supprimée');
    }

    #[OA\Post(path: '/api/formations/plans/{id}/valider', operationId: 'validerPlanFormation', tags: ['Formations'], summary: 'Valider le plan annuel', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Validé')])]
    public function valider(int $id): JsonResponse
    {
        return $this->respond($this->service->valider($id), 'Plan validé');
    }

    #[OA\Post(path: '/api/formations/plans/{id}/executer', operationId: 'executerPlanFormation', tags: ['Formations'], summary: 'Passer le plan en exécution', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Exécuté')])]
    public function executer(int $id): JsonResponse
    {
        return $this->respond($this->service->executer($id), 'Plan en exécution');
    }

    #[OA\Post(path: '/api/formations/plans/{id}/cloturer', operationId: 'cloturerPlanFormation', tags: ['Formations'], summary: 'Clôturer le plan annuel', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Clôturé')])]
    public function cloturer(int $id): JsonResponse
    {
        return $this->respond($this->service->cloturer($id), 'Plan clôturé');
    }
}
