<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TypeIntegration\CreateRequest;
use App\Http\Requests\TypeIntegration\UpdateRequest;
use App\Http\Resources\TypeIntegrationResource;
use App\Services\TypeIntegrationService;
use Illuminate\Http\JsonResponse;

class TypeIntegrationController extends BaseController
{
    public function __construct(TypeIntegrationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return TypeIntegrationResource::class;
    }

    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Créé avec succès', 201);
    }

    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Mis à jour avec succès');
    }
}
