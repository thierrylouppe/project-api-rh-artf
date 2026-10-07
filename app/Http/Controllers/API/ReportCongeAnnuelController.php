<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ReportCongeAnnuel\CreateRequest;
use App\Http\Requests\ReportCongeAnnuel\RefusRequest;
use App\Http\Resources\ReportCongeAnnuelResource;
use App\Services\ReportCongeAnnuelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReportCongeAnnuelController extends BaseController
{
    public function __construct(ReportCongeAnnuelService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ReportCongeAnnuelResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent'];
    }

    #[OA\Get(path: '/api/conges-annuels/reports', operationId: 'listReportsCongeAnnuel', tags: ['Congé annuel'], summary: 'Reports pour nécessité de service', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/conges-annuels/reports', operationId: 'storeReportCongeAnnuel', tags: ['Congé annuel'], summary: 'Proposer un report', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Proposé')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->proposer($request->validated()), 'Report proposé', 201);
    }

    #[OA\Post(path: '/api/conges-annuels/reports/{id}/accorder', operationId: 'accorderReportCongeAnnuel', tags: ['Congé annuel'], summary: 'Accorder un report', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Accordé')])]
    public function accorder(int $id): JsonResponse
    {
        return $this->respond($this->service->accorder($id), 'Report accordé');
    }

    #[OA\Post(path: '/api/conges-annuels/reports/{id}/refuser', operationId: 'refuserReportCongeAnnuel', tags: ['Congé annuel'], summary: 'Refuser un report', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Refusé')])]
    public function refuser(RefusRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->refuser($id, $request->validated('commentaire')), 'Report refusé');
    }
}
