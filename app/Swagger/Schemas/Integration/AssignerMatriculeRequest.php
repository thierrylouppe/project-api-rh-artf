<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AssignerMatriculeRequest',
    required: ['matricule'],
    properties: [
        new OA\Property(property: 'matricule', type: 'string', example: 'ARTF-2026-001'),
    ]
)]
class AssignerMatriculeRequest {}
