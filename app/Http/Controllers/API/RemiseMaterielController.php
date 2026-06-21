<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\RemiseMateriel\CreateRequest;
use App\Http\Resources\RemiseMaterielResource;
use App\Services\RemiseMaterielService;
use Illuminate\Http\JsonResponse;

class RemiseMaterielController extends BaseController
{
    public function __construct(RemiseMaterielService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return RemiseMaterielResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'remiseur'];
    }

    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Remise de matériel enregistrée', 201);
    }

    public function byAgent(int $agentId): JsonResponse
    {
        $remises = $this->service->getByAgent($agentId);

        return response()->json(['data' => RemiseMaterielResource::collection($remises)]);
    }
}
