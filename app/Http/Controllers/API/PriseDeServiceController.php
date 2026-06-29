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
