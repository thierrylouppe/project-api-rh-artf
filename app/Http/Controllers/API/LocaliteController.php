<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Localite\CreateRequest;
use App\Http\Requests\Localite\UpdateRequest;
use App\Http\Resources\LocaliteResource;
use App\Services\LocaliteService;
use Illuminate\Http\JsonResponse;

class LocaliteController extends BaseController
{
    public function __construct(LocaliteService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return LocaliteResource::class;
    }

    protected function showRelations(): array
    {
        return ['administrations'];
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
