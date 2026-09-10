<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\AvisHierarchique\PostAvisRequest;
use App\Http\Resources\AvisHierarchiqueResource;
use App\Services\AvisHierarchiqueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Avis hiérarchiques séquentiels (CCN ARTF art. 64).
 *
 * Chaîne : chef_bureau → chef_service → directeur → directeur_general
 * Variante DG : chef_bureau → chef_service → directeur_general (si direction.rattache_dg)
 *
 * Séquentialité : le niveau N ne peut être posé que si N−1 est signé.
 * Signature définitive : irreversible.
 */
class AvisHierarchiqueController extends BaseController
{
    public function __construct(private readonly AvisHierarchiqueService $avisService)
    {
        parent::__construct($avisService);
    }

    protected function resource(): string
    {
        return AvisHierarchiqueResource::class;
    }

    // ----------------------------------------------------------------
    // Lecture
    // ----------------------------------------------------------------

    #[OA\Get(
        path: '/api/avancements/evaluations/{evaluationId}/avis-hierarchiques',
        operationId: 'listAvisHierarchiques',
        tags: ['Évaluation'],
        summary: 'Avis hiérarchiques d\'une fiche, ordonnés',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'evaluationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Liste des avis')]
    )]
    public function indexParEvaluation(int $evaluationId): JsonResponse
    {
        $avis = $this->avisService->listeParEvaluation($evaluationId);

        return $this->collectionResponse(AvisHierarchiqueResource::collection($avis));
    }

    #[OA\Get(
        path: '/api/avancements/evaluations/{evaluationId}/niveaux-requis',
        operationId: 'niveauxRequis',
        tags: ['Évaluation'],
        summary: 'Niveaux d\'avis hiérarchiques requis pour une fiche (selon position de l\'évalué)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'evaluationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Niveaux requis')]
    )]
    public function niveauxRequis(int $evaluationId): JsonResponse
    {
        /** @var \App\Models\Evaluation $evaluation */
        $evaluation = app(\App\Interfaces\EvaluationInterface::class)->findById($evaluationId);

        $niveaux = $this->avisService->niveauxRequis($evaluation);

        return response()->json([
            'data' => array_map(fn ($n) => [
                'niveau' => $n->value,
                'label'  => $n->label(),
            ], $niveaux),
        ]);
    }

    // ----------------------------------------------------------------
    // Actions
    // ----------------------------------------------------------------

    #[OA\Post(
        path: '/api/avancements/evaluations/{evaluationId}/avis-hierarchiques',
        operationId: 'posterAvis',
        tags: ['Évaluation'],
        summary: 'Poster (ou mettre à jour) un avis hiérarchique non signé',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'evaluationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Avis posté')]
    )]
    public function poster(PostAvisRequest $request, int $evaluationId): JsonResponse
    {
        $avis = $this->avisService->poster(
            $evaluationId,
            $request->validated('niveau'),
            $request->only(['avis', 'approuve', 'observations']),
            $request->user(),
        );

        $avis->load('signePar');

        return response()->json([
            'data'    => new AvisHierarchiqueResource($avis),
            'message' => 'Avis enregistré.',
        ], 201);
    }

    #[OA\Put(
        path: '/api/avancements/avis-hierarchiques/{id}',
        operationId: 'updateAvis',
        tags: ['Évaluation'],
        summary: 'Mettre à jour un avis non signé',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Avis mis à jour')]
    )]
    public function update(PostAvisRequest $request, int $id): JsonResponse
    {
        /** @var \App\Models\AvisHierarchique $avis */
        $avis = $this->avisService->findById($id);

        if ($avis->signe) {
            abort(422, 'Cet avis a déjà été signé. Il n\'est plus modifiable.');
        }

        $updated = $this->avisService->poster(
            $avis->evaluation_id,
            $request->validated('niveau') ?? $avis->niveau->value,
            $request->only(['avis', 'approuve', 'observations']),
            $request->user(),
        );

        return $this->respond($updated, 'Avis mis à jour.');
    }

    #[OA\Post(
        path: '/api/avancements/avis-hierarchiques/{id}/signer',
        operationId: 'signerAvis',
        tags: ['Évaluation'],
        summary: 'Signer un avis hiérarchique (action définitive)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Avis signé')]
    )]
    public function signer(Request $request, int $id): JsonResponse
    {
        $avis = $this->avisService->signer($id, $request->user());

        return $this->respond($avis, 'Avis signé. Cette action est définitive.');
    }
}
