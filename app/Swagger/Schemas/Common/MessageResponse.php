<?php

namespace App\Swagger\Schemas\Common;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MessageResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Opération réussie'),
    ]
)]
class MessageResponse {}
