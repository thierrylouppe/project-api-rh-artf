<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Fonction\CreateRequest;
use App\Http\Requests\Fonction\UpdateRequest;
use App\Http\Resources\FonctionResource;
use App\Services\FonctionService;
use Illuminate\Http\JsonResponse;

class FonctionController extends BaseController
{
    public function __construct(FonctionService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return FonctionResource::class;
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
