<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GenererActeResponse',
    properties: [
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(property: 'acte', ref: '#/components/schemas/ActeAdministratif'),
                new OA\Property(property: 'dossier', ref: '#/components/schemas/DossierIntegration'),
                new OA\Property(property: 'necessite_contrat', type: 'boolean', example: true),
                new OA\Property(property: 'prochaine_etape', type: 'string', example: 'contrat_signe'),
            ],
            type: 'object'
        ),
        new OA\Property(property: 'message', type: 'string'),
    ]
)]
class GenererActeResponse {}
