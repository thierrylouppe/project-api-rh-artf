<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TypeRecrutement\CreateRequest;
use App\Http\Requests\TypeRecrutement\UpdateRequest;
use App\Http\Resources\TypeRecrutementResource;
use App\Services\TypeRecrutementService;
use Illuminate\Http\JsonResponse;

class TypeRecrutementController extends BaseController
{
    public function __construct(TypeRecrutementService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return TypeRecrutementResource::class;
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
