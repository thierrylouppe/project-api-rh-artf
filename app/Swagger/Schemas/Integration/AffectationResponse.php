<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AffectationResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Affectation'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
    ]
)]
class AffectationResponse {}
