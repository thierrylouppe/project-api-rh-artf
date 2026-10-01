<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Reclassement\StoreReclassementRequest;
use App\Http\Requests\Reclassement\TraiterReclassementRequest;
use App\Http\Resources\ReclassementResource;
use App\Services\ReclassementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reclassement / hors classe / reconversion (CCN ARTF art. 73–75).
 * Hors module notation — préfixe /api/carriere/reclassements.
 */
class ReclassementController extends BaseController
{
    public function __construct(private readonly ReclassementService $reclassementService)
    {
        parent::__construct($reclassementService);
    }

    protected function resource(): string
    {
        return ReclassementResource::class;
    }

    protected function showRelations(): array
    {
        return [
            'agent',
            'classeOrigine.grade',
            'classeOrigine.categorie',
            'classeCible.grade',
            'classeCible.categorie',
            'fonctionCible',
            'diplome',
        ];
    }

    public function index(Request $request): JsonResponse
    {
        return $this->collectionResponse(
            ReclassementResource::collection($this->reclassementService->getAll($request->query()))
        );
    }

    public function show(Request $request): JsonResponse
    {
        $id = (int) collect($request->route()->parameters())->first();

        return $this->successResponse(
            new ReclassementResource($this->reclassementService->detail($id))
        );
    }

    public function parAgent(int $id): JsonResponse
    {
        return $this->collectionResponse(
            ReclassementResource::collection($this->reclassementService->parAgent($id))
        );
    }

    public function store(StoreReclassementRequest $request): JsonResponse
    {
        $dossier = $this->reclassementService->soumettre($request->validated(), $request->user());

        return $this->successResponse(
            new ReclassementResource($this->reclassementService->detail($dossier->id)),
            'Demande de reclassement soumise.',
            201
        );
    }

    public function approuver(TraiterReclassementRequest $request, int $id): JsonResponse
    {
        $dossier = $this->reclassementService->approuver(
            $id,
            $request->user(),
            $request->validated('commentaire'),
        );

        return $this->respond($this->reclassementService->detail($dossier->id), 'Reclassement approuvé.');
    }

    public function rejeter(TraiterReclassementRequest $request, int $id): JsonResponse
    {
        $dossier = $this->reclassementService->rejeter(
            $id,
            $request->user(),
            $request->validated('commentaire'),
        );

        return $this->respond($this->reclassementService->detail($dossier->id), 'Reclassement rejeté.');
    }

    public function appliquer(int $id): JsonResponse
    {
        $result = $this->reclassementService->appliquer($id, request()->user());

        return response()->json([
            'success' => true,
            'data'    => new ReclassementResource($result['data']),
            'message' => $result['message'],
            'meta'    => ['applique' => $result['applique']],
        ]);
    }
}
