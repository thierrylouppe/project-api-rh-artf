<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\ExportRequest;
use App\Http\Requests\Reporting\FilterRequest;
use App\Http\Requests\Reporting\RepartitionRequest;
use App\Http\Resources\ReportingEffectifResource;
use App\Services\ReportingExportService;
use App\Services\ReportingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class ReportingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ReportingService $reporting,
        private readonly ReportingExportService $exportService,
    ) {}

    #[OA\Get(
        path: '/api/reporting/dashboard',
        operationId: 'reportingDashboard',
        tags: ['Reporting'],
        summary: 'Tableau de bord RH (effectifs, répartitions, masse salariale)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'annee', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'direction_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'service_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'bureau_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Dashboard')]
    )]
    public function dashboard(FilterRequest $request): JsonResponse
    {
        return $this->successResponse($this->reporting->dashboard($request->validated()), 'Tableau de bord');
    }

    #[OA\Get(
        path: '/api/reporting/effectifs',
        operationId: 'reportingEffectifs',
        tags: ['Reporting'],
        summary: 'Liste paginée des effectifs',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Liste paginée')]
    )]
    public function effectifs(FilterRequest $request): JsonResponse
    {
        $page = $this->reporting->effectifs($request->validated());
        $lignes = collect($page->items())->map(fn ($agent) => $this->reporting->ligneEffectif($agent));

        return response()->json([
            'success' => true,
            'message' => 'Effectifs',
            'data' => ReportingEffectifResource::collection($lignes),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reporting/repartitions',
        operationId: 'reportingRepartitions',
        tags: ['Reporting'],
        summary: 'Répartition selon un axe (direction, grade, genre, âge, …)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'axe', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Répartition'),
            new OA\Response(response: 422, description: 'Axe invalide'),
        ]
    )]
    public function repartitions(RepartitionRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $axe = $filters['axe'];
        unset($filters['axe']);

        return $this->successResponse($this->reporting->repartition($axe, $filters), 'Répartition');
    }

    #[OA\Get(
        path: '/api/reporting/stats/conges',
        operationId: 'reportingStatsConges',
        tags: ['Reporting'],
        summary: 'Statistiques congés et absences de l\'année',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'annee', in: 'query', schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Stats')]
    )]
    public function statsConges(FilterRequest $request): JsonResponse
    {
        return $this->successResponse($this->reporting->statsConges($request->validated()), 'Statistiques congés');
    }

    #[OA\Get(
        path: '/api/reporting/stats/evaluations',
        operationId: 'reportingStatsEvaluations',
        tags: ['Reporting'],
        summary: 'Statistiques évaluations (année + session courante)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'annee', in: 'query', schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Stats')]
    )]
    public function statsEvaluations(FilterRequest $request): JsonResponse
    {
        return $this->successResponse($this->reporting->statsEvaluations($request->validated()), 'Statistiques évaluations');
    }

    #[OA\Get(
        path: '/api/reporting/alertes',
        operationId: 'reportingAlertes',
        tags: ['Reporting'],
        summary: 'Alertes de conformité RH',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Alertes')]
    )]
    public function alertes(): JsonResponse
    {
        return $this->successResponse($this->reporting->alertes(), 'Alertes de conformité');
    }

    #[OA\Get(
        path: '/api/reporting/exports/{type}',
        operationId: 'reportingExports',
        tags: ['Reporting'],
        summary: 'Export PDF ou CSV (effectifs, conges, evaluations)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'format', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['csv', 'pdf'])),
            new OA\Parameter(name: 'annee', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'portee', in: 'query', schema: new OA\Schema(type: 'string', enum: ['annee', 'session'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Fichier'),
            new OA\Response(response: 422, description: 'Type ou format invalide'),
        ]
    )]
    public function export(ExportRequest $request): Response
    {
        return $this->exportService->exporter($request->validated('type'), $request->validated());
    }
}
