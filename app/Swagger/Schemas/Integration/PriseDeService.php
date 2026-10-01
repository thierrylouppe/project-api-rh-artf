<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PriseDeService',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'agent_id', type: 'integer', example: 1),
        new OA\Property(property: 'dossier_integration_id', type: 'integer', nullable: true),
        new OA\Property(property: 'responsable_id', type: 'integer', example: 2),
        new OA\Property(property: 'date_prise_service', type: 'string', format: 'date'),
        new OA\Property(property: 'confirmation_presence', type: 'boolean', example: true),
        new OA\Property(property: 'confirmation_installation', type: 'boolean', example: true),
        new OA\Property(property: 'confirmation_equipements', type: 'boolean', example: true),
        new OA\Property(property: 'observations', type: 'string', nullable: true),
    ]
)]
class PriseDeService {}
