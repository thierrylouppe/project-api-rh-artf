<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DecisionRequest',
    properties: [
        new OA\Property(property: 'commentaire', type: 'string', nullable: true, example: 'Avis favorable'),
    ]
)]
class DecisionRequest {}
