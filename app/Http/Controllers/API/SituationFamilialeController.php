<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\SituationFamiliale\UpsertRequest;
use App\Http\Resources\SituationFamilialeResource;
use App\Services\SituationFamilialeService;
use Illuminate\Http\JsonResponse;

class SituationFamilialeController extends BaseController
{
    public function __construct(SituationFamilialeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return SituationFamilialeResource::class;
    }

    public function afficher(int $agent): JsonResponse
    {
        $item = $this->service->getByAgent($agent);

        if ($item === null) {
            return response()->json(['data' => null, 'message' => 'Non renseigné']);
        }

        return $this->respond($item);
    }

    public function upsert(UpsertRequest $request, int $agent): JsonResponse
    {
        return $this->respond($this->service->upsert($agent, $request->validated()), 'Situation familiale enregistrée');
    }
}
