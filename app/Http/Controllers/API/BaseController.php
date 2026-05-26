<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseController extends Controller
{
    abstract protected function service(): BaseService;

    abstract protected function resource(): string;

    public function index(Request $request): JsonResponse
    {
        $items = $this->service()->getAll($request->query());

        return response()->json(['data' => $this->resource()::collection($items)]);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service()->findById($id);

        return response()->json(['data' => new ($this->resource())($item)]);
    }

    public function store(Request $request): JsonResponse
    {
        $item = $this->service()->create($request->validate($this->storeRules()));

        return response()->json(
            ['data' => new ($this->resource())($item), 'message' => 'Créé avec succès'],
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = $this->service()->update($id, $request->validate($this->updateRules()));

        return response()->json(
            ['data' => new ($this->resource())($item), 'message' => 'Mis à jour avec succès']
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service()->delete($id);

        return response()->json(['message' => 'Supprimé avec succès']);
    }

    protected function storeRules(): array
    {
        return [];
    }

    protected function updateRules(): array
    {
        return $this->storeRules();
    }
}
