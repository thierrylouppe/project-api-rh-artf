<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Diplome\CreateRequest;
use App\Http\Requests\Diplome\UpdateRequest;
use App\Http\Resources\DiplomeResource;
use App\Services\DiplomeService;
use Illuminate\Http\JsonResponse;

class DiplomeController extends BaseController
{
    public function __construct(DiplomeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return DiplomeResource::class;
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
