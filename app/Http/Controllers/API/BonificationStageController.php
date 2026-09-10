<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\BonificationStage\SoumettreBonificationRequest;
use App\Http\Requests\BonificationStage\TraiterBonificationRequest;
use App\Http\Resources\BonificationStageResource;
use App\Services\BonificationStageService;
use Illuminate\Http\JsonResponse;

/**
 * Bonification +2 échelons après stage ≥ 9 mois (CCN ARTF art. 71).
 */
class BonificationStageController extends BaseController
{
    public function __construct(private readonly BonificationStageService $bonifService)
    {
        parent::__construct($bonifService);
    }

    protected function resource(): string
    {
        return BonificationStageResource::class;
    }

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        return $this->collectionResponse(
            BonificationStageResource::collection($this->bonifService->getAll())
        );
    }

    public function enAttente(): JsonResponse
    {
        return $this->collectionResponse(
            BonificationStageResource::collection($this->bonifService->getEnAttente())
        );
    }

    public function store(SoumettreBonificationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $bonif = $this->bonifService->soumettre(
            (int) $validated['agent_id'],
            $validated,
            $request->user(),
        );

        return response()->json([
            'data'    => new BonificationStageResource($bonif),
            'message' => "Demande de bonification soumise ({$bonif->duree_mois} mois de stage).",
        ], 201);
    }

    public function traiter(TraiterBonificationRequest $request, int $id): JsonResponse
    {
        $bonif = $this->bonifService->traiter(
            $id,
            $request->user(),
            (bool) $request->validated('approuver'),
            $request->validated('commentaire'),
        );

        $msg = $bonif->statut->value === 'approuvee' ? 'Demande approuvée.' : 'Demande rejetée.';

        return $this->respond($bonif, $msg);
    }

    public function appliquer(int $id): JsonResponse
    {
        $result = $this->bonifService->appliquer($id, request()->user());

        return response()->json([
            'data'    => $result,
            'message' => $result['message'],
        ]);
    }
}
