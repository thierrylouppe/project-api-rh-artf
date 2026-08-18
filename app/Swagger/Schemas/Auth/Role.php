<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Role',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'rh'),
        new OA\Property(
            property: 'permissions',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Permission')
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Role {}
