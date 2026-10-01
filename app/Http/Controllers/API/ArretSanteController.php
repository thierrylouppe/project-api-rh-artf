<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ArretSante\CreateRequest;
use App\Http\Requests\ArretSante\UpdateRequest;
use App\Http\Requests\Sante\AccorderRequest;
use App\Http\Requests\Sante\ClasserRequest;
use App\Http\Requests\Sante\InstruireRequest;
use App\Http\Requests\Sante\RefuserRequest;
use App\Http\Requests\Sante\StorePieceRequest;
use App\Http\Resources\ArretSanteResource;
use App\Http\Resources\PieceSanteResource;
use App\Services\ArretSanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** @property ArretSanteService $service */
class ArretSanteController extends BaseController
{
    public function __construct(ArretSanteService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ArretSanteResource::class;
    }

    protected function showRelations(): array
    {
        return [
            'agent:id,matricule,nom,prenom,statut',
            'structure:id,nom,type',
            'createur:id,name',
            'instructeur:id,name',
            'decideur:id,name',
            'affectationPaie',
            'pieces.uploader:id,name',
        ];
    }

    #[OA\Get(path: '/api/affaires-sociales/arrets', operationId: 'listArretsSante', tags: ['Affaires sociales'], summary: 'Liste des arrêts maladie / AT-MP', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/affaires-sociales/arrets', operationId: 'storeArretSante', tags: ['Affaires sociales'], summary: 'Créer un arrêt (brouillon)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créé')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Arrêt créé', 201);
    }

    #[OA\Get(path: '/api/affaires-sociales/agents/{agent}/arrets', operationId: 'arretsParAgent', tags: ['Affaires sociales'], summary: 'Arrêts d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return $this->collectionResponse(
            ArretSanteResource::collection($this->service->getByAgent($agent)),
            'Arrêts récupérés'
        );
    }

    #[OA\Get(path: '/api/affaires-sociales/arrets/{id}', operationId: 'showArretSante', tags: ['Affaires sociales'], summary: 'Détail d\'un arrêt', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Put(path: '/api/affaires-sociales/arrets/{id}', operationId: 'updateArretSante', tags: ['Affaires sociales'], summary: 'Modifier un arrêt en brouillon ou soumis', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mise à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Arrêt mis à jour');
    }

    #[OA\Delete(path: '/api/affaires-sociales/arrets/{id}', operationId: 'destroyArretSante', tags: ['Affaires sociales'], summary: 'Supprimer un brouillon', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }

    #[OA\Post(path: '/api/affaires-sociales/arrets/{id}/soumettre', operationId: 'soumettreArretSante', tags: ['Affaires sociales'], summary: 'Soumettre un arrêt', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Soumis')])]
    public function soumettre(int $id): JsonResponse
    {
        return $this->respond($this->service->soumettre($id), 'Arrêt soumis');
    }

    #[OA\Post(path: '/api/affaires-sociales/arrets/{id}/instruire', operationId: 'instruireArretSante', tags: ['Affaires sociales'], summary: 'Instruire un arrêt et le transmettre au DG', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Instruit')])]
    public function instruire(InstruireRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->instruire($id, $request->validated()), 'Arrêt instruit');
    }

    #[OA\Post(path: '/api/affaires-sociales/arrets/{id}/accorder', operationId: 'accorderArretSante', tags: ['Affaires sociales'], summary: 'Accorder un arrêt (DG) et poser l\'allocation en paie', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Accordé')])]
    public function accorder(AccorderRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->accorder($id, $request->validated()), 'Arrêt accordé');
    }

    #[OA\Post(path: '/api/affaires-sociales/arrets/{id}/refuser', operationId: 'refuserArretSante', tags: ['Affaires sociales'], summary: 'Refuser un arrêt (DG)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Refusé')])]
    public function refuser(RefuserRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->refuser($id, $request->validated('commentaire')), 'Arrêt refusé');
    }

    #[OA\Post(path: '/api/affaires-sociales/arrets/{id}/classer', operationId: 'classerArretSante', tags: ['Affaires sociales'], summary: 'Classer un arrêt sans suite', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Classé')])]
    public function classer(ClasserRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->classer($id, $request->validated('commentaire')), 'Arrêt classé');
    }

    #[OA\Get(path: '/api/affaires-sociales/arrets/{id}/simulation', operationId: 'simulationArretSante', tags: ['Affaires sociales'], summary: 'Simuler l\'allocation CCN', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Simulation')])]
    public function simulation(int $id): JsonResponse
    {
        return $this->successResponse($this->service->simuler($id), 'Simulation CCN');
    }

    #[OA\Get(path: '/api/affaires-sociales/arrets/{id}/pdf-decision', operationId: 'decisionArretSantePdf', tags: ['Affaires sociales'], summary: 'PDF de la décision du DG', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function decisionPdf(int $id): Response
    {
        return $this->service->decisionPdf($id);
    }

    #[OA\Get(path: '/api/affaires-sociales/arrets/{id}/pieces', operationId: 'listPiecesArretSante', tags: ['Affaires sociales'], summary: 'Pièces d\'un arrêt', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function pieces(int $id): JsonResponse
    {
        return $this->collectionResponse(
            PieceSanteResource::collection($this->service->pieces($id)),
            'Pièces récupérées'
        );
    }

    #[OA\Post(path: '/api/affaires-sociales/arrets/{id}/pieces', operationId: 'storePieceArretSante', tags: ['Affaires sociales'], summary: 'Joindre une pièce à un arrêt', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function storePiece(StorePieceRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            new PieceSanteResource($this->service->ajouterPiece(
                $id,
                $request->file('fichier'),
                $request->validated('type_piece')
            )),
            'Pièce jointe',
            201
        );
    }

    #[OA\Get(path: '/api/affaires-sociales/arrets/{id}/pieces/{pieceId}', operationId: 'downloadPieceArretSante', tags: ['Affaires sociales'], summary: 'Télécharger une pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'pieceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Fichier')])]
    public function downloadPiece(int $id, int $pieceId): StreamedResponse
    {
        return $this->service->telechargerPiece($id, $pieceId);
    }

    #[OA\Delete(path: '/api/affaires-sociales/arrets/{id}/pieces/{pieceId}', operationId: 'destroyPieceArretSante', tags: ['Affaires sociales'], summary: 'Retirer une pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'pieceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroyPiece(int $id, int $pieceId): JsonResponse
    {
        $this->service->supprimerPiece($id, $pieceId);

        return $this->messageResponse('Pièce supprimée');
    }
}
