<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'NominationResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Nomination'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
    ]
)]
class NominationResponse {}
