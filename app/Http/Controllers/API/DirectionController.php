<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Direction\CreateRequest;
use App\Http\Requests\Direction\UpdateRequest;
use App\Http\Resources\DirectionResource;
use App\Services\DirectionService;
use Illuminate\Http\JsonResponse;

class DirectionController extends BaseController
{
    public function __construct(DirectionService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return DirectionResource::class;
    }

    protected function showRelations(): array
    {
        return ['administration.localite', 'services'];
    }

    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Créé avec succès', 201);
    }

    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Mis à jour avec succès');
    }

    public function byAdministration(int $administrationId): JsonResponse
    {
        $items = $this->service->getByAdministration($administrationId);

        return response()->json(['data' => DirectionResource::collection($items)]);
    }
}
