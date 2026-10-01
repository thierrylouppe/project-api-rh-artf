<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseController extends Controller
{
    use ApiResponse;

    public function __construct(protected BaseService $service) {}

    abstract protected function resource(): string;

    /** Resource utilisée pour les listes (index). Par défaut = resource de détail. */
    protected function listResource(): string
    {
        return $this->resource();
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->query();

        // Vague F — si le middleware ScopeByBureau a injecté un utilisateur, le propager
        // au repository via les clés internes _scope_user / _scope_niveau.
        if ($scopeUser = $request->get('bureau_scope_user')) {
            $filters['_scope_user']  = $scopeUser;
            $filters['_scope_niveau'] = $request->get('bureau_scope_niveau', $scopeUser->niveauCloisonnement());
        }

        $items = $this->service->getAll($filters);

        return $this->collectionResponse($this->listResource()::collection($items));
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

        return $this->messageResponse('Supprimé avec succès');
    }

    protected function respond(mixed $model, ?string $message = null, int $status = 200): JsonResponse
    {
        return $this->successResponse(
            new ($this->resource())($model),
            $message,
            $status
        );
    }
}
