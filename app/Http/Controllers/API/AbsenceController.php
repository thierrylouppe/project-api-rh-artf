<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Absence\CreateRequest;
use App\Http\Requests\Absence\DecisionRequest;
use App\Http\Requests\Absence\RejectionRequest;
use App\Http\Resources\AbsenceResource;
use App\Services\AbsenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AbsenceController extends BaseController
{
    public function __construct(AbsenceService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AbsenceResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'typeAbsence'];
    }

    #[OA\Get(path: '/api/absences', operationId: 'listAbsences', tags: ['Absences'], summary: 'Liste des absences', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request->query());
        $items->load(['agent', 'typeAbsence']);

        return $this->collectionResponse($this->listResource()::collection($items));
    }

    #[OA\Post(path: '/api/absences', operationId: 'storeAbsence', tags: ['Absences'], summary: 'Déclarer une absence', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Absence déclarée', 201);
    }

    #[OA\Get(path: '/api/absences/agents/{agent}', operationId: 'absencesParAgent', tags: ['Absences'], summary: 'Absences d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return response()->json([
            'data'    => AbsenceResource::collection($this->service->getByAgent($agent)),
            'message' => 'Absences récupérées',
        ]);
    }

    #[OA\Post(path: '/api/absences/{id}/valider', operationId: 'validerAbsence', tags: ['Absences'], summary: 'Valider une absence', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Validée')])]
    public function valider(DecisionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->valider($id, $request->validated('commentaire')), 'Absence validée');
    }

    #[OA\Post(path: '/api/absences/{id}/rejeter', operationId: 'rejeterAbsence', tags: ['Absences'], summary: 'Rejeter une absence', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Rejetée')])]
    public function rejeter(RejectionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->rejeter($id, $request->validated('commentaire')), 'Absence rejetée');
    }
}
