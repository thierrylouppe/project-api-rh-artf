<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Classegrillesalariale\CreateRequest;
use App\Http\Requests\Classegrillesalariale\UpdateRequest;
use App\Http\Resources\ClassegrillesalarialeResource;
use App\Services\ClassegrillesalarialeService;
use Illuminate\Http\JsonResponse;

class ClassegrillesalarialeController extends BaseController
{
    public function __construct(ClassegrillesalarialeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ClassegrillesalarialeResource::class;
    }

    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Classe créée avec succès', 201);
    }

    public function update(UpdateRequest $request, int $classegrillesalariale): JsonResponse
    {
        return $this->respond($this->service->update($classegrillesalariale, $request->validated()), 'Classe mise à jour avec succès');
    }
}
