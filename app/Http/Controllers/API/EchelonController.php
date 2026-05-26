<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Echelon\CreateRequest;
use App\Http\Requests\Echelon\UpdateRequest;
use App\Http\Resources\EchelonResource;
use App\Services\EchelonService;
use Illuminate\Http\JsonResponse;

class EchelonController extends BaseController
{
    public function __construct(EchelonService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return EchelonResource::class;
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
