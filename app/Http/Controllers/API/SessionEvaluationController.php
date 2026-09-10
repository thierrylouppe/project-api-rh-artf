<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\SessionEvaluation\CreateRequest;
use App\Http\Requests\SessionEvaluation\UpdateRequest;
use App\Http\Resources\SessionEvaluationResource;
use App\Services\SessionEvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Gestion des sessions d'évaluation.
 * Réservé à la RH (permission : creer-evaluations).
 *
 * CCN ARTF art. 60–62.
 */
class SessionEvaluationController extends BaseController
{
    public function __construct(private readonly SessionEvaluationService $sessionService)
    {
        parent::__construct($sessionService);
    }

    protected function resource(): string
    {
        return SessionEvaluationResource::class;
    }

    protected function showRelations(): array
    {
        return ['evaluations'];
    }

    #[OA\Get(
        path: '/api/avancements/sessions',
        operationId: 'listSessionsEvaluation',
        tags: ['Évaluation'],
        summary: 'Liste des sessions d\'évaluation',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Liste des sessions')]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(
        path: '/api/avancements/sessions/{id}',
        operationId: 'showSessionEvaluation',
        tags: ['Évaluation'],
        summary: 'Détail d\'une session',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Détail de la session')]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(
        path: '/api/avancements/sessions',
        operationId: 'storeSessionEvaluation',
        tags: ['Évaluation'],
        summary: 'Ouvrir une nouvelle session d\'évaluation (génère les fiches éligibles)',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Session créée avec fiches')]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        $session = $this->sessionService->create(
            array_merge($request->validated(), ['created_by' => $request->user()?->id])
        );

        return $this->respond($session, 'Session d\'évaluation ouverte. Fiches générées pour les agents éligibles.', 201);
    }

    #[OA\Put(
        path: '/api/avancements/sessions/{id}',
        operationId: 'updateSessionEvaluation',
        tags: ['Évaluation'],
        summary: 'Mettre à jour les métadonnées d\'une session (description, dates)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Mise à jour effectuée')]
    )]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Session mise à jour');
    }

    #[OA\Post(
        path: '/api/avancements/sessions/{id}/cloturer',
        operationId: 'cloturerSessionEvaluation',
        tags: ['Évaluation'],
        summary: 'Clôturer une session ouverte',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Session clôturée')]
    )]
    public function cloturer(Request $request, int $id): JsonResponse
    {
        $session = $this->sessionService->cloturer($id, $request->user()->id);

        return $this->respond($session, 'Session clôturée.');
    }

    #[OA\Post(
        path: '/api/avancements/sessions/{id}/annuler',
        operationId: 'annulerSessionEvaluation',
        tags: ['Évaluation'],
        summary: 'Annuler une session ouverte (annule les fiches non finalisées)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Session annulée')]
    )]
    public function annuler(Request $request, int $id): JsonResponse
    {
        $session = $this->sessionService->annuler($id, $request->user()->id);

        return $this->respond($session, 'Session annulée. Les fiches non finalisées ont été annulées.');
    }

    #[OA\Post(
        path: '/api/avancements/sessions/{id}/generer-fiches',
        operationId: 'genererFichesSession',
        tags: ['Évaluation'],
        summary: 'Regénérer les fiches manquantes pour une session ouverte',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Fiches générées')]
    )]
    public function genererFiches(int $id): JsonResponse
    {
        /** @var \App\Models\SessionEvaluation $session */
        $session = $this->service->findById($id);
        $fiches  = $this->sessionService->genererFiches($session);

        return $this->successResponse(
            ['nb_fiches_creees' => $fiches->count()],
            "{$fiches->count()} fiche(s) créée(s)."
        );
    }

    #[OA\Get(
        path: '/api/avancements/sessions/{id}/sans-superieur',
        operationId: 'agentsSansSuperieur',
        tags: ['Évaluation'],
        summary: 'Agents éligibles sans N+1 identifiable (vue RH — affectations à corriger)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Liste des agents sans notateur')]
    )]
    public function sansSupereur(int $id): JsonResponse
    {
        /** @var \App\Models\SessionEvaluation $session */
        $session = $this->service->findById($id);
        $agents  = $this->sessionService->agentsSansSuperieur($session);

        return $this->collectionResponse(
            \App\Http\Resources\AgentIdentiteResource::collection($agents)
        );
    }
}
