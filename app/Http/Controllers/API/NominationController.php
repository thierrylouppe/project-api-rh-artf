<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Nomination\ActiverRequest;
use App\Http\Requests\Nomination\CloturerRequest;
use App\Http\Requests\Nomination\CreateRequest;
use App\Http\Requests\Nomination\RejeterRequest;
use App\Http\Requests\Nomination\UpdateRequest;
use App\Http\Resources\AffectationResource;
use App\Http\Resources\AgentResource;
use App\Http\Resources\NominationResource;
use App\Services\NominationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/** @property NominationService $service */
class NominationController extends BaseController
{
    public function __construct(NominationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return NominationResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'structure', 'validations.validateur'];
    }

    #[OA\Get(path: '/api/carriere/nominations', operationId: 'listNominationsCarriere', tags: ['Carrière — Nominations'], summary: 'Liste des nominations', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste'), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    #[OA\Get(path: '/api/integration/nominations', operationId: 'listNominations', tags: ['Intégration — Nominations'], summary: 'Liste des nominations (alias)', deprecated: true, security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste'), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(path: '/api/carriere/nominations/{id}', operationId: 'showNominationCarriere', tags: ['Carrière — Nominations'], summary: 'Détail d\'une nomination', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail', content: new OA\JsonContent(ref: '#/components/schemas/NominationResponse')), new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404'))])]
    #[OA\Get(path: '/api/integration/nominations/{id}', operationId: 'showNomination', tags: ['Intégration — Nominations'], summary: 'Détail d\'une nomination (alias)', deprecated: true, security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail', content: new OA\JsonContent(ref: '#/components/schemas/NominationResponse')), new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404'))])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(
        path: '/api/carriere/nominations',
        operationId: 'storeNominationCarriere',
        tags: ['Carrière — Nominations'],
        summary: 'Créer une nomination',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['agent_id', 'poste', 'structurable_type', 'structurable_id', 'date_debut'],
                properties: [
                    new OA\Property(property: 'agent_id', type: 'integer', example: 1),
                    new OA\Property(property: 'poste', type: 'string', example: 'Chef de Service', enum: ['Directeur Général', 'Directeur Central', 'Directeur Départemental', 'Chef de Service', 'Chef de Bureau']),
                    new OA\Property(property: 'structurable_type', type: 'string', example: 'App\\Models\\Service'),
                    new OA\Property(property: 'structurable_id', type: 'integer', example: 1),
                    new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-09-01'),
                    new OA\Property(property: 'type_acte', type: 'string', nullable: true, enum: ['arrete', 'decision', 'note_service']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Créée', content: new OA\JsonContent(ref: '#/components/schemas/NominationResponse')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    #[OA\Post(path: '/api/integration/nominations', operationId: 'storeNomination', tags: ['Intégration — Nominations'], summary: 'Créer une nomination (alias)', deprecated: true, security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        $nomination = $this->service->create($request->validated());

        return $this->respond($nomination, 'Nomination créée — circuit de validation initialisé', 201);
    }

    #[OA\Put(path: '/api/carriere/nominations/{id}', operationId: 'updateNominationCarriere', tags: ['Carrière — Nominations'], summary: 'Modifier une nomination en attente', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Modifiée', content: new OA\JsonContent(ref: '#/components/schemas/NominationResponse')), new OA\Response(response: 422, description: 'Validation')])]
    #[OA\Put(path: '/api/integration/nominations/{id}', operationId: 'updateNomination', tags: ['Intégration — Nominations'], summary: 'Modifier une nomination (alias)', deprecated: true, security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Modifiée')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->update($id, $request->validated()),
            'Nomination mise à jour'
        );
    }

    #[OA\Post(path: '/api/carriere/nominations/{nomination}/activer', operationId: 'activerNominationCarriere', tags: ['Carrière — Nominations'], summary: 'Activer une nomination', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nomination', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'dossier_integration_id', type: 'integer', nullable: true, description: 'Ignoré — conservé pour compatibilité FE')])), responses: [new OA\Response(response: 200, description: 'Activée', content: new OA\JsonContent(ref: '#/components/schemas/NominationResponse'))])]
    #[OA\Post(path: '/api/integration/nominations/{nomination}/activer', operationId: 'activerNomination', tags: ['Intégration — Nominations'], summary: 'Activer une nomination (alias)', deprecated: true, security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nomination', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Activée')])]
    public function activer(ActiverRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->activer($id),
            'Nomination activée — ancienne nomination clôturée automatiquement'
        );
    }

    #[OA\Post(path: '/api/carriere/nominations/{nomination}/cloturer', operationId: 'cloturerNominationCarriere', tags: ['Carrière — Nominations'], summary: 'Clôturer une nomination', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nomination', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'date_fin', type: 'string', format: 'date')])), responses: [new OA\Response(response: 200, description: 'Clôturée', content: new OA\JsonContent(ref: '#/components/schemas/NominationResponse'))])]
    #[OA\Post(path: '/api/integration/nominations/{nomination}/cloturer', operationId: 'cloturerNomination', tags: ['Intégration — Nominations'], summary: 'Clôturer une nomination (alias)', deprecated: true, security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nomination', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Clôturée')])]
    public function cloturer(CloturerRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->cloturer($id, $request->input('date_fin')),
            'Nomination clôturée'
        );
    }

    #[OA\Post(path: '/api/carriere/nominations/{nomination}/rejeter', operationId: 'rejeterNominationCarriere', tags: ['Carrière — Nominations'], summary: 'Rejeter une nomination', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nomination', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/TransitionRequest')), responses: [new OA\Response(response: 200, description: 'Rejetée', content: new OA\JsonContent(ref: '#/components/schemas/NominationResponse'))])]
    #[OA\Post(path: '/api/integration/nominations/{nomination}/rejeter', operationId: 'rejeterNomination', tags: ['Intégration — Nominations'], summary: 'Rejeter une nomination (alias)', deprecated: true, security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nomination', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Rejetée')])]
    public function rejeter(RejeterRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->rejeter($id, $request->validated('commentaire')),
            'Nomination rejetée'
        );
    }

    #[OA\Get(path: '/api/carriere/agents/{agent}/nominations', operationId: 'listNominationsByAgentCarriere', tags: ['Carrière — Nominations'], summary: 'Nominations d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    #[OA\Get(path: '/api/integration/agents/{agent}/nominations', operationId: 'listNominationsByAgent', tags: ['Intégration — Nominations'], summary: 'Nominations d\'un agent (alias)', deprecated: true, security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agentId): JsonResponse
    {
        $nominations = $this->service->getByAgent($agentId);

        return response()->json(['data' => NominationResource::collection($nominations)]);
    }

    #[OA\Get(path: '/api/carriere/agents/{agent}/nominations/historique', operationId: 'historiqueNominationsByAgentCarriere', tags: ['Carrière — Nominations'], summary: 'Historique des nominations (hors active)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    #[OA\Get(path: '/api/integration/agents/{agent}/nominations/historique', operationId: 'historiqueNominationsByAgent', tags: ['Intégration — Nominations'], summary: 'Historique des nominations (alias)', deprecated: true, security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function historique(int $agentId): JsonResponse
    {
        return response()->json([
            'data' => NominationResource::collection($this->service->getHistoriqueByAgent($agentId)),
        ]);
    }

    #[OA\Get(path: '/api/carriere/nominations/postes-vacants', operationId: 'postesVacantsNominationsCarriere', tags: ['Carrière — Nominations'], summary: 'Structures sans responsable actif', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    #[OA\Get(path: '/api/integration/nominations/postes-vacants', operationId: 'postesVacantsNominations', tags: ['Intégration — Nominations'], summary: 'Postes vacants (alias)', deprecated: true, security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function postesVacants(): JsonResponse
    {
        return response()->json(['data' => $this->service->postesVacants()]);
    }

    #[OA\Get(path: '/api/carriere/nominations/chefs/{chef}/agents-sous-autorite', operationId: 'agentsSousAutoriteCarriere', tags: ['Carrière — Nominations'], summary: 'Agents sous l\'autorité d\'un chef', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'chef', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    #[OA\Get(path: '/api/integration/nominations/chefs/{chef}/agents-sous-autorite', operationId: 'agentsSousAutorite', tags: ['Intégration — Nominations'], summary: 'Agents sous autorité (alias)', deprecated: true, security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'chef', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function agentsSousAutorite(int $chefId): JsonResponse
    {
        $result = $this->service->agentsSousAutorite($chefId);

        return response()->json([
            'data' => [
                'chef'              => new AgentResource($result['chef']),
                'nomination_active' => $result['nomination_active']
                    ? new NominationResource($result['nomination_active'])
                    : null,
                'agents'            => $result['affectations']->map(fn ($affectation) => [
                    'agent'       => new AgentResource($affectation->agent),
                    'affectation' => new AffectationResource($affectation),
                ]),
            ],
        ]);
    }

    #[OA\Get(path: '/api/carriere/nominations/{nomination}/acte', operationId: 'acteNominationCarriere', tags: ['Carrière — Nominations'], summary: 'Télécharger l\'acte de nomination (PDF)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nomination', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Fichier PDF')])]
    #[OA\Get(path: '/api/integration/nominations/{nomination}/acte', operationId: 'acteNomination', tags: ['Intégration — Nominations'], summary: 'Télécharger l\'acte (alias)', deprecated: true, security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'nomination', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Fichier PDF')])]
    public function acte(int $id): SymfonyResponse
    {
        $path     = $this->service->genererActePdf($id);
        $fileName = $this->service->nomFichierActe($id);

        return response()->download(Storage::disk('local')->path($path), $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
