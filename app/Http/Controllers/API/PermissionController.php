<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\AssignToRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function __construct(private PermissionService $permissionService) {}

    public function index(): JsonResponse
    {
        $permissions = $this->permissionService->getAll();

        return response()->json(['data' => PermissionResource::collection($permissions)]);
    }

    public function assignToRole(AssignToRoleRequest $request, int $role): JsonResponse
    {
        $model = $this->permissionService->assignToRole($role, $request->validated('permissions'));

        return response()->json([
            'data' => new RoleResource($model),
            'message' => 'Permissions assignées avec succès',
        ]);
    }
}
