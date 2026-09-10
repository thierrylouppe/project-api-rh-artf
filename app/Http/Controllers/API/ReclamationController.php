<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Reclamation\TraiterRequest;
use App\Http\Resources\ReclamationResource;
use App\Services\ReclamationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Gestion des réclamations (vue RH).
 * CCN ARTF art. 65.
 */
class ReclamationController extends BaseController
{
    public function __construct(private readonly ReclamationService $reclamationService)
    {
        parent::__construct($reclamationService);
    }

    protected function resource(): string
    {
        return ReclamationResource::class;
    }

    #[OA\Get(
        path: '/api/avancements/reclamations',
        operationId: 'listReclamations',
        tags: ['Évaluation'],
        summary: 'Liste des réclamations (vue RH)',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Liste des réclamations')]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(
        path: '/api/avancements/reclamations/en-attente',
        operationId: 'reclamationsEnAttente',
        tags: ['Évaluation'],
        summary: 'Réclamations en attente de traitement',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Réclamations à traiter')]
    )]
    public function enAttente(): JsonResponse
    {
        $items = $this->reclamationService->enAttente();

        return $this->collectionResponse(ReclamationResource::collection($items));
    }

    #[OA\Get(
        path: '/api/avancements/reclamations/{id}',
        operationId: 'showReclamation',
        tags: ['Évaluation'],
        summary: 'Détail d\'une réclamation',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Détail')]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(
        path: '/api/avancements/reclamations/{id}/traiter',
        operationId: 'traiterReclamation',
        tags: ['Évaluation'],
        summary: 'RH traite une réclamation (acceptée = renvoi notateur, rejetée = maintien + envoi RH)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Réclamation traitée')]
    )]
    public function traiter(TraiterRequest $request, int $id): JsonResponse
    {
        $reclamation = $this->reclamationService->traiter(
            $id,
            $request->user(),
            (bool) $request->validated('acceptee'),
            $request->validated('commentaire'),
        );

        $message = $request->validated('acceptee')
            ? 'Réclamation acceptée. La fiche est renvoyée au notateur pour correction.'
            : 'Réclamation rejetée. La note est maintenue. La fiche est transmise en validation RH.';

        return $this->respond($reclamation, $message);
    }
}
