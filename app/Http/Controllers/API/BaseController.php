<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseController extends Controller
{
    public function __construct(protected BaseService $service) {}

    abstract protected function resource(): string;

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request->query());

        return response()->json(['data' => $this->resource()::collection($items)]);
    }

    public function show(Request $request): JsonResponse
    {
        $id = (int) collect($request->route()->parameters())->first();

        return $this->showWithRelations($id);
    }

    protected function showWithRelations(int $id): JsonResponse
    {
        $model = $this->service->findById($id);

        if ($relations = $this->showRelations()) {
            $model->load($relations);
        }

        return $this->respond($model);
    }

    /** @return list<string> */
    protected function showRelations(): array
    {
        return [];
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(['message' => 'Supprimé avec succès']);
    }

    protected function respond(mixed $model, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['data' => new ($this->resource())($model)];
        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }
}
