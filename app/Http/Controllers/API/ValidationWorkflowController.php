<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ValidationWorkflow\DecisionRequest;
use App\Http\Requests\ValidationWorkflow\RejectionRequest;
use App\Http\Resources\ValidationWorkflowResource;
use App\Models\Affectation;
use App\Models\DossierIntegration;
use App\Services\AffectationService;
use App\Services\DossierIntegrationService;
use App\Services\ValidationWorkflowService;
use Illuminate\Http\JsonResponse;

/** @property ValidationWorkflowService $service */
class ValidationWorkflowController extends BaseController
{
    public function __construct(
        ValidationWorkflowService $service,
        private readonly DossierIntegrationService $dossierService,
        private readonly AffectationService $affectationService,
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

        if (
            $validation->validable_type === DossierIntegration::class
            && $this->service->circuitTermine(DossierIntegration::class, $validation->validable_id)
        ) {
            $this->dossierService->validerDG($validation->validable_id);
        }

        if (
            $validation->validable_type === Affectation::class
            && $this->service->circuitTermine(Affectation::class, $validation->validable_id)
        ) {
            $this->affectationService->approuver($validation->validable_id);
        }

        return $this->respond($validation, 'Validation approuvée');
    }

    public function rejeter(RejectionRequest $request, int $id): JsonResponse
    {
        $validation = $this->service->rejeter($id, $request->input('commentaire'));

        if ($validation->validable_type === DossierIntegration::class) {
            $this->dossierService->rejeterRH($validation->validable_id, $request->input('commentaire'));
        }

        if ($validation->validable_type === Affectation::class) {
            $this->affectationService->rejeter($validation->validable_id, $request->input('commentaire', ''));
        }

        return $this->respond($validation, 'Validation rejetée');
    }

    public function renvoyer(RejectionRequest $request, int $id): JsonResponse
    {
        $validation = $this->service->renvoyer($id, $request->input('commentaire'));

        if ($validation->validable_type === DossierIntegration::class) {
            $this->dossierService->marquerIncomplet($validation->validable_id, $request->input('commentaire'));
        }

        return $this->respond($validation, 'Dossier renvoyé pour correction');
    }

    public function circuit(int $dossierId): JsonResponse
    {
        $circuit = $this->service->getCircuit(DossierIntegration::class, $dossierId);

        return response()->json(['data' => ValidationWorkflowResource::collection($circuit)]);
    }
}
