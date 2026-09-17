<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\VisiteMedicale\CreateRequest;
use App\Http\Requests\VisiteMedicale\UpdateRequest;
use App\Http\Resources\VisiteMedicaleResource;
use App\Services\VisiteMedicaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/** @property VisiteMedicaleService $service */
class VisiteMedicaleController extends BaseController
{
    public function __construct(VisiteMedicaleService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return VisiteMedicaleResource::class;
    }

    protected function showRelations(): array
    {
        return [
            'agent:id,matricule,nom,prenom,statut',
            'structure:id,nom,type',
        ];
    }

    #[OA\Get(path: '/api/affaires-sociales/visites-medicales', operationId: 'listVisitesMedicales', tags: ['Affaires sociales'], summary: 'Liste des visites médicales', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/affaires-sociales/visites-medicales', operationId: 'storeVisiteMedicale', tags: ['Affaires sociales'], summary: 'Enregistrer une visite médicale', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Visite médicale enregistrée', 201);
    }

    #[OA\Get(path: '/api/affaires-sociales/alertes/visites-annuelles-manquantes', operationId: 'alertesVisitesAnnuelles', tags: ['Affaires sociales'], summary: 'Agents sans visite annuelle (alerte CCN art. 122)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function alertesAnnuelles(Request $request): JsonResponse
    {
        $annee = $request->query('annee') !== null ? (int) $request->query('annee') : null;

        return $this->successResponse(
            $this->service->alertesAnnuellesManquantes($annee),
            'Alertes visites annuelles'
        );
    }

    #[OA\Get(path: '/api/affaires-sociales/agents/{agent}/visites-medicales', operationId: 'visitesParAgent', tags: ['Affaires sociales'], summary: 'Visites médicales d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return $this->collectionResponse(
            VisiteMedicaleResource::collection($this->service->getByAgent($agent)),
            'Visites médicales récupérées'
        );
    }

    #[OA\Get(path: '/api/affaires-sociales/visites-medicales/{id}', operationId: 'showVisiteMedicale', tags: ['Affaires sociales'], summary: 'Détail d\'une visite médicale', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Put(path: '/api/affaires-sociales/visites-medicales/{id}', operationId: 'updateVisiteMedicale', tags: ['Affaires sociales'], summary: 'Modifier une visite médicale', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mise à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Visite médicale mise à jour');
    }

    #[OA\Delete(path: '/api/affaires-sociales/visites-medicales/{id}', operationId: 'destroyVisiteMedicale', tags: ['Affaires sociales'], summary: 'Supprimer une visite médicale', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
