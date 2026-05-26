<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\User\CreateRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function __construct(UserService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return UserResource::class;
    }

    protected function showRelations(): array
    {
        return ['roles.permissions'];
    }

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request->query());

        return response()->json(['data' => UserResource::collection($items)]);
    }

    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond(
            $this->service->createUser($request->validated()),
            'Créé avec succès',
            201
        );
    }

    public function update(UpdateRequest $request, int $user): JsonResponse
    {
        return $this->respond(
            $this->service->updateUser($user, $request->validated()),
            'Mis à jour avec succès'
        );
    }

    public function destroy(int $user): JsonResponse
    {
        $this->service->delete($user);

        return response()->json(['message' => 'Supprimé avec succès']);
    }
}
