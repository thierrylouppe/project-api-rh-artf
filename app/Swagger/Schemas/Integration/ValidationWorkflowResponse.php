<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ValidationWorkflowResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/ValidationWorkflow'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
    ]
)]
class ValidationWorkflowResponse {}
