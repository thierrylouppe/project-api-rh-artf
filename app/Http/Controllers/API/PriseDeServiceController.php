<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\PriseDeService\CreateRequest;
use App\Http\Resources\PriseDeServiceResource;
use App\Services\AgentService;
use App\Services\DossierIntegrationService;
use App\Services\PriseDeServiceService;
use Illuminate\Http\JsonResponse;

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

    public function integrer(int $dossierId): JsonResponse
    {
        $dossier = $this->dossierService->integrer($dossierId);

        return response()->json([
            'data'    => new \App\Http\Resources\DossierIntegrationResource($dossier),
            'message' => 'Intégration administrative finalisée avec succès',
        ]);
    }
}
