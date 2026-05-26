<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Role\CreateRequest;
use App\Http\Requests\Role\UpdateRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends BaseController
{
    public function __construct(RoleService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return RoleResource::class;
    }

    protected function showRelations(): array
    {
        return ['permissions'];
    }

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->getAllWithPermissions();

        return response()->json(['data' => RoleResource::collection($items)]);
    }

    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond(
            $this->service->createRole($request->validated()),
            'Créé avec succès',
            201
        );
    }

    public function update(UpdateRequest $request, int $role): JsonResponse
    {
        return $this->respond(
            $this->service->updateRole($role, $request->validated()),
            'Mis à jour avec succès'
        );
    }

    public function dupliquer(int $role): JsonResponse
    {
        return $this->respond(
            $this->service->dupliquer($role),
            'Rôle dupliqué avec succès',
            201
        );
    }

    public function destroy(int $role): JsonResponse
    {
        $this->service->delete($role);

        return response()->json(['message' => 'Supprimé avec succès']);
    }
}
