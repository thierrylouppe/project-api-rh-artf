<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TypeIntegration\CreateRequest;
use App\Http\Requests\TypeIntegration\UpdateRequest;
use App\Http\Resources\TypeIntegrationListResource;
use App\Http\Resources\TypeIntegrationResource;
use App\Services\TypeIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TypeIntegrationController extends BaseController
{
    public function __construct(TypeIntegrationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return TypeIntegrationResource::class;
    }

    protected function listResource(): string
    {
        return TypeIntegrationListResource::class;
    }

    protected function showRelations(): array
    {
        return ['documentsObligatoires'];
    }

    #[OA\Get(
        path: '/api/types-integrations',
        operationId: 'listTypesIntegrations',
        tags: ['Référentiels — Types d\'intégration'],
        summary: 'Liste des types d\'intégration',
        description: 'Référentiel public utile pour peupler les selects du front (création de dossier).',
        responses: [
            new OA\Response(response: 200, description: 'Liste', content: new OA\JsonContent(ref: '#/components/schemas/TypeIntegrationListResponse')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(
        path: '/api/types-integrations/{id}',
        operationId: 'showTypeIntegration',
        tags: ['Référentiels — Types d\'intégration'],
        summary: 'Détail d\'un type d\'intégration',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail', content: new OA\JsonContent(ref: '#/components/schemas/TypeIntegrationResponse')),
            new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404')),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(
        path: '/api/types-integrations',
        operationId: 'storeTypeIntegration',
        tags: ['Référentiels — Types d\'intégration'],
        summary: 'Créer un type d\'intégration',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nom'],
                properties: [
                    new OA\Property(property: 'nom', type: 'string', example: 'Recrutement externe'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'type_acte_administratif', type: 'string', example: 'decision_recrutement'),
                    new OA\Property(property: 'necessite_contrat', type: 'boolean', example: true),
                    new OA\Property(property: 'necessite_validation_dg', type: 'boolean', example: true),
                    new OA\Property(property: 'necessite_compte_utilisateur', type: 'boolean', example: true),
                    new OA\Property(property: 'prefixe_matricule', type: 'string', nullable: true),
                    new OA\Property(property: 'duree_max_mois', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Créé', content: new OA\JsonContent(ref: '#/components/schemas/TypeIntegrationResponse')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Créé avec succès', 201);
    }

    #[OA\Put(
        path: '/api/types-integrations/{id}',
        operationId: 'updateTypeIntegration',
        tags: ['Référentiels — Types d\'intégration'],
        summary: 'Mettre à jour un type d\'intégration',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nom', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'type_acte_administratif', type: 'string'),
                    new OA\Property(property: 'necessite_contrat', type: 'boolean'),
                    new OA\Property(property: 'necessite_validation_dg', type: 'boolean'),
                    new OA\Property(property: 'necessite_compte_utilisateur', type: 'boolean'),
                    new OA\Property(property: 'prefixe_matricule', type: 'string', nullable: true),
                    new OA\Property(property: 'duree_max_mois', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mis à jour', content: new OA\JsonContent(ref: '#/components/schemas/TypeIntegrationResponse')),
            new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Mis à jour avec succès');
    }
}
