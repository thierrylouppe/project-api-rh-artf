<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\AffiliationSocialeResource;
use App\Http\Resources\AyantDroitResource;
use App\Services\DossierSocialService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DossierSocialController
{
    use ApiResponse;

    public function __construct(private readonly DossierSocialService $service) {}

    #[OA\Get(path: '/api/affaires-sociales/agents/{agent}/dossier-social', operationId: 'dossierSocialAgent', tags: ['Affaires sociales'], summary: 'Dossier social d\'un agent (affiliations, ayants droit, synthèse CCN)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Dossier')])]
    public function show(int $agent): JsonResponse
    {
        $dossier = $this->service->getDossier($agent);

        return $this->successResponse([
            'agent' => $dossier['agent'],
            'affiliations' => AffiliationSocialeResource::collection($dossier['affiliations']),
            'ayants_droit' => AyantDroitResource::collection($dossier['ayants_droit']),
            'synthese' => $dossier['synthese'],
        ], 'Dossier social récupéré');
    }
}
