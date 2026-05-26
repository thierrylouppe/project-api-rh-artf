<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Bureau\CreateRequest;
use App\Http\Requests\Bureau\UpdateRequest;
use App\Http\Resources\BureauResource;
use App\Services\BureauService;
use Illuminate\Http\JsonResponse;

class BureauController extends BaseController
{
    public function __construct(BureauService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return BureauResource::class;
    }

    protected function showRelations(): array
    {
        return ['service.direction.administration'];
    }

    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Créé avec succès', 201);
    }

    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Mis à jour avec succès');
    }

    public function byService(int $serviceId): JsonResponse
    {
        $items = $this->service->getByService($serviceId);

        return response()->json(['data' => BureauResource::collection($items)]);
    }
}
