<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\AyantDroit\CreateRequest;
use App\Http\Requests\AyantDroit\StorePieceRequest;
use App\Http\Requests\AyantDroit\UpdateRequest;
use App\Http\Resources\AyantDroitPieceResource;
use App\Http\Resources\AyantDroitResource;
use App\Services\AyantDroitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AyantDroitController extends BaseController
{
    public function __construct(AyantDroitService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AyantDroitResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent:id,matricule,nom,prenom,statut', 'pieces.uploader:id,name'];
    }

    #[OA\Get(path: '/api/affaires-sociales/ayants-droit', operationId: 'listAyantsDroit', tags: ['Affaires sociales'], summary: 'Liste des ayants droit', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/affaires-sociales/ayants-droit', operationId: 'storeAyantDroit', tags: ['Affaires sociales'], summary: 'Déclarer un ayant droit', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créé')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Ayant droit créé', 201);
    }

    #[OA\Get(path: '/api/affaires-sociales/agents/{agent}/ayants-droit', operationId: 'ayantsDroitParAgent', tags: ['Affaires sociales'], summary: 'Ayants droit d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return $this->collectionResponse(
            AyantDroitResource::collection($this->service->getByAgent($agent)),
            'Ayants droit récupérés'
        );
    }

    #[OA\Get(path: '/api/affaires-sociales/ayants-droit/{id}', operationId: 'showAyantDroit', tags: ['Affaires sociales'], summary: 'Détail d\'un ayant droit', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Put(path: '/api/affaires-sociales/ayants-droit/{id}', operationId: 'updateAyantDroit', tags: ['Affaires sociales'], summary: 'Modifier un ayant droit', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mis à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Ayant droit mis à jour');
    }

    #[OA\Delete(path: '/api/affaires-sociales/ayants-droit/{id}', operationId: 'destroyAyantDroit', tags: ['Affaires sociales'], summary: 'Supprimer un ayant droit', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }

    #[OA\Get(path: '/api/affaires-sociales/ayants-droit/{id}/pieces', operationId: 'listPiecesAyantDroit', tags: ['Affaires sociales'], summary: 'Pièces d\'un ayant droit', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function pieces(int $id): JsonResponse
    {
        return $this->collectionResponse(
            AyantDroitPieceResource::collection($this->service->pieces($id)),
            'Pièces récupérées'
        );
    }

    #[OA\Post(path: '/api/affaires-sociales/ayants-droit/{id}/pieces', operationId: 'storePieceAyantDroit', tags: ['Affaires sociales'], summary: 'Joindre une pièce à un ayant droit', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function storePiece(StorePieceRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            new AyantDroitPieceResource($this->service->ajouterPiece(
                $id,
                $request->file('fichier'),
                $request->validated('type_piece')
            )),
            'Pièce jointe',
            201
        );
    }

    #[OA\Get(path: '/api/affaires-sociales/ayants-droit/{id}/pieces/{pieceId}', operationId: 'downloadPieceAyantDroit', tags: ['Affaires sociales'], summary: 'Télécharger une pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'pieceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Fichier')])]
    public function downloadPiece(int $id, int $pieceId): StreamedResponse
    {
        return $this->service->telechargerPiece($id, $pieceId);
    }

    #[OA\Delete(path: '/api/affaires-sociales/ayants-droit/{id}/pieces/{pieceId}', operationId: 'destroyPieceAyantDroit', tags: ['Affaires sociales'], summary: 'Retirer une pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'pieceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroyPiece(int $id, int $pieceId): JsonResponse
    {
        $this->service->supprimerPiece($id, $pieceId);

        return $this->messageResponse('Pièce supprimée');
    }
}
