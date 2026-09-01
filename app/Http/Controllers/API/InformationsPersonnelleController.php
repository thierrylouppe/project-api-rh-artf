<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\InformationsPersonnelle\UpsertRequest;
use App\Http\Resources\InformationsPersonnelleResource;
use App\Services\InformationsPersonnelleService;
use Illuminate\Http\JsonResponse;

class InformationsPersonnelleController extends BaseController
{
    public function __construct(InformationsPersonnelleService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return InformationsPersonnelleResource::class;
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
        return $this->respond($this->service->upsert($agent, $request->validated()), 'Informations personnelles enregistrées');
    }
}
