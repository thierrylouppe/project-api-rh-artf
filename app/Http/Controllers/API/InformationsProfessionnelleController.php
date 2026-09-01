<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\InformationsProfessionnelle\UpsertRequest;
use App\Http\Resources\InformationsProfessionnelleResource;
use App\Services\InformationsProfessionnelleService;
use Illuminate\Http\JsonResponse;

class InformationsProfessionnelleController extends BaseController
{
    public function __construct(InformationsProfessionnelleService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return InformationsProfessionnelleResource::class;
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
        return $this->respond($this->service->upsert($agent, $request->validated()), 'Informations professionnelles enregistrées');
    }
}
