<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DossierIntegrationResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/DossierIntegration'),
        new OA\Property(property: 'message', type: 'string', nullable: true, example: 'Dossier d\'intégration créé'),
    ]
)]
class DossierIntegrationResponse {}
