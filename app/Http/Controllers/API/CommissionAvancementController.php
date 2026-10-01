<?php

namespace App\Http\Controllers\API;

use App\Enums\DecisionCommission;
use App\Http\Requests\Commission\CloturerRequest;
use App\Http\Requests\Commission\DeciderRequest;
use App\Http\Requests\Commission\OuvrirRequest;
use App\Http\Resources\CommissionAvancementResource;
use App\Http\Resources\EvaluationResource;
use App\Services\CommissionAvancementService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Commission d'avancement (CCN ARTF art. 69–70).
 * Une par session. Décide de l'avancement pour chaque fiche finalisée.
 */
class CommissionAvancementController extends BaseController
{
    public function __construct(private readonly CommissionAvancementService $commService)
    {
        parent::__construct($commService);
    }

    protected function resource(): string
    {
        return CommissionAvancementResource::class;
    }

    #[OA\Post(
        path: '/api/avancements/sessions/{sessionId}/commission-avancement',
        operationId: 'ouvrirCommissionAvan',
        tags: ['Commissions'],
        summary: 'Ouvrir la commission d\'avancement (prérequis : commission préparatoire clôturée)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'sessionId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Commission ouverte')]
    )]
    public function ouvrir(OuvrirRequest $request, int $sessionId): JsonResponse
    {
        $commission = $this->commService->ouvrir($sessionId, $request->user(), $request->validated());

        return response()->json([
            'data'    => new CommissionAvancementResource($commission),
            'message' => 'Commission d\'avancement ouverte.',
        ], 201);
    }

    #[OA\Get(
        path: '/api/avancements/sessions/{sessionId}/commission-avancement',
        operationId: 'getCommissionAvan',
        tags: ['Commissions'],
        summary: 'Commission d\'avancement d\'une session',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'sessionId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Commission')]
    )]
    public function parSession(int $sessionId): JsonResponse
    {
        $commission = \App\Models\CommissionAvancement::with('session')
            ->where('session_id', $sessionId)->firstOrFail();

        return response()->json(['data' => new CommissionAvancementResource($commission)]);
    }

    #[OA\Post(
        path: '/api/avancements/commissions-avancements/{id}/decider',
        operationId: 'deciderCommissionAvan',
        tags: ['Commissions'],
        summary: 'Enregistrer la décision d\'avancement pour une fiche (art. 70)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Décision enregistrée')]
    )]
    public function decider(DeciderRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $evaluation = $this->commService->decider(
            $id,
            (int) $validated['evaluation_id'],
            DecisionCommission::from($validated['decision']),
            (int) ($validated['nombre_echelons'] ?? 0),
            isset($validated['note_avancement']) ? (float) $validated['note_avancement'] : null,
            $validated['commentaire'] ?? null,
        );

        return response()->json([
            'data'    => new EvaluationResource($evaluation),
            'message' => 'Décision enregistrée.',
        ]);
    }

    #[OA\Post(
        path: '/api/avancements/evaluations/{evaluationId}/avancer-echelon',
        operationId: 'avancerEchelon',
        tags: ['Commissions'],
        summary: 'Appliquer l\'avancement d\'échelon en paie (idempotent, D6)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'evaluationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Échelon avancé')]
    )]
    public function avancerEchelon(int $evaluationId): JsonResponse
    {
        $result = $this->commService->avancerEchelon($evaluationId);

        return response()->json([
            'data'    => $result,
            'message' => $result['message'],
        ]);
    }

    #[OA\Post(
        path: '/api/avancements/commissions-avancements/{id}/cloturer',
        operationId: 'cloturerCommissionAvan',
        tags: ['Commissions'],
        summary: 'Clôturer la commission d\'avancement (prérequis pour clôturer la session)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Commission clôturée')]
    )]
    public function cloturer(CloturerRequest $request, int $id): JsonResponse
    {
        $commission = $this->commService->cloturer(
            $id,
            $request->user(),
            $request->validated('observations'),
        );

        return $this->respond($commission, 'Commission d\'avancement clôturée. La session peut être clôturée.');
    }
}
