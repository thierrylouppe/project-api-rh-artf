<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Categorie\CreateRequest;
use App\Http\Requests\Categorie\UpdateRequest;
use App\Http\Resources\CategorieResource;
use App\Services\CategorieService;
use Illuminate\Http\JsonResponse;

class CategorieController extends BaseController
{
    public function __construct(CategorieService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return CategorieResource::class;
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
