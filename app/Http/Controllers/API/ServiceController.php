<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ServiceRH\CreateRequest;
use App\Http\Requests\ServiceRH\UpdateRequest;
use App\Http\Resources\ServiceResource;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;

class ServiceController extends BaseController
{
    public function __construct(ServiceService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ServiceResource::class;
    }

    protected function showRelations(): array
    {
        return ['direction.administration', 'bureaux'];
    }

    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Créé avec succès', 201);
    }

    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Mis à jour avec succès');
    }

    public function byDirection(int $directionId): JsonResponse
    {
        $items = $this->service->getByDirection($directionId);

        return response()->json(['data' => ServiceResource::collection($items)]);
    }
}
