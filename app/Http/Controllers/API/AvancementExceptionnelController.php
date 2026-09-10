<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\AvancementExceptionnel\ProposeAvancementRequest;
use App\Http\Requests\BonificationStage\TraiterBonificationRequest;
use App\Http\Resources\AvancementExceptionnelResource;
use App\Services\AvancementExceptionnelService;
use Illuminate\Http\JsonResponse;

/**
 * Avancement exceptionnel (CCN ARTF art. 72) — proposition DG, ≤ 2 échelons.
 */
class AvancementExceptionnelController extends BaseController
{
    public function __construct(private readonly AvancementExceptionnelService $avanService)
    {
        parent::__construct($avanService);
    }

    protected function resource(): string
    {
        return AvancementExceptionnelResource::class;
    }

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        return $this->collectionResponse(
            AvancementExceptionnelResource::collection($this->avanService->getAll())
        );
    }

    public function enAttente(): JsonResponse
    {
        return $this->collectionResponse(
            AvancementExceptionnelResource::collection($this->avanService->getEnAttente())
        );
    }

    public function store(ProposeAvancementRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $avan = $this->avanService->proposer(
            (int) $validated['agent_id'],
            (int) $validated['nb_echelons'],
            $validated['motif'],
            $request->user(),
            $validated,
        );

        return response()->json([
            'data'    => new AvancementExceptionnelResource($avan),
            'message' => "Proposition d'avancement exceptionnel soumise (+{$avan->nb_echelons} échelon(s)).",
        ], 201);
    }

    public function traiter(TraiterBonificationRequest $request, int $id): JsonResponse
    {
        $avan = $this->avanService->traiter(
            $id,
            $request->user(),
            (bool) $request->validated('approuver'),
            $request->validated('commentaire'),
        );

        $msg = $avan->statut->value === 'approuvee' ? 'Proposition approuvée.' : 'Proposition rejetée.';

        return $this->respond($avan, $msg);
    }

    public function appliquer(int $id): JsonResponse
    {
        $result = $this->avanService->appliquer($id, request()->user());

        return response()->json([
            'data'    => $result,
            'message' => $result['message'],
        ]);
    }
}
