<?php

namespace App\Swagger\Schemas\Referentiels;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TypeIntegrationListResponse',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TypeIntegrationList')
        ),
    ]
)]
class TypeIntegrationListResponse {}
