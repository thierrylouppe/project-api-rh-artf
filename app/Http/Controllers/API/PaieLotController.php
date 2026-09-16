<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\PaieLot\CreateRequest;
use App\Http\Resources\PaieLotLigneResource;
use App\Http\Resources\PaieLotResource;
use App\Services\PaieBulletinService;
use App\Services\PaieLotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/** @property PaieLotService $service */
class PaieLotController extends BaseController
{
    public function __construct(
        PaieLotService $service,
        private readonly PaieBulletinService $bulletinService,
    ) {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return PaieLotResource::class;
    }

    #[OA\Get(
        path: '/api/paie/lots',
        operationId: 'listPaieLots',
        tags: ['Paie'],
        summary: 'Liste des lots de paie',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'annee', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'mois', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'statut', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Liste')]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(
        path: '/api/paie/lots',
        operationId: 'storePaieLot',
        tags: ['Paie'],
        summary: 'Créer un lot de paie brouillon',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Créé')]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Lot de paie créé', 201);
    }

    #[OA\Get(
        path: '/api/paie/lots/{id}',
        operationId: 'showPaieLot',
        tags: ['Paie'],
        summary: 'Détail d\'un lot de paie',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Détail')]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Delete(
        path: '/api/paie/lots/{id}',
        operationId: 'destroyPaieLot',
        tags: ['Paie'],
        summary: 'Supprimer un lot non validé',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Supprimé')]
    )]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }

    #[OA\Post(
        path: '/api/paie/lots/{id}/generer',
        operationId: 'genererPaieLot',
        tags: ['Paie'],
        summary: 'Générer ou recalculer les lignes du lot',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Généré')]
    )]
    public function generer(int $id): JsonResponse
    {
        $lot = $this->service->generer($id);
        $message = $lot->getAttribute('_recalcul') ? 'Lot recalculé' : 'Lot généré';

        return $this->respond($lot, $message);
    }

    #[OA\Post(
        path: '/api/paie/lots/{id}/controler',
        operationId: 'controlerPaieLot',
        tags: ['Paie'],
        summary: 'Contrôler un lot généré',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Contrôlé')]
    )]
    public function controler(int $id): JsonResponse
    {
        return $this->respond($this->service->controler($id), 'Lot contrôlé');
    }

    #[OA\Post(
        path: '/api/paie/lots/{id}/valider',
        operationId: 'validerPaieLot',
        tags: ['Paie'],
        summary: 'Valider un lot contrôlé',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Validé')]
    )]
    public function valider(int $id): JsonResponse
    {
        return $this->respond($this->service->valider($id), 'Lot validé');
    }

    #[OA\Post(
        path: '/api/paie/lots/{id}/cloturer',
        operationId: 'cloturerPaieLot',
        tags: ['Paie'],
        summary: 'Clôturer un lot validé',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Clôturé')]
    )]
    public function cloturer(int $id): JsonResponse
    {
        return $this->respond($this->service->cloturer($id), 'Lot clôturé');
    }

    #[OA\Get(
        path: '/api/paie/lots/{id}/lignes',
        operationId: 'listPaieLotLignes',
        tags: ['Paie'],
        summary: 'Lignes d\'un lot de paie',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Liste')]
    )]
    public function lignes(int $id): JsonResponse
    {
        return $this->collectionResponse(
            PaieLotLigneResource::collection($this->service->getLignes($id))
        );
    }

    #[OA\Get(
        path: '/api/paie/lots/{id}/lignes/{ligneId}',
        operationId: 'showPaieLotLigne',
        tags: ['Paie'],
        summary: 'Détail d\'une ligne de lot',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ligneId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Détail')]
    )]
    public function ligne(int $id, int $ligneId): JsonResponse
    {
        return $this->successResponse(
            new PaieLotLigneResource($this->service->getLigne($id, $ligneId))
        );
    }

    #[OA\Get(
        path: '/api/paie/lots/{id}/lignes/{ligneId}/bulletin',
        operationId: 'bulletinPaieLotLigne',
        tags: ['Paie'],
        summary: 'Bulletin de paie PDF enrichi d\'une ligne de lot',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ligneId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'PDF')]
    )]
    public function bulletin(int $id, int $ligneId): Response
    {
        return $this->bulletinService->genererPdf($id, $ligneId);
    }

    #[OA\Get(
        path: '/api/paie/agents/{agent}/bulletins',
        operationId: 'paieBulletinsParAgent',
        tags: ['Paie'],
        summary: 'Bulletins clôturés d\'un agent',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Liste')]
    )]
    public function bulletinsAgent(int $agent): JsonResponse
    {
        return $this->collectionResponse(
            PaieLotLigneResource::collection($this->service->getBulletinsAgent($agent))
        );
    }
}
