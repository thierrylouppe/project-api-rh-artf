<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Administration\CreateRequest;
use App\Http\Requests\Administration\UpdateRequest;
use App\Http\Resources\AdministrationResource;
use App\Services\AdministrationService;
use Illuminate\Http\JsonResponse;

class AdministrationController extends BaseController
{
    public function __construct(AdministrationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AdministrationResource::class;
    }

    protected function showRelations(): array
    {
        return ['localite', 'directions'];
    }

    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Créé avec succès', 201);
    }

    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Mis à jour avec succès');
    }

    public function byLocalite(int $localiteId): JsonResponse
    {
        $items = $this->service->getByLocalite($localiteId);

        return response()->json(['data' => AdministrationResource::collection($items)]);
    }
}
