<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Commission\CloturerRequest;
use App\Http\Requests\Commission\NoterFicheRequest;
use App\Http\Requests\Commission\OuvrirRequest;
use App\Http\Resources\CommissionPreparatoireResource;
use App\Http\Resources\EvaluationResource;
use App\Services\CommissionPreparatoireService;
use App\Services\EvaluationPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * Commission préparatoire (CCN ARTF art. 68).
 * Une par session. Harmonise les notes N+1 et produit les notes de synthèse.
 */
class CommissionPreparatoireController extends BaseController
{
    public function __construct(
        private readonly CommissionPreparatoireService $commService,
        private readonly EvaluationPdfService          $pdfService,
    ) {
        parent::__construct($commService);
    }

    protected function resource(): string
    {
        return CommissionPreparatoireResource::class;
    }

    #[OA\Post(
        path: '/api/avancements/sessions/{sessionId}/commission-preparatoire',
        operationId: 'ouvrirCommissionPrep',
        tags: ['Commissions'],
        summary: 'Ouvrir la commission préparatoire pour une session',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'sessionId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 201, description: 'Commission ouverte')]
    )]
    public function ouvrir(OuvrirRequest $request, int $sessionId): JsonResponse
    {
        $commission = $this->commService->ouvrir($sessionId, $request->user(), $request->validated());

        return response()->json([
            'data'    => new CommissionPreparatoireResource($commission),
            'message' => 'Commission préparatoire ouverte.',
        ], 201);
    }

    #[OA\Get(
        path: '/api/avancements/sessions/{sessionId}/commission-preparatoire',
        operationId: 'getCommissionPrep',
        tags: ['Commissions'],
        summary: 'Commission préparatoire d\'une session',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'sessionId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Commission')]
    )]
    public function parSession(int $sessionId): JsonResponse
    {
        $commission = \App\Models\CommissionPreparatoire::with('session')
            ->where('session_id', $sessionId)->firstOrFail();

        return response()->json(['data' => new CommissionPreparatoireResource($commission)]);
    }

    #[OA\Post(
        path: '/api/avancements/commissions-preparatoires/{id}/noter',
        operationId: 'noterFichePrep',
        tags: ['Commissions'],
        summary: 'Harmoniser la note d\'une fiche (commission_note) + note de synthèse facultative',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Note harmonisée')]
    )]
    public function noter(NoterFicheRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->commService->noterFiche(
            $id,
            (int) $validated['evaluation_id'],
            (float) $validated['commission_note'],
        );

        // Si note de synthèse fournie, la sauvegarder aussi
        if (! empty($validated['note_synthese'])) {
            $evaluation = $this->commService->redigerSynthese(
                $id,
                (int) $validated['evaluation_id'],
                $validated['note_synthese']
            );

            $result['note_synthese_sauvee'] = true;
        }

        return response()->json([
            'data'    => $result,
            'message' => $result['message'],
        ]);
    }

    #[OA\Get(
        path: '/api/avancements/commissions-preparatoires/{id}/alertes',
        operationId: 'alertesEcart',
        tags: ['Commissions'],
        summary: 'Fiches avec écart > 5 pts entre note N+1 et note commission',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Fiches avec alerte')]
    )]
    public function alertes(int $id): JsonResponse
    {
        $commission = \App\Models\CommissionPreparatoire::findOrFail($id);
        $fiches     = $this->commService->fichesAvecAlerte($commission->session_id);

        return $this->collectionResponse(EvaluationResource::collection($fiches));
    }

    #[OA\Post(
        path: '/api/avancements/commissions-preparatoires/{id}/cloturer',
        operationId: 'cloturerCommissionPrep',
        tags: ['Commissions'],
        summary: 'Clôturer la commission préparatoire',
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

        return $this->respond($commission, 'Commission préparatoire clôturée. Vous pouvez ouvrir la commission d\'avancement.');
    }

    #[OA\Get(
        path: '/api/avancements/commissions-preparatoires/{id}/synthese-pdf',
        operationId: 'synthesePreparatoirePdf',
        tags: ['Commissions'],
        summary: 'PDF de la note de synthèse (art. 67) — commission clôturée',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'PDF')]
    )]
    public function synthesePdf(Request $request, int $id): Response
    {
        return $this->pdfService->synthesePdf($id, $request->user());
    }
}
