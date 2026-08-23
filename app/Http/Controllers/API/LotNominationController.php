<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Nomination\ActiverRequest;
use App\Http\Requests\Nomination\GroupeeRequest;
use App\Http\Requests\Nomination\RejeterRequest;
use App\Http\Resources\LotNominationResource;
use App\Services\LotNominationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/** @property LotNominationService $service */
class LotNominationController extends BaseController
{
    public function __construct(LotNominationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return LotNominationResource::class;
    }

    #[OA\Post(path: '/api/carriere/nominations/groupee', operationId: 'storeLotNominationCarriere', tags: ['Carrière — Nominations'], summary: 'Créer un lot de nominations (un circuit, un acte)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Lot créé'), new OA\Response(response: 422, description: 'Validation')])]
    #[OA\Post(path: '/api/integration/nominations/groupee', operationId: 'storeLotNomination', tags: ['Intégration — Nominations'], summary: 'Créer un lot (alias)', deprecated: true, security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Lot créé')])]
    public function store(GroupeeRequest $request): JsonResponse
    {
        $lot = $this->service->creerGroupe($request->validated());

        return $this->respond($lot, 'Lot créé — un seul circuit de validation initialisé', 201);
    }

    #[OA\Get(path: '/api/carriere/nominations/lots/{id}', operationId: 'showLotNominationCarriere', tags: ['Carrière — Nominations'], summary: 'Détail d\'un lot', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function detail(int $lot): JsonResponse
    {
        return $this->respond($this->service->detail($lot));
    }

    #[OA\Post(path: '/api/carriere/nominations/lots/{id}/activer', operationId: 'activerLotNominationCarriere', tags: ['Carrière — Nominations'], summary: 'Activer toutes les lignes du lot', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Activé')])]
    public function activer(ActiverRequest $request, int $lot): JsonResponse
    {
        return $this->respond($this->service->activer($lot), 'Lot activé — toutes les nominations sont actives');
    }

    #[OA\Post(path: '/api/carriere/nominations/lots/{id}/rejeter', operationId: 'rejeterLotNominationCarriere', tags: ['Carrière — Nominations'], summary: 'Rejeter le lot', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Rejeté')])]
    public function rejeter(RejeterRequest $request, int $lot): JsonResponse
    {
        return $this->respond(
            $this->service->rejeter($lot, $request->validated('commentaire')),
            'Lot rejeté'
        );
    }

    #[OA\Get(path: '/api/carriere/nominations/lots/{id}/acte', operationId: 'acteLotNominationCarriere', tags: ['Carrière — Nominations'], summary: 'PDF unique listant toutes les nominations du lot', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function acte(int $lot): SymfonyResponse
    {
        $path     = $this->service->genererActePdf($lot);
        $fileName = $this->service->nomFichierActe($lot);

        return response()->download(Storage::disk('local')->path($path), $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
