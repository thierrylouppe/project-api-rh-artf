<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Nomination\ActiverRequest;
use App\Http\Requests\Nomination\CloturerRequest;
use App\Http\Requests\Nomination\CreateRequest;
use App\Http\Requests\Nomination\RejeterRequest;
use App\Http\Resources\NominationResource;
use App\Services\NominationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

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
}
