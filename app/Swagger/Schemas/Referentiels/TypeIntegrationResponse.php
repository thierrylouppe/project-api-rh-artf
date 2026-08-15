<?php

namespace App\Swagger\Schemas\Referentiels;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TypeIntegrationResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/TypeIntegration'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
    ]
)]
class TypeIntegrationResponse {}
