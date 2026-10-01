<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\QuestionEvaluation\CreateRequest;
use App\Http\Requests\QuestionEvaluation\UpdateRequest;
use App\Http\Resources\QuestionEvaluationResource;
use App\Services\QuestionEvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Gestion de la grille de questions d'évaluation (référentiel RH-paramétrable).
 * Réservé à la RH (permission : creer-evaluations).
 *
 * Phase 1 : CRUD simple.
 * Phase 2 : verrouillage si session ouverte avec fiches en cours.
 */
class QuestionEvaluationController extends BaseController
{
    public function __construct(QuestionEvaluationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return QuestionEvaluationResource::class;
    }

    #[OA\Get(
        path: '/api/avancements/questions-evaluation',
        operationId: 'listQuestionsEvaluation',
        tags: ['Évaluation'],
        summary: 'Grille des critères d\'évaluation',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Liste des critères')]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(
        path: '/api/avancements/questions-evaluation/{id}',
        operationId: 'showQuestionEvaluation',
        tags: ['Évaluation'],
        summary: 'Détail d\'un critère',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Détail du critère')]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(
        path: '/api/avancements/questions-evaluation',
        operationId: 'storeQuestionEvaluation',
        tags: ['Évaluation'],
        summary: 'Créer un critère d\'évaluation',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: 'Critère créé')]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Critère d\'évaluation créé', 201);
    }

    #[OA\Put(
        path: '/api/avancements/questions-evaluation/{id}',
        operationId: 'updateQuestionEvaluation',
        tags: ['Évaluation'],
        summary: 'Modifier un critère',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Mis à jour')]
    )]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Critère mis à jour');
    }

    #[OA\Delete(
        path: '/api/avancements/questions-evaluation/{id}',
        operationId: 'destroyQuestionEvaluation',
        tags: ['Évaluation'],
        summary: 'Supprimer un critère',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Supprimé')]
    )]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
