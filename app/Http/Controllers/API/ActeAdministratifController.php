<?php

namespace App\Http\Controllers\API;

use App\Enums\TypeActeAdministratif;
use App\Http\Requests\ActeAdministratif\GenererRequest;
use App\Http\Resources\ActeAdministratifResource;
use App\Services\ActeAdministratifService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/** @property ActeAdministratifService $service */
class ActeAdministratifController extends BaseController
{
    public function __construct(ActeAdministratifService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ActeAdministratifResource::class;
    }

    #[OA\Post(
        path: '/api/integration/dossiers/{dossier}/actes',
        operationId: 'genererActeAdministratif',
        tags: ['Intégration — Actes'],
        summary: 'Générer un acte administratif (manuel)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'dossier', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type_acte'],
                properties: [
                    new OA\Property(property: 'type_acte', type: 'string', example: 'decision_recrutement'),
                    new OA\Property(property: 'contenu', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Créé', content: new OA\JsonContent(ref: '#/components/schemas/ActeAdministratifResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function generer(GenererRequest $request, int $dossierId): JsonResponse
    {
        $typeActe = TypeActeAdministratif::from($request->input('type_acte'));
        $acte     = $this->service->generer($dossierId, $typeActe, $request->input('contenu'));

        return $this->respond($acte, "Acte {$typeActe->label()} généré avec le numéro {$acte->numero}", 201);
    }

    #[OA\Post(
        path: '/api/integration/actes/{acte}/signer',
        operationId: 'signerActeAdministratif',
        tags: ['Intégration — Actes'],
        summary: 'Signer un acte administratif',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'acte', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Signé', content: new OA\JsonContent(ref: '#/components/schemas/ActeAdministratifResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function signer(int $id): JsonResponse
    {
        $acte = $this->service->signer($id);

        return $this->respond($acte, 'Acte signé');
    }

    #[OA\Get(
        path: '/api/integration/dossiers/{dossier}/actes',
        operationId: 'listActesDossier',
        tags: ['Intégration — Actes'],
        summary: 'Lister les actes d\'un dossier',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'dossier', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ActeAdministratif')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function byDossier(int $dossierId): JsonResponse
    {
        $actes = $this->service->getByDossier($dossierId);

        return response()->json(['data' => ActeAdministratifResource::collection($actes)]);
    }
}
