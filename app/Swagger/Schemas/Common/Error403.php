<?php

namespace App\Swagger\Schemas\Common;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Error403',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Action non autorisée.'),
    ]
)]
class Error403 {}
