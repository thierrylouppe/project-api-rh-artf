<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\PriseEnCharge\CreateRequest;
use App\Http\Requests\PriseEnCharge\UpdateRequest;
use App\Http\Requests\Sante\AccorderRequest;
use App\Http\Requests\Sante\ClasserRequest;
use App\Http\Requests\Sante\InstruireRequest;
use App\Http\Requests\Sante\RefuserRequest;
use App\Http\Requests\Sante\StorePieceRequest;
use App\Http\Resources\PieceSanteResource;
use App\Http\Resources\PriseEnChargeResource;
use App\Services\PriseEnChargeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** @property PriseEnChargeService $service */
class PriseEnChargeController extends BaseController
{
    public function __construct(PriseEnChargeService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return PriseEnChargeResource::class;
    }

    protected function showRelations(): array
    {
        return [
            'agent:id,matricule,nom,prenom,statut',
            'ayantDroit:id,nom,prenom,type',
            'structure:id,nom,type',
            'createur:id,name',
            'instructeur:id,name',
            'decideur:id,name',
            'affectationPaie',
            'pieces.uploader:id,name',
        ];
    }

    #[OA\Get(path: '/api/affaires-sociales/prises-en-charge', operationId: 'listPrisesEnCharge', tags: ['Affaires sociales'], summary: 'Liste des prises en charge santé', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/affaires-sociales/prises-en-charge', operationId: 'storePriseEnCharge', tags: ['Affaires sociales'], summary: 'Créer une prise en charge (brouillon)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Prise en charge créée', 201);
    }

    #[OA\Get(path: '/api/affaires-sociales/agents/{agent}/prises-en-charge', operationId: 'prisesEnChargeParAgent', tags: ['Affaires sociales'], summary: 'Prises en charge d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return $this->collectionResponse(
            PriseEnChargeResource::collection($this->service->getByAgent($agent)),
            'Prises en charge récupérées'
        );
    }

    #[OA\Get(path: '/api/affaires-sociales/prises-en-charge/{id}', operationId: 'showPriseEnCharge', tags: ['Affaires sociales'], summary: 'Détail d\'une prise en charge', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Put(path: '/api/affaires-sociales/prises-en-charge/{id}', operationId: 'updatePriseEnCharge', tags: ['Affaires sociales'], summary: 'Modifier une prise en charge en brouillon ou soumise', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mise à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Prise en charge mise à jour');
    }

    #[OA\Delete(path: '/api/affaires-sociales/prises-en-charge/{id}', operationId: 'destroyPriseEnCharge', tags: ['Affaires sociales'], summary: 'Supprimer un brouillon', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }

    #[OA\Post(path: '/api/affaires-sociales/prises-en-charge/{id}/soumettre', operationId: 'soumettrePriseEnCharge', tags: ['Affaires sociales'], summary: 'Soumettre une prise en charge', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Soumise')])]
    public function soumettre(int $id): JsonResponse
    {
        return $this->respond($this->service->soumettre($id), 'Prise en charge soumise');
    }

    #[OA\Post(path: '/api/affaires-sociales/prises-en-charge/{id}/instruire', operationId: 'instruirePriseEnCharge', tags: ['Affaires sociales'], summary: 'Instruire une prise en charge et la transmettre au DG', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Instruite')])]
    public function instruire(InstruireRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->instruire($id, $request->validated()), 'Prise en charge instruite');
    }

    #[OA\Post(path: '/api/affaires-sociales/prises-en-charge/{id}/accorder', operationId: 'accorderPriseEnCharge', tags: ['Affaires sociales'], summary: 'Accorder une prise en charge (DG) et poser en paie', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Accordée')])]
    public function accorder(AccorderRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->accorder($id, $request->validated()), 'Prise en charge accordée');
    }

    #[OA\Post(path: '/api/affaires-sociales/prises-en-charge/{id}/refuser', operationId: 'refuserPriseEnCharge', tags: ['Affaires sociales'], summary: 'Refuser une prise en charge (DG)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Refusée')])]
    public function refuser(RefuserRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->refuser($id, $request->validated('commentaire')), 'Prise en charge refusée');
    }

    #[OA\Post(path: '/api/affaires-sociales/prises-en-charge/{id}/classer', operationId: 'classerPriseEnCharge', tags: ['Affaires sociales'], summary: 'Classer une prise en charge sans suite', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Classée')])]
    public function classer(ClasserRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->classer($id, $request->validated('commentaire')), 'Prise en charge classée');
    }

    #[OA\Get(path: '/api/affaires-sociales/prises-en-charge/{id}/simulation', operationId: 'simulationPriseEnCharge', tags: ['Affaires sociales'], summary: 'Simuler le montant CCN', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Simulation')])]
    public function simulation(int $id): JsonResponse
    {
        return $this->successResponse($this->service->simuler($id), 'Simulation CCN');
    }

    #[OA\Get(path: '/api/affaires-sociales/prises-en-charge/{id}/pdf-decision', operationId: 'decisionPriseEnChargePdf', tags: ['Affaires sociales'], summary: 'PDF de la décision du DG', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function decisionPdf(int $id): Response
    {
        return $this->service->decisionPdf($id);
    }

    #[OA\Get(path: '/api/affaires-sociales/prises-en-charge/{id}/pieces', operationId: 'listPiecesPriseEnCharge', tags: ['Affaires sociales'], summary: 'Pièces d\'une prise en charge', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function pieces(int $id): JsonResponse
    {
        return $this->collectionResponse(
            PieceSanteResource::collection($this->service->pieces($id)),
            'Pièces récupérées'
        );
    }

    #[OA\Post(path: '/api/affaires-sociales/prises-en-charge/{id}/pieces', operationId: 'storePiecePriseEnCharge', tags: ['Affaires sociales'], summary: 'Joindre une pièce à une prise en charge', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Créée')])]
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

    #[OA\Get(path: '/api/affaires-sociales/prises-en-charge/{id}/pieces/{pieceId}', operationId: 'downloadPiecePriseEnCharge', tags: ['Affaires sociales'], summary: 'Télécharger une pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'pieceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Fichier')])]
    public function downloadPiece(int $id, int $pieceId): StreamedResponse
    {
        return $this->service->telechargerPiece($id, $pieceId);
    }

    #[OA\Delete(path: '/api/affaires-sociales/prises-en-charge/{id}/pieces/{pieceId}', operationId: 'destroyPiecePriseEnCharge', tags: ['Affaires sociales'], summary: 'Retirer une pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'pieceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroyPiece(int $id, int $pieceId): JsonResponse
    {
        $this->service->supprimerPiece($id, $pieceId);

        return $this->messageResponse('Pièce supprimée');
    }
}
