<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ContratResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Contrat'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
    ]
)]
class ContratResponse {}
