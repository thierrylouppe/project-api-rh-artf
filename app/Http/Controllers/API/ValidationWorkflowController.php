<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ValidationWorkflow\DecisionRequest;
use App\Http\Requests\ValidationWorkflow\RejectionRequest;
use App\Http\Resources\ValidationWorkflowResource;
use App\Models\Affectation;
use App\Models\DossierIntegration;
use App\Models\LotAffectation;
use App\Models\LotNomination;
use App\Models\Nomination;
use App\Services\AffectationService;
use App\Services\DossierIntegrationService;
use App\Services\LotAffectationService;
use App\Services\LotNominationService;
use App\Services\NominationService;
use App\Services\ValidationWorkflowService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/** @property ValidationWorkflowService $service */
class ValidationWorkflowController extends BaseController
{
    public function __construct(
        ValidationWorkflowService $service,
        private readonly DossierIntegrationService $dossierService,
        private readonly AffectationService $affectationService,
        private readonly LotAffectationService $lotAffectationService,
        private readonly NominationService $nominationService,
        private readonly LotNominationService $lotNominationService,
    ) {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ValidationWorkflowResource::class;
    }

    #[OA\Post(
        path: '/api/integration/validations/{validation}/approuver',
        operationId: 'approuverValidation',
        tags: ['Intégration — Validations'],
        summary: 'Approuver une étape du circuit',
        description: 'Si le circuit dossier est terminé, déclenche automatiquement `validerDG`.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'validation', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/DecisionRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Approuvée', content: new OA\JsonContent(ref: '#/components/schemas/ValidationWorkflowResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
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

        if (
            $validation->validable_type === LotAffectation::class
            && $this->service->circuitTermine(LotAffectation::class, $validation->validable_id)
        ) {
            $this->lotAffectationService->approuver($validation->validable_id);
        }

        if (
            $validation->validable_type === Nomination::class
            && $this->service->circuitTermine(Nomination::class, $validation->validable_id)
        ) {
            $this->nominationService->approuver($validation->validable_id);
        }

        if (
            $validation->validable_type === LotNomination::class
            && $this->service->circuitTermine(LotNomination::class, $validation->validable_id)
        ) {
            $this->lotNominationService->approuver($validation->validable_id);
        }

        return $this->respond($validation, 'Validation approuvée');
    }

    #[OA\Post(
        path: '/api/integration/validations/{validation}/rejeter',
        operationId: 'rejeterValidation',
        tags: ['Intégration — Validations'],
        summary: 'Rejeter une étape du circuit',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'validation', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TransitionRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Rejetée', content: new OA\JsonContent(ref: '#/components/schemas/ValidationWorkflowResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function rejeter(RejectionRequest $request, int $id): JsonResponse
    {
        $validation = $this->service->rejeter($id, $request->input('commentaire'));

        if ($validation->validable_type === DossierIntegration::class) {
            $this->dossierService->rejeterRH($validation->validable_id, $request->input('commentaire'));
        }

        if ($validation->validable_type === Affectation::class) {
            $this->affectationService->rejeter($validation->validable_id, $request->input('commentaire', ''));
        }

        if ($validation->validable_type === LotAffectation::class) {
            $this->lotAffectationService->rejeter($validation->validable_id, $request->input('commentaire', ''));
        }

        if ($validation->validable_type === Nomination::class) {
            $this->nominationService->rejeter($validation->validable_id, $request->input('commentaire', ''));
        }

        if ($validation->validable_type === LotNomination::class) {
            $this->lotNominationService->rejeter($validation->validable_id, $request->input('commentaire', ''));
        }

        return $this->respond($validation, 'Validation rejetée');
    }

    #[OA\Post(
        path: '/api/integration/validations/{validation}/renvoyer',
        operationId: 'renvoyerValidation',
        tags: ['Intégration — Validations'],
        summary: 'Renvoyer pour correction',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'validation', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TransitionRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Renvoyé', content: new OA\JsonContent(ref: '#/components/schemas/ValidationWorkflowResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function renvoyer(RejectionRequest $request, int $id): JsonResponse
    {
        $validation = $this->service->renvoyer($id, $request->input('commentaire'));

        if ($validation->validable_type === DossierIntegration::class) {
            $this->dossierService->marquerIncomplet($validation->validable_id, $request->input('commentaire'));
        }

        return $this->respond($validation, 'Dossier renvoyé pour correction');
    }

    #[OA\Get(
        path: '/api/integration/dossiers/{dossier}/circuit',
        operationId: 'circuitValidationDossier',
        tags: ['Intégration — Validations'],
        summary: 'Circuit de validation d\'un dossier',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'dossier', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Circuit', content: new OA\JsonContent(ref: '#/components/schemas/ValidationCircuitResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function circuit(int $dossierId): JsonResponse
    {
        $circuit = $this->service->getCircuit(DossierIntegration::class, $dossierId);

        return response()->json(['data' => ValidationWorkflowResource::collection($circuit)]);
    }
}
