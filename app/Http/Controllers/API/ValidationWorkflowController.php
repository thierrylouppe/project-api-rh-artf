<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ValidationWorkflow\DecisionRequest;
use App\Http\Requests\ValidationWorkflow\RejectionRequest;
use App\Http\Resources\ValidationWorkflowResource;
use App\Models\DossierIntegration;
use App\Services\DossierIntegrationService;
use App\Services\ValidationWorkflowService;
use Illuminate\Http\JsonResponse;

/** @property ValidationWorkflowService $service */
class ValidationWorkflowController extends BaseController
{
    public function __construct(
        ValidationWorkflowService $service,
        private readonly DossierIntegrationService $dossierService,
    ) {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ValidationWorkflowResource::class;
    }

    public function approuver(DecisionRequest $request, int $id): JsonResponse
    {
        $validation = $this->service->approuver($id, $request->input('commentaire'));

        if ($this->service->circuitTermine(DossierIntegration::class, $validation->validable_id)) {
            $this->dossierService->validerDG($validation->validable_id);
        }

        return $this->respond($validation, 'Validation approuvée');
    }

    public function rejeter(RejectionRequest $request, int $id): JsonResponse
    {
        $validation = $this->service->rejeter($id, $request->input('commentaire'));

        $this->dossierService->rejeterRH($validation->validable_id, $request->input('commentaire'));

        return $this->respond($validation, 'Validation rejetée');
    }

    public function renvoyer(RejectionRequest $request, int $id): JsonResponse
    {
        $validation = $this->service->renvoyer($id, $request->input('commentaire'));

        $this->dossierService->marquerIncomplet($validation->validable_id, $request->input('commentaire'));

        return $this->respond($validation, 'Dossier renvoyé pour correction');
    }

    public function circuit(int $dossierId): JsonResponse
    {
        $circuit = $this->service->getCircuit(DossierIntegration::class, $dossierId);

        return response()->json(['data' => ValidationWorkflowResource::collection($circuit)]);
    }
}
