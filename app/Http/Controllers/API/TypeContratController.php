<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TypeContrat\CreateRequest;
use App\Http\Requests\TypeContrat\UpdateRequest;
use App\Http\Resources\TypeContratResource;
use App\Services\TypeContratService;
use Illuminate\Http\JsonResponse;

class TypeContratController extends BaseController
{
    public function __construct(TypeContratService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return TypeContratResource::class;
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
