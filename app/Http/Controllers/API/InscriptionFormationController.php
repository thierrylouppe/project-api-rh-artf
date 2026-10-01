<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\InscriptionFormation\CloturerRequest;
use App\Http\Requests\InscriptionFormation\CreateRequest;
use App\Http\Resources\InscriptionFormationResource;
use App\Services\InscriptionFormationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class InscriptionFormationController extends BaseController
{
    public function __construct(InscriptionFormationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return InscriptionFormationResource::class;
    }

    #[OA\Get(path: '/api/formations/inscriptions', operationId: 'listInscriptionsFormation', tags: ['Formations'], summary: 'Liste des inscriptions', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/formations/inscriptions', operationId: 'storeInscriptionFormation', tags: ['Formations'], summary: 'Inscrire un agent', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Inscription créée', 201);
    }

    #[OA\Get(path: '/api/formations/agents/{agent}/inscriptions', operationId: 'inscriptionsFormationParAgent', tags: ['Formations'], summary: 'Inscriptions d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return $this->collectionResponse(
            InscriptionFormationResource::collection($this->service->getByAgent($agent)),
            'Inscriptions récupérées'
        );
    }

    #[OA\Get(path: '/api/formations/inscriptions/{id}', operationId: 'showInscriptionFormation', tags: ['Formations'], summary: 'Détail d\'une inscription', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(path: '/api/formations/inscriptions/{id}/confirmer-presence', operationId: 'confirmerPresenceFormation', tags: ['Formations'], summary: 'Confirmer la présence', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Présente')])]
    public function confirmerPresence(int $id): JsonResponse
    {
        return $this->respond($this->service->confirmerPresence($id), 'Présence confirmée');
    }

    #[OA\Post(path: '/api/formations/inscriptions/{id}/cloturer', operationId: 'cloturerInscriptionFormation', tags: ['Formations'], summary: 'Clôturer une inscription', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Clôturée')])]
    public function cloturer(CloturerRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->cloturer($id, $request->validated()), 'Inscription clôturée');
    }

    #[OA\Post(path: '/api/formations/inscriptions/{id}/annuler', operationId: 'annulerInscriptionFormation', tags: ['Formations'], summary: 'Annuler une inscription', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Annulée')])]
    public function annuler(int $id): JsonResponse
    {
        return $this->respond($this->service->annuler($id), 'Inscription annulée');
    }

    #[OA\Delete(path: '/api/formations/inscriptions/{id}', operationId: 'destroyInscriptionFormation', tags: ['Formations'], summary: 'Supprimer une inscription', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
