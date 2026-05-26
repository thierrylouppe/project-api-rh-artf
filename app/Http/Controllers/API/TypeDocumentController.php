<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TypeDocument\CreateRequest;
use App\Http\Requests\TypeDocument\UpdateRequest;
use App\Http\Resources\TypeDocumentResource;
use App\Services\TypeDocumentService;
use Illuminate\Http\JsonResponse;

class TypeDocumentController extends BaseController
{
    public function __construct(TypeDocumentService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return TypeDocumentResource::class;
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
