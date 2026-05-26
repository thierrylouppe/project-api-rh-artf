<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TypeAbsence\CreateRequest;
use App\Http\Requests\TypeAbsence\UpdateRequest;
use App\Http\Resources\TypeAbsenceResource;
use App\Services\TypeAbsenceService;
use Illuminate\Http\JsonResponse;

class TypeAbsenceController extends BaseController
{
    public function __construct(TypeAbsenceService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return TypeAbsenceResource::class;
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
