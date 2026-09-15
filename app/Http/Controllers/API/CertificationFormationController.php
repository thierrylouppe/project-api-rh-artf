<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CertificationFormation\CreateRequest;
use App\Http\Resources\CertificationFormationResource;
use App\Services\CertificationFormationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificationFormationController extends BaseController
{
    public function __construct(CertificationFormationService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return CertificationFormationResource::class;
    }

    #[OA\Get(path: '/api/formations/certifications', operationId: 'listCertificationsFormation', tags: ['Formations'], summary: 'Liste des certifications', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Post(path: '/api/formations/certifications', operationId: 'storeCertificationFormation', tags: ['Formations'], summary: 'Enregistrer une certification', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créée')])]
    public function store(CreateRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['fichier']);

        return $this->respond(
            $this->service->create($data, $request->file('fichier')),
            'Certification enregistrée',
            201
        );
    }

    #[OA\Get(path: '/api/formations/agents/{agent}/certifications', operationId: 'certificationsFormationParAgent', tags: ['Formations'], summary: 'Certifications d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agent): JsonResponse
    {
        return $this->collectionResponse(
            CertificationFormationResource::collection($this->service->getByAgent($agent)),
            'Certifications récupérées'
        );
    }

    #[OA\Get(path: '/api/formations/certifications/{id}', operationId: 'showCertificationFormation', tags: ['Formations'], summary: 'Détail d\'une certification', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail')])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Get(path: '/api/formations/certifications/{id}/fichier', operationId: 'downloadCertificationFormation', tags: ['Formations'], summary: 'Télécharger la pièce', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Fichier')])]
    public function fichier(int $id): StreamedResponse
    {
        return $this->service->telecharger($id);
    }

    #[OA\Delete(path: '/api/formations/certifications/{id}', operationId: 'destroyCertificationFormation', tags: ['Formations'], summary: 'Supprimer une certification', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Supprimé')])]
    public function destroy(int $id): JsonResponse
    {
        return parent::destroy($id);
    }
}
