<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\PriseDeService\CreateRequest;
use App\Http\Resources\PriseDeServiceResource;
use App\Services\AgentService;
use App\Services\DossierIntegrationService;
use App\Services\PriseDeServiceService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PriseDeServiceController extends BaseController
{
    public function __construct(
        PriseDeServiceService $service,
        private readonly AgentService $agentService,
        private readonly DossierIntegrationService $dossierService,
    ) {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return PriseDeServiceResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'responsable'];
    }

    #[OA\Post(
        path: '/api/integration/prises-de-service',
        operationId: 'storePriseDeService',
        tags: ['Intégration — Prise de service'],
        summary: 'Confirmer une prise de service',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PriseDeServiceRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Confirmée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PriseDeService'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        $priseDeService = $this->service->create($request->validated());

        $this->agentService->mettreAJourDatePriseService(
            $request->input('agent_id'),
            $request->input('date_prise_service')
        );

        if ($request->filled('dossier_integration_id')) {
            $this->dossierService->marquerPriseDeService($request->input('dossier_integration_id'));
        }

        return $this->respond($priseDeService, 'Prise de service confirmée', 201);
    }

    #[OA\Post(
        path: '/api/integration/dossiers/{dossier}/integrer',
        operationId: 'integrerDossier',
        tags: ['Intégration — Prise de service'],
        summary: 'Finaliser l\'intégration administrative',
        description: 'Clôture le workflow : dossier INTEGRE, compte éventuel, checklist post-intégration.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'dossier', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Intégration finalisée'),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function integrer(int $dossierId): JsonResponse
    {
        $result = $this->dossierService->integrer($dossierId);

        $data = ['dossier' => new \App\Http\Resources\DossierIntegrationResource($result['dossier'])];

        if ($result['compte'] !== null) {
            $data['compte'] = [
                'login'               => $result['compte']->login,
                'email_professionnel' => $result['compte']->email_professionnel,
                'badge_numero'        => $result['compte']->badge_numero,
            ];
        }

        $restantes = collect($result['taches_post_integration'])->where('statut', 'non_fait');

        $data['taches_post_integration'] = $result['taches_post_integration'];
        $data['rappel']                  = $restantes->isEmpty()
            ? 'Toutes les tâches post-intégration sont complètes.'
            : "{$restantes->count()} tâche(s) post-intégration en attente — consultez taches_post_integration.";

        return response()->json([
            'data'    => $data,
            'message' => 'Intégration administrative finalisée avec succès',
        ]);
    }
}
