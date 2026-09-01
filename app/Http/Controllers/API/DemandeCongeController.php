<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\DemandeConge\CreateRequest;
use App\Http\Requests\DemandeConge\DecisionRequest;
use App\Http\Requests\DemandeConge\RejectionRequest;
use App\Http\Resources\DemandeCongeResource;
use App\Services\DemandeCongeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class DemandeCongeController extends BaseController
{
    public function __construct(DemandeCongeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return DemandeCongeResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'typeConge'];
    }

    #[OA\Get(path: '/api/conges/demandes', operationId: 'listDemandesConges', tags: ['Congés'], summary: 'Liste des demandes', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request->query());
        $items->load(['agent', 'typeConge']);

        return $this->collectionResponse($this->listResource()::collection($items));
    }

    #[OA\Get(path: '/api/conges/demandes/{id}', operationId: 'showDemandeConge', tags: ['Congés'], summary: 'Détail d\'une demande', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(path: '/api/conges/demandes', operationId: 'storeDemandeConge', tags: ['Congés'], summary: 'Soumettre une demande', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        $demande = $this->service->create($request->validated());

        return $this->respond($demande, 'Demande de congé soumise', 201);
    }

    #[OA\Get(path: '/api/conges/agents/{agent}/demandes', operationId: 'demandesCongesParAgent', tags: ['Congés'], summary: 'Demandes d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        $items = $this->service->getByAgent($agent);

        return response()->json([
            'data'    => DemandeCongeResource::collection($items),
            'message' => 'Demandes récupérées',
        ]);
    }

    #[OA\Post(path: '/api/conges/demandes/{id}/valider-n1', operationId: 'validerDemandeCongeN1', tags: ['Congés'], summary: 'Valider N+1', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Validée')])]
    public function validerN1(DecisionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->validerN1($id, $request->validated('commentaire')), 'Demande validée N+1');
    }

    #[OA\Post(path: '/api/conges/demandes/{id}/rejeter-n1', operationId: 'rejeterDemandeCongeN1', tags: ['Congés'], summary: 'Rejeter N+1', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Rejetée')])]
    public function rejeterN1(RejectionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->rejeterN1($id, $request->validated('commentaire')), 'Demande rejetée N+1');
    }

    #[OA\Post(path: '/api/conges/demandes/{id}/valider-rh', operationId: 'validerDemandeCongeRh', tags: ['Congés'], summary: 'Valider RH', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Validée')])]
    public function validerRH(DecisionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->validerRH($id, $request->validated('commentaire')), 'Demande validée RH');
    }

    #[OA\Post(path: '/api/conges/demandes/{id}/rejeter-rh', operationId: 'rejeterDemandeCongeRh', tags: ['Congés'], summary: 'Rejeter RH', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Rejetée')])]
    public function rejeterRH(RejectionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->rejeterRH($id, $request->validated('commentaire')), 'Demande rejetée RH');
    }

    #[OA\Post(path: '/api/conges/demandes/{id}/valider-dg', operationId: 'validerDemandeCongeDg', tags: ['Congés'], summary: 'Valider DG', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Validée')])]
    public function validerDG(DecisionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->validerDG($id, $request->validated('commentaire')), 'Demande validée DG');
    }

    #[OA\Post(path: '/api/conges/demandes/{id}/rejeter-dg', operationId: 'rejeterDemandeCongeDg', tags: ['Congés'], summary: 'Rejeter DG', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Rejetée')])]
    public function rejeterDG(RejectionRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->rejeterDG($id, $request->validated('commentaire')), 'Demande rejetée DG');
    }

    #[OA\Get(path: '/api/conges/statistiques', operationId: 'statistiquesConges', tags: ['Congés'], summary: 'Statistiques', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Stats')])]
    public function statistiques(Request $request): JsonResponse
    {
        return response()->json([
            'data'    => $this->service->statistiques($request->query()),
            'message' => 'Statistiques congés',
        ]);
    }

    #[OA\Get(path: '/api/conges/demandes/{id}/fiche-pdf', operationId: 'ficheCongePdf', tags: ['Congés'], summary: 'Fiche PDF', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function fichePdf(int $id): Response
    {
        return $this->service->fichePdf($id);
    }

    #[OA\Get(path: '/api/conges/demandes/{id}/attestation', operationId: 'attestationCongePdf', tags: ['Congés'], summary: 'Attestation PDF (après validation RH)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function attestation(int $id): Response
    {
        return $this->service->attestationPdf($id);
    }
}
