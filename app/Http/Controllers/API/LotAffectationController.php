<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Affectation\ActiverRequest;
use App\Http\Requests\Affectation\GroupeeRequest;
use App\Http\Requests\Affectation\RejeterRequest;
use App\Http\Resources\LotAffectationResource;
use App\Services\LotAffectationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/** @property LotAffectationService $service */
class LotAffectationController extends BaseController
{
    public function __construct(LotAffectationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return LotAffectationResource::class;
    }

    #[OA\Post(path: '/api/carriere/affectations/groupee', operationId: 'storeLotAffectationCarriere', tags: ['Carrière — Affectations'], summary: 'Créer un lot d\'affectations (un circuit, un acte)', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Lot créé'), new OA\Response(response: 422, description: 'Validation')])]
    #[OA\Post(path: '/api/integration/affectations/groupee', operationId: 'storeLotAffectation', tags: ['Intégration — Affectations'], summary: 'Créer un lot (alias)', deprecated: true, security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Lot créé')])]
    public function store(GroupeeRequest $request): JsonResponse
    {
        $lot = $this->service->creerGroupe(
            $request->validated(),
            $request->file('note_service')
        );

        $message      = 'Lot créé — un seul circuit de validation initialisé';
        $sansSuperior = $lot->affectations->filter(fn ($a) => empty($a->superieur_hierarchique_id))->count();
        if ($sansSuperior > 0) {
            $message .= ". {$sansSuperior} agent(s) sans supérieur hiérarchique résolu pour leur structure.";
        }

        return $this->respond($lot, $message, 201);
    }

    #[OA\Get(path: '/api/carriere/affectations/lots/{id}', operationId: 'showLotAffectationCarriere', tags: ['Carrière — Affectations'], summary: 'Détail d\'un lot', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function detail(int $lot): JsonResponse
    {
        return $this->respond($this->service->detail($lot));
    }

    #[OA\Post(path: '/api/carriere/affectations/lots/{id}/activer', operationId: 'activerLotAffectationCarriere', tags: ['Carrière — Affectations'], summary: 'Activer toutes les lignes du lot', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Activé')])]
    public function activer(ActiverRequest $request, int $lot): JsonResponse
    {
        return $this->respond($this->service->activer($lot), 'Lot activé — toutes les affectations sont actives');
    }

    #[OA\Post(path: '/api/carriere/affectations/lots/{id}/rejeter', operationId: 'rejeterLotAffectationCarriere', tags: ['Carrière — Affectations'], summary: 'Rejeter le lot', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Rejeté')])]
    public function rejeter(RejeterRequest $request, int $lot): JsonResponse
    {
        return $this->respond(
            $this->service->rejeter($lot, $request->validated('commentaire')),
            'Lot rejeté'
        );
    }

    #[OA\Get(path: '/api/carriere/affectations/lots/{id}/acte', operationId: 'acteLotAffectationCarriere', tags: ['Carrière — Affectations'], summary: 'PDF unique listant toutes les affectations du lot', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'PDF')])]
    public function acte(int $lot): SymfonyResponse
    {
        $path     = $this->service->genererActePdf($lot);
        $fileName = $this->service->nomFichierActe($lot);

        return response()->download(Storage::disk('local')->path($path), $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
