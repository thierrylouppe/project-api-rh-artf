<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Prestation\AccorderRequest;
use App\Http\Requests\Prestation\ClasserRequest;
use App\Http\Requests\Prestation\CreateRequest;
use App\Http\Requests\Prestation\InstruireRequest;
use App\Http\Requests\Prestation\RefuserRequest;
use App\Http\Requests\Prestation\StorePieceRequest;
use App\Http\Requests\Prestation\UpdateRequest;
use App\Http\Resources\PrestationPieceResource;
use App\Http\Resources\PrestationResource;
use App\Services\PrestationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** @property PrestationService $service */
class PrestationController extends BaseController
{
    public function __construct(PrestationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return PrestationResource::class;
    }

    protected function showRelations(): array
    {
        return [
            'agent:id,matricule,nom,prenom,statut',
            'ayantDroit:id,nom,prenom,type',
            'createur:id,name',
            'instructeur:id,name',
            'decideur:id,name',
            'affectationPaie',
            'pieces.uploader:id,name',
        ];
    }

    #[OA\Get(path: '/api/affaires-sociales/prestations', operationId: 'listPrestations', tags: ['Affaires sociales'], summary: 'Liste des prestations sociales', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/affaires-sociales/prestations', operationId: 'storePrestation', tags: ['Affaires sociales'], summary: 'Créer une demande de prestation (brouillon)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Prestation créée', 201);
    }

    #[OA\Get(path: '/api/affaires-sociales/agents/{agent}/prestations', operationId: 'prestationsParAgent', tags: ['Affaires sociales'], summary: 'Prestations d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return $this->collectionResponse(
            PrestationResource::collection($this->service->getByAgent($agent)),
            'Prestations récupérées'
        );
    }

    #[OA\Get(path: '/api/affaires-sociales/prestations/{id}', operationId: 'showPrestation', tags: ['Affaires sociales'], summary: 'Détail d\'une prestation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Put(path: '/api/affaires-sociales/prestations/{id}', operationId: 'updatePrestation', tags: ['Affaires sociales'], summary: 'Modifier une prestation en brouillon ou soumise', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mise à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Prestation mise à jour');
    }

    #[OA\Delete(path: '/api/affaires-sociales/prestations/{id}', operationId: 'destroyPrestation', tags: ['Affaires sociales'], summary: 'Supprimer un brouillon', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }

    #[OA\Post(path: '/api/affaires-sociales/prestations/{id}/soumettre', operationId: 'soumettrePrestation', tags: ['Affaires sociales'], summary: 'Soumettre une prestation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Soumise')])]
    public function soumettre(int $id): JsonResponse
    {
        return $this->respond($this->service->soumettre($id), 'Prestation soumise');
    }

    #[OA\Post(path: '/api/affaires-sociales/prestations/{id}/instruire', operationId: 'instruirePrestation', tags: ['Affaires sociales'], summary: 'Instruire une prestation et la transmettre au DG', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Instruite')])]
    public function instruire(InstruireRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->instruire($id, $request->validated()), 'Prestation instruite');
    }

    #[OA\Post(path: '/api/affaires-sociales/prestations/{id}/accorder', operationId: 'accorderPrestation', tags: ['Affaires sociales'], summary: 'Accorder une prestation (DG) et poser en paie', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Accordée')])]
    public function accorder(AccorderRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->accorder($id, $request->validated()), 'Prestation accordée');
    }

    #[OA\Post(path: '/api/affaires-sociales/prestations/{id}/refuser', operationId: 'refuserPrestation', tags: ['Affaires sociales'], summary: 'Refuser une prestation (DG)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Refusée')])]
    public function refuser(RefuserRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->refuser($id, $request->validated('commentaire')), 'Prestation refusée');
    }

    #[OA\Post(path: '/api/affaires-sociales/prestations/{id}/classer', operationId: 'classerPrestation', tags: ['Affaires sociales'], summary: 'Classer une prestation sans suite', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Classée')])]
    public function classer(ClasserRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->classer($id, $request->validated('commentaire')), 'Prestation classée');
    }

    #[OA\Get(path: '/api/affaires-sociales/prestations/{id}/simulation', operationId: 'simulationPrestation', tags: ['Affaires sociales'], summary: 'Simuler le montant CCN', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Simulation')])]
    public function simulation(int $id): JsonResponse
    {
        return $this->successResponse($this->service->simuler($id), 'Simulation CCN');
    }

    #[OA\Get(path: '/api/affaires-sociales/prestations/{id}/pdf-decision', operationId: 'decisionPrestationPdf', tags: ['Affaires sociales'], summary: 'PDF de la décision du DG', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function decisionPdf(int $id): Response
    {
        return $this->service->decisionPdf($id);
    }

    #[OA\Get(path: '/api/affaires-sociales/prestations/{id}/pieces', operationId: 'listPiecesPrestation', tags: ['Affaires sociales'], summary: 'Pièces d\'une prestation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function pieces(int $id): JsonResponse
    {
        return $this->collectionResponse(
            PrestationPieceResource::collection($this->service->pieces($id)),
            'Pièces récupérées'
        );
    }

    #[OA\Post(path: '/api/affaires-sociales/prestations/{id}/pieces', operationId: 'storePiecePrestation', tags: ['Affaires sociales'], summary: 'Joindre une pièce à une prestation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function storePiece(StorePieceRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            new PrestationPieceResource($this->service->ajouterPiece(
                $id,
                $request->file('fichier'),
                $request->validated('type_piece')
            )),
            'Pièce jointe',
            201
        );
    }

    #[OA\Get(path: '/api/affaires-sociales/prestations/{id}/pieces/{pieceId}', operationId: 'downloadPiecePrestation', tags: ['Affaires sociales'], summary: 'Télécharger une pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'pieceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Fichier')])]
    public function downloadPiece(int $id, int $pieceId): StreamedResponse
    {
        return $this->service->telechargerPiece($id, $pieceId);
    }

    #[OA\Delete(path: '/api/affaires-sociales/prestations/{id}/pieces/{pieceId}', operationId: 'destroyPiecePrestation', tags: ['Affaires sociales'], summary: 'Retirer une pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'pieceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroyPiece(int $id, int $pieceId): JsonResponse
    {
        $this->service->supprimerPiece($id, $pieceId);

        return $this->messageResponse('Pièce supprimée');
    }
}
