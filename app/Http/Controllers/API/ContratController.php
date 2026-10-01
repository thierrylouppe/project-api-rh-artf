<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Contrat\CreateRequest;
use App\Http\Requests\Contrat\RompreEssaiRequest;
use App\Http\Resources\AlerteDelaiContratResource;
use App\Http\Resources\ContratResource;
use App\Services\ContratService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ContratController extends BaseController
{
    public function __construct(private readonly ContratService $contratService)
    {
        parent::__construct($contratService);
    }

    protected function resource(): string
    {
        return ContratResource::class;
    }

    protected function showRelations(): array
    {
        return [
            'agent.grade',
            'agent.categorie',
            'agent.echelon',
            'agent.fonction',
            'agent.situationFamiliale',
            'agent.affectationActive.structure',
            'typeContrat',
            'fonction',
        ];
    }

    #[OA\Get(
        path: '/api/carriere/contrats',
        operationId: 'listContrats',
        tags: ['Carrière — Contrats'],
        summary: 'Liste des contrats',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste'),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(
        path: '/api/carriere/contrats/{id}',
        operationId: 'showContrat',
        tags: ['Carrière — Contrats'],
        summary: 'Détail d\'un contrat (essai art. 49, mentions art. 52)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail', content: new OA\JsonContent(ref: '#/components/schemas/ContratResponse')),
            new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404')),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(
        path: '/api/carriere/contrats',
        operationId: 'storeContrat',
        tags: ['Carrière — Contrats'],
        summary: 'Créer un contrat',
        description: 'CDI/CDD : période d\'essai automatique art. 49 (1/2/3 mois selon la classe) et salaire au minimum de la classe. Alias `/api/integration/contrats`.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ContratRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Créé', content: new OA\JsonContent(ref: '#/components/schemas/ContratResponse')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        $contrat = $this->contratService->create($request->validated());

        return $this->respond($contrat, 'Contrat créé avec succès', 201);
    }

    #[OA\Get(
        path: '/api/carriere/contrats/alertes/delai-30-jours',
        operationId: 'alertesDelaiContrat30Jours',
        tags: ['Carrière — Contrats'],
        summary: 'Dossiers intégrés sans CDI/CDD plus de 30 jours ouvrables après la PDS (art. 52)',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste'),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 403, description: 'Permission manquante', content: new OA\JsonContent(ref: '#/components/schemas/Error403')),
        ]
    )]
    public function alertesDelai30Jours(): JsonResponse
    {
        return $this->collectionResponse(
            AlerteDelaiContratResource::collection($this->contratService->alertesDelai30Jours()),
            'Alertes délai contrat 30 jours ouvrables'
        );
    }

    #[OA\Post(
        path: '/api/carriere/contrats/{contrat}/renouveler-essai',
        operationId: 'renouvelerEssaiContrat',
        tags: ['Carrière — Contrats'],
        summary: 'Renouveler la période d\'essai une fois (art. 49)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'contrat', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Renouvelé', content: new OA\JsonContent(ref: '#/components/schemas/ContratResponse')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function renouvelerEssai(int $id): JsonResponse
    {
        return $this->respond(
            $this->contratService->renouvelerEssai($id),
            'Période d\'essai renouvelée'
        );
    }

    #[OA\Post(
        path: '/api/carriere/contrats/{contrat}/confirmer-essai',
        operationId: 'confirmerEssaiContrat',
        tags: ['Carrière — Contrats'],
        summary: 'Confirmer l\'essai concluant — engagement définitif (art. 49)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'contrat', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Confirmé', content: new OA\JsonContent(ref: '#/components/schemas/ContratResponse')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function confirmerEssai(int $id): JsonResponse
    {
        return $this->respond(
            $this->contratService->confirmerEssai($id),
            'Période d\'essai concluante — engagement définitif'
        );
    }

    #[OA\Post(
        path: '/api/carriere/contrats/{contrat}/rompre-essai',
        operationId: 'rompreEssaiContrat',
        tags: ['Carrière — Contrats'],
        summary: 'Rompre le contrat pendant l\'essai, sans préavis ni indemnité (art. 49)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'contrat', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'commentaire', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Rompu', content: new OA\JsonContent(ref: '#/components/schemas/ContratResponse')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function rompreEssai(RompreEssaiRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->contratService->rompreEssai($id, $request->validated()),
            'Contrat rompu pendant la période d\'essai (sans préavis ni indemnité)'
        );
    }

    #[OA\Get(
        path: '/api/carriere/agents/{agent}/contrats',
        operationId: 'listContratsByAgent',
        tags: ['Carrière — Contrats'],
        summary: 'Contrats d\'un agent',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste'),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function byAgent(int $agentId): JsonResponse
    {
        $contrats = $this->contratService->getByAgent($agentId);

        return response()->json(['data' => ContratResource::collection($contrats)]);
    }

    #[OA\Post(
        path: '/api/carriere/contrats/{contrat}/resilier',
        operationId: 'resilierContrat',
        tags: ['Carrière — Contrats'],
        summary: 'Résilier un contrat',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'contrat', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Résilié', content: new OA\JsonContent(ref: '#/components/schemas/ContratResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function resilier(int $id): JsonResponse
    {
        return $this->respond($this->contratService->resilier($id), 'Contrat résilié');
    }
}
