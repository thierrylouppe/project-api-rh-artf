<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TypeConge\CreateRequest;
use App\Http\Requests\TypeConge\UpdateRequest;
use App\Http\Resources\TypeCongeResource;
use App\Services\TypeCongeService;
use Illuminate\Http\JsonResponse;

class TypeCongeController extends BaseController
{
    public function __construct(TypeCongeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return TypeCongeResource::class;
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
