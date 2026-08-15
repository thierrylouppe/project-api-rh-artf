<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ValidationWorkflow',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'niveau', type: 'string', example: 'CHEF_SERVICE'),
        new OA\Property(property: 'niveau_label', type: 'string', example: 'Chef de service'),
        new OA\Property(property: 'ordre', type: 'integer', example: 1),
        new OA\Property(property: 'statut', type: 'string', example: 'EN_ATTENTE'),
        new OA\Property(property: 'commentaire', type: 'string', nullable: true),
        new OA\Property(property: 'date_decision', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class ValidationWorkflow {}
