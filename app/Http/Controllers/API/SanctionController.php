<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Sanction\CreateRequest;
use App\Http\Requests\Sanction\InstruireRequest;
use App\Http\Requests\Sanction\RejeterRequest;
use App\Http\Requests\Sanction\StorePieceRequest;
use App\Http\Requests\Sanction\UpdateRequest;
use App\Http\Requests\Sanction\ValiderRequest;
use App\Http\Resources\AvertissementResource;
use App\Http\Resources\SanctionPieceResource;
use App\Http\Resources\SanctionResource;
use App\Services\SanctionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SanctionController extends BaseController
{
    public function __construct(SanctionService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return SanctionResource::class;
    }

    protected function showRelations(): array
    {
        return [
            'agent:id,matricule,nom,prenom',
            'typeSanction',
            'validateur:id,name',
            'createur:id,name',
            'pieces.uploader:id,name',
        ];
    }

    #[OA\Get(path: '/api/discipline/sanctions', operationId: 'listSanctions', tags: ['Discipline'], summary: 'Liste des sanctions', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/discipline/sanctions', operationId: 'storeSanction', tags: ['Discipline'], summary: 'Soumettre un rapport disciplinaire (N+1 ou RH)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Rapport disciplinaire soumis', 201);
    }

    #[OA\Get(path: '/api/discipline/sanctions/a-instruire', operationId: 'sanctionsAInstruire', tags: ['Discipline'], summary: 'File des rapports à instruire (RH)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function aInstruire(): JsonResponse
    {
        return $this->collectionResponse(
            SanctionResource::collection($this->service->aInstruire()),
            'Sanctions à instruire'
        );
    }

    #[OA\Get(path: '/api/discipline/sanctions/a-valider', operationId: 'sanctionsAValider', tags: ['Discipline'], summary: 'Alias de a-prononcer', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function aValider(): JsonResponse
    {
        return $this->aPrononcer();
    }

    #[OA\Get(path: '/api/discipline/sanctions/a-prononcer', operationId: 'sanctionsAPrononcer', tags: ['Discipline'], summary: 'File des sanctions à prononcer (DG)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function aPrononcer(): JsonResponse
    {
        return $this->collectionResponse(
            SanctionResource::collection($this->service->aPrononcer()),
            'Sanctions à prononcer'
        );
    }

    #[OA\Get(path: '/api/discipline/sanctions/mes-rapports', operationId: 'mesRapportsDiscipline', tags: ['Discipline'], summary: 'Rapports disciplinaires soumis par le N+1 connecté', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function mesRapports(): JsonResponse
    {
        return $this->collectionResponse(
            SanctionResource::collection($this->service->mesRapports()),
            'Rapports disciplinaires récupérés'
        );
    }

    #[OA\Get(path: '/api/discipline/moi/historique', operationId: 'mesDossiersDiscipline', tags: ['Discipline'], summary: 'Historique disciplinaire de l\'agent connecté', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Historique')])]
    public function moiHistorique(): JsonResponse
    {
        $historique = $this->service->monHistorique();

        return response()->json([
            'data' => [
                'sanctions' => SanctionResource::collection($historique['sanctions']),
                'avertissements' => AvertissementResource::collection($historique['avertissements']),
                'recidive' => $historique['recidive'],
            ],
            'message' => 'Historique disciplinaire récupéré',
        ]);
    }

    #[OA\Get(path: '/api/discipline/moi/sanctions', operationId: 'mesSanctions', tags: ['Discipline'], summary: 'Sanctions de l\'agent connecté', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function mesSanctions(): JsonResponse
    {
        return $this->collectionResponse(
            SanctionResource::collection($this->service->mesSanctions()),
            'Sanctions récupérées'
        );
    }

    #[OA\Get(path: '/api/discipline/moi/sanctions/{id}', operationId: 'maSanction', tags: ['Discipline'], summary: 'Détail d\'une sanction de l\'agent connecté', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function maSanction(int $id): JsonResponse
    {
        return $this->respond($this->service->maSanction($id), 'Sanction récupérée');
    }

    #[OA\Get(path: '/api/discipline/moi/sanctions/{id}/pdf-decision', operationId: 'maDecisionDisciplinePdf', tags: ['Discipline'], summary: 'PDF de la décision pour l\'agent concerné', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function maDecisionPdf(int $id): Response
    {
        return $this->service->maDecisionPdf($id);
    }

    #[OA\Get(path: '/api/discipline/agents/{agent}/sanctions', operationId: 'sanctionsParAgent', tags: ['Discipline'], summary: 'Sanctions d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return response()->json([
            'data' => SanctionResource::collection($this->service->getByAgent($agent)),
            'message' => 'Sanctions récupérées',
        ]);
    }

    #[OA\Get(path: '/api/discipline/agents/{agent}/historique', operationId: 'historiqueDisciplineAgent', tags: ['Discipline'], summary: 'Historique disciplinaire d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Historique')])]
    public function historique(int $agent): JsonResponse
    {
        $historique = $this->service->historique($agent);

        return response()->json([
            'data' => [
                'sanctions' => SanctionResource::collection($historique['sanctions']),
                'avertissements' => AvertissementResource::collection($historique['avertissements']),
                'recidive' => $historique['recidive'],
            ],
            'message' => 'Historique disciplinaire récupéré',
        ]);
    }

    #[OA\Get(path: '/api/discipline/sanctions/{id}', operationId: 'showSanction', tags: ['Discipline'], summary: 'Détail d\'une sanction', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        $id = (int) $request->route('id');

        return $this->respond($this->service->voir($id), 'Sanction récupérée');
    }

    #[OA\Put(path: '/api/discipline/sanctions/{id}', operationId: 'updateSanction', tags: ['Discipline'], summary: 'Modifier un rapport encore ouvert', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Mis à jour')])]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Rapport disciplinaire mis à jour');
    }

    #[OA\Post(path: '/api/discipline/sanctions/{id}/instruire', operationId: 'instruireSanction', tags: ['Discipline'], summary: 'Instruire un rapport et le transmettre au DG', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Instruite')])]
    public function instruire(InstruireRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->instruire($id, $request->validated()), 'Sanction instruite');
    }

    #[OA\Post(path: '/api/discipline/sanctions/{id}/valider', operationId: 'validerSanction', tags: ['Discipline'], summary: 'Prononcer une sanction (DG)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Prononcée')])]
    public function valider(ValiderRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->valider($id, $request->validated()), 'Sanction prononcée');
    }

    #[OA\Post(path: '/api/discipline/sanctions/{id}/rejeter', operationId: 'rejeterSanction', tags: ['Discipline'], summary: 'Classer un rapport sans suite (DG)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Classée')])]
    public function rejeter(RejeterRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->rejeter($id, $request->validated('commentaire')), 'Rapport classé sans suite');
    }

    #[OA\Get(path: '/api/discipline/sanctions/{id}/pieces', operationId: 'listPiecesSanction', tags: ['Discipline'], summary: 'Pièces d\'un rapport disciplinaire', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function pieces(int $id): JsonResponse
    {
        return $this->collectionResponse(
            SanctionPieceResource::collection($this->service->pieces($id)),
            'Pièces récupérées'
        );
    }

    #[OA\Post(path: '/api/discipline/sanctions/{id}/pieces', operationId: 'storePieceSanction', tags: ['Discipline'], summary: 'Joindre une pièce au rapport', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function storePiece(StorePieceRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            new SanctionPieceResource($this->service->ajouterPiece($id, $request->file('fichier'))),
            'Pièce jointe',
            201
        );
    }

    #[OA\Get(path: '/api/discipline/sanctions/{id}/pieces/{pieceId}', operationId: 'downloadPieceSanction', tags: ['Discipline'], summary: 'Télécharger une pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'pieceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Fichier')])]
    public function downloadPiece(int $id, int $pieceId): StreamedResponse
    {
        return $this->service->telechargerPiece($id, $pieceId);
    }

    #[OA\Delete(path: '/api/discipline/sanctions/{id}/pieces/{pieceId}', operationId: 'destroyPieceSanction', tags: ['Discipline'], summary: 'Retirer une pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'pieceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroyPiece(int $id, int $pieceId): JsonResponse
    {
        $this->service->supprimerPiece($id, $pieceId);

        return $this->messageResponse('Pièce supprimée');
    }

    #[OA\Get(path: '/api/discipline/sanctions/{id}/pdf-rapport', operationId: 'rapportDisciplinePdf', tags: ['Discipline'], summary: 'PDF du rapport disciplinaire', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function rapportPdf(int $id): Response
    {
        return $this->service->rapportPdf($id);
    }

    #[OA\Get(path: '/api/discipline/sanctions/{id}/pdf-decision', operationId: 'decisionDisciplinePdf', tags: ['Discipline'], summary: 'PDF de la décision du DG', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function decisionPdf(int $id): Response
    {
        return $this->service->decisionPdf($id);
    }

    #[OA\Delete(path: '/api/discipline/sanctions/{id}', operationId: 'destroySanction', tags: ['Discipline'], summary: 'Supprimer un rapport en attente', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
