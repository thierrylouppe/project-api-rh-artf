<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AgentCreateResponse',
    properties: [
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(property: 'agent', ref: '#/components/schemas/Agent'),
                new OA\Property(property: 'dossier', ref: '#/components/schemas/DossierIntegration'),
            ],
            type: 'object'
        ),
        new OA\Property(property: 'message', type: 'string'),
    ]
)]
class AgentCreateResponse {}
