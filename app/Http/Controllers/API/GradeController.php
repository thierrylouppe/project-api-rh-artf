<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Grade\CreateRequest;
use App\Http\Requests\Grade\UpdateRequest;
use App\Http\Resources\GradeResource;
use App\Services\GradeService;
use Illuminate\Http\JsonResponse;

class GradeController extends BaseController
{
    public function __construct(GradeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return GradeResource::class;
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
