<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Nomination\CreateRequest;
use App\Http\Resources\NominationResource;
use App\Services\DossierIntegrationService;
use App\Services\NominationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NominationController extends BaseController
{
    public function __construct(
        NominationService $service,
        private readonly DossierIntegrationService $dossierService,
    ) {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return NominationResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'validations.validateur'];
    }

    public function store(CreateRequest $request): JsonResponse
    {
        $nomination = $this->service->create($request->validated());

        return $this->respond($nomination, 'Nomination créée — circuit de validation initialisé', 201);
    }

    public function activer(Request $request, int $id): JsonResponse
    {
        $nomination = $this->service->activer($id);

        if ($request->filled('dossier_integration_id')) {
            $this->dossierService->marquerNomme($request->input('dossier_integration_id'));
        }

        return $this->respond($nomination, 'Nomination activée — ancienne nomination clôturée automatiquement');
    }

    public function cloturer(Request $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->cloturer($id, $request->input('date_fin')),
            'Nomination clôturée'
        );
    }

    public function rejeter(Request $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->rejeter($id, $request->input('commentaire', '')),
            'Nomination rejetée'
        );
    }

    public function byAgent(int $agentId): JsonResponse
    {
        $nominations = $this->service->getByAgent($agentId);

        return response()->json(['data' => NominationResource::collection($nominations)]);
    }
}
