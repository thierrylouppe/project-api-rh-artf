<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\DocumentDossier\CreateRequest;
use App\Http\Resources\DocumentDossierResource;
use App\Http\Resources\TypeDocumentResource;
use App\Services\DocumentDossierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DocumentDossierController extends BaseController
{
    public function __construct(DocumentDossierService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return DocumentDossierResource::class;
    }

    #[OA\Post(
        path: '/api/integration/dossiers/{dossier}/documents',
        operationId: 'storeDocumentDossier',
        tags: ['Intégration — Documents'],
        summary: 'Déposer un document sur un dossier',
        description: 'Multipart/form-data : `type_document_id`, `fichier` (pdf/jpg/png, max 10 Mo), `est_obligatoire` optionnel.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'dossier', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['type_document_id', 'fichier'],
                    properties: [
                        new OA\Property(property: 'type_document_id', type: 'integer', example: 1),
                        new OA\Property(property: 'fichier', type: 'string', format: 'binary'),
                        new OA\Property(property: 'est_obligatoire', type: 'boolean', example: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Document ajouté', content: new OA\JsonContent(ref: '#/components/schemas/DocumentDossierResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function store(CreateRequest $request, int $dossierId): JsonResponse
    {
        $fichier       = $request->file('fichier');
        $cheminFichier = $fichier->store("dossiers/{$dossierId}/documents", 'local');

        $document = $this->service->create([
            'dossier_integration_id' => $dossierId,
            'type_document_id'       => $request->input('type_document_id'),
            'nom_original'           => $fichier->getClientOriginalName(),
            'chemin_fichier'         => $cheminFichier,
            'est_obligatoire'        => $request->boolean('est_obligatoire', false),
        ]);

        return $this->respond($document, 'Document ajouté au dossier', 201);
    }

    #[OA\Get(
        path: '/api/integration/dossiers/{dossier}/documents',
        operationId: 'listDocumentsDossier',
        tags: ['Intégration — Documents'],
        summary: 'État des documents d\'un dossier',
        description: 'Retourne les documents déposés, les manquants et un résumé.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'dossier', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'État', content: new OA\JsonContent(ref: '#/components/schemas/DocumentsEtatResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function parDossier(int $dossierId): JsonResponse
    {
        $etat = $this->service->getEtatDocuments($dossierId);

        return response()->json([
            'data' => [
                'deposes'   => DocumentDossierResource::collection($etat['deposes']),
                'manquants' => collect($etat['manquants'])->map(fn (array $item) => [
                    'type_document'   => new TypeDocumentResource($item['type_document']),
                    'est_obligatoire' => $item['est_obligatoire'],
                ]),
                'resume' => $etat['resume'],
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/integration/documents/{document}/valider',
        operationId: 'validerDocumentDossier',
        tags: ['Intégration — Documents'],
        summary: 'Valider un document déposé',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'document', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/DecisionRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Validé', content: new OA\JsonContent(ref: '#/components/schemas/DocumentDossierResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function valider(Request $request, int $id): JsonResponse
    {
        $document = $this->service->valider($id, $request->input('commentaire'));

        return $this->respond($document, 'Document validé');
    }

    #[OA\Delete(
        path: '/api/integration/documents/{document}',
        operationId: 'deleteDocumentDossier',
        tags: ['Intégration — Documents'],
        summary: 'Supprimer un document',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'document', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Supprimé', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
