<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ActeAdministratifResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/ActeAdministratif'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
    ]
)]
class ActeAdministratifResponse {}
