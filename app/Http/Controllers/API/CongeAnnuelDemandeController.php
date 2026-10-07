<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CongeAnnuel\CreateRequest;
use App\Http\Requests\DemandeConge\DecisionRequest;
use App\Http\Requests\DemandeConge\RejectionRequest;
use App\Http\Resources\CongeSoldeResource;
use App\Http\Resources\DemandeCongeResource;
use App\Services\CongeAnnuelDemandeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class CongeAnnuelDemandeController extends BaseController
{
    public function __construct(CongeAnnuelDemandeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return DemandeCongeResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'typeConge', 'campagne'];
    }

    #[OA\Get(path: '/api/conges-annuels/agents/{agent}/solde', operationId: 'soldeCongeAnnuel', tags: ['Congé annuel'], summary: 'Solde de congé annuel', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Solde')])]
    public function solde(Request $request, int $agent): JsonResponse
    {
        $annee = $request->query('annee') !== null ? (int) $request->query('annee') : null;

        return $this->successResponse(
            new CongeSoldeResource($this->service->solde($agent, $annee)),
            'Solde de congé annuel'
        );
    }

    #[OA\Get(path: '/api/conges-annuels/statistiques', operationId: 'statsCongeAnnuel', tags: ['Congé annuel'], summary: 'Statistiques du congé annuel', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Stats')])]
    public function statistiques(Request $request): JsonResponse
    {
        return $this->successResponse($this->service->statistiques($request->query()), 'Statistiques congé annuel');
    }

    #[OA\Get(path: '/api/conges-annuels/demandes/a-valider', operationId: 'congeAnnuelAValider', tags: ['Congé annuel'], summary: 'File à traiter', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function aValider(): JsonResponse
    {
        return $this->collectionResponse(
            DemandeCongeResource::collection($this->service->aValider()),
            'Demandes à valider'
        );
    }

    #[OA\Get(path: '/api/conges-annuels/demandes', operationId: 'listCongesAnnuels', tags: ['Congé annuel'], summary: 'Propositions de congé annuel', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/conges-annuels/demandes', operationId: 'storeCongeAnnuel', tags: ['Congé annuel'], summary: 'Proposer un congé annuel', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->deposer($request->validated()), 'Proposition de congé annuel enregistrée', 201);
    }

    #[OA\Get(path: '/api/conges-annuels/demandes/{id}', operationId: 'showCongeAnnuel', tags: ['Congé annuel'], summary: 'Détail', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        $id = (int) collect($request->route()->parameters())->first();

        return $this->respond($this->service->consulter($id));
    }

    #[OA\Post(path: '/api/conges-annuels/demandes/{id}/valider-n1', operationId: 'validerCongeAnnuelN1', tags: ['Congé annuel'], summary: 'Visa N+1', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Visée')])]
    public function validerN1(DecisionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->validerN1($id, $request->validated('commentaire')), 'Proposition visée par le N+1');
    }

    #[OA\Post(path: '/api/conges-annuels/demandes/{id}/rejeter-n1', operationId: 'rejeterCongeAnnuelN1', tags: ['Congé annuel'], summary: 'Rejet N+1', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Rejetée')])]
    public function rejeterN1(RejectionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->rejeterN1($id, $request->validated('commentaire')), 'Proposition rejetée par le N+1');
    }

    #[OA\Post(path: '/api/conges-annuels/demandes/{id}/valider-rh', operationId: 'validerCongeAnnuelRh', tags: ['Congé annuel'], summary: 'Attribution RH', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Attribuée')])]
    public function validerRH(DecisionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->validerRH($id, $request->validated('commentaire')), 'Congé annuel attribué');
    }

    #[OA\Post(path: '/api/conges-annuels/demandes/{id}/rejeter-rh', operationId: 'rejeterCongeAnnuelRh', tags: ['Congé annuel'], summary: 'Rejet RH', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Rejetée')])]
    public function rejeterRH(RejectionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->rejeterRH($id, $request->validated('commentaire')), 'Proposition rejetée par les RH');
    }

    #[OA\Post(path: '/api/conges-annuels/demandes/{id}/annuler', operationId: 'annulerCongeAnnuel', tags: ['Congé annuel'], summary: 'Annuler une proposition', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Annulée')])]
    public function annuler(int $id): JsonResponse
    {
        return $this->respond($this->service->annuler($id), 'Proposition annulée');
    }

    #[OA\Get(path: '/api/conges-annuels/demandes/{id}/fiche-pdf', operationId: 'ficheCongeAnnuel', tags: ['Congé annuel'], summary: 'Fiche PDF', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function fichePdf(int $id): Response
    {
        return $this->service->fichePdf($id);
    }

    #[OA\Get(path: '/api/conges-annuels/demandes/{id}/attestation', operationId: 'attestationCongeAnnuel', tags: ['Congé annuel'], summary: 'Attestation PDF', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function attestation(int $id): Response
    {
        return $this->service->attestationPdf($id);
    }
}
