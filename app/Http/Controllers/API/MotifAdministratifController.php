<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\MotifAdministratif\CreateRequest;
use App\Http\Requests\MotifAdministratif\UpdateRequest;
use App\Http\Resources\MotifAdministratifResource;
use App\Services\MotifAdministratifService;
use Illuminate\Http\JsonResponse;

class MotifAdministratifController extends BaseController
{
    public function __construct(MotifAdministratifService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return MotifAdministratifResource::class;
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
