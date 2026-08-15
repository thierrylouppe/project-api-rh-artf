<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AgentResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Agent'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
    ]
)]
class AgentResponse {}
