<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Affectation\CreateRequest;
use App\Http\Resources\AffectationResource;
use App\Services\AffectationService;
use App\Services\DossierIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffectationController extends BaseController
{
    public function __construct(
        AffectationService $service,
        private readonly DossierIntegrationService $dossierService,
    ) {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AffectationResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'superieurHierarchique', 'validations.validateur'];
    }

    public function store(CreateRequest $request): JsonResponse
    {
        $affectation = $this->service->create($request->validated());

        return $this->respond($affectation, 'Affectation créée — circuit de validation initialisé', 201);
    }

    public function activer(Request $request, int $id): JsonResponse
    {
        $affectation = $this->service->activer($id);

        if ($request->filled('dossier_integration_id')) {
            $this->dossierService->marquerAffecte($request->input('dossier_integration_id'));
        }

        return $this->respond($affectation, 'Affectation activée');
    }

    public function rejeter(Request $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->rejeter($id, $request->input('commentaire', '')),
            'Affectation rejetée'
        );
    }

    public function terminer(Request $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->terminer($id, $request->input('date_fin')),
            'Affectation terminée'
        );
    }

    public function byAgent(int $agentId): JsonResponse
    {
        $affectations = $this->service->getByAgent($agentId);

        return response()->json(['data' => AffectationResource::collection($affectations)]);
    }
}
