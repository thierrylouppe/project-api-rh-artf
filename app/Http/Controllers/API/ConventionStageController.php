<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Stage\CloturerRequest;
use App\Http\Requests\Stage\ProlongerRequest;
use App\Http\Resources\ConventionStageResource;
use App\Services\ClotureStageService;
use App\Services\ConventionStageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConventionStageController extends BaseController
{
    public function __construct(
        private readonly ConventionStageService $conventionService,
        private readonly ClotureStageService $clotureService,
    ) {
        parent::__construct($conventionService);
    }

    protected function resource(): string
    {
        return ConventionStageResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'tuteurInterne', 'contrat', 'dossier.typeIntegration'];
    }

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request->query());

        return response()->json(['data' => ConventionStageResource::collection($items)]);
    }

    public function prolonger(ProlongerRequest $request, int $id): JsonResponse
    {
        $convention = $this->conventionService->prolonger($id, $request->input('date_fin'));

        return response()->json([
            'data'    => new ConventionStageResource($convention),
            'message' => 'Convention prolongée jusqu\'au ' . $request->input('date_fin'),
        ]);
    }

    public function cloturer(CloturerRequest $request, int $id): JsonResponse
    {
        $convention = $this->clotureService->cloturer(
            $id,
            (float) $request->input('note'),
            $request->input('appreciation'),
        );

        return response()->json([
            'data'    => new ConventionStageResource($convention),
            'message' => 'Stage clôturé avec succès',
        ]);
    }

    public function attestation(int $id): Response
    {
        return $this->clotureService->genererAttestationPdf($id);
    }
}
