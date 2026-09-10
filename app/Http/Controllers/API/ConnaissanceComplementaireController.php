<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ConnaissanceComplementaire\StoreConnaissanceRequest;
use App\Http\Resources\ConnaissanceComplementaireResource;
use App\Services\ConnaissanceComplementaireService;
use Illuminate\Http\JsonResponse;

/**
 * Connaissances complémentaires / besoins de formation liés à une fiche.
 */
class ConnaissanceComplementaireController extends BaseController
{
    public function __construct(private readonly ConnaissanceComplementaireService $connService)
    {
        parent::__construct($connService);
    }

    protected function resource(): string
    {
        return ConnaissanceComplementaireResource::class;
    }

    public function parEvaluation(int $evaluationId): JsonResponse
    {
        return $this->collectionResponse(
            ConnaissanceComplementaireResource::collection(
                $this->connService->parEvaluation($evaluationId)
            )
        );
    }

    public function store(StoreConnaissanceRequest $request, int $evaluationId): JsonResponse
    {
        $conn = $this->connService->ajouter($evaluationId, $request->validated(), $request->user());

        return response()->json([
            'data'    => new ConnaissanceComplementaireResource($conn),
            'message' => 'Besoin de formation enregistré.',
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->connService->delete($id);

        return response()->json(['message' => 'Supprimé.']);
    }
}
