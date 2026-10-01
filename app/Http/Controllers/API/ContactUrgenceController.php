<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ContactUrgence\CreateRequest;
use App\Http\Requests\ContactUrgence\UpdateRequest;
use App\Http\Resources\ContactUrgenceResource;
use App\Services\ContactUrgenceService;
use Illuminate\Http\JsonResponse;

class ContactUrgenceController extends BaseController
{
    public function __construct(ContactUrgenceService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ContactUrgenceResource::class;
    }

    public function lister(int $agent): JsonResponse
    {
        return $this->collectionResponse(ContactUrgenceResource::collection($this->service->getByAgent($agent)));
    }

    public function store(CreateRequest $request, int $agent): JsonResponse
    {
        return $this->respond($this->service->createForAgent($agent, $request->validated()), 'Contact d\'urgence ajouté', 201);
    }

    public function update(UpdateRequest $request, int $agent, int $id): JsonResponse
    {
        return $this->respond($this->service->updateForAgent($agent, $id, $request->validated()), 'Contact d\'urgence mis à jour');
    }

    public function supprimer(int $agent, int $id): JsonResponse
    {
        $this->service->deleteForAgent($agent, $id);

        return $this->messageResponse('Contact d\'urgence supprimé');
    }
}
