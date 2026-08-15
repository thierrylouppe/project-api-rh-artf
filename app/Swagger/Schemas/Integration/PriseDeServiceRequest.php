<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PriseDeServiceRequest',
    required: ['agent_id', 'responsable_id', 'date_prise_service'],
    properties: [
        new OA\Property(property: 'agent_id', type: 'integer', example: 1),
        new OA\Property(property: 'dossier_integration_id', type: 'integer', nullable: true),
        new OA\Property(property: 'responsable_id', type: 'integer', example: 2),
        new OA\Property(property: 'date_prise_service', type: 'string', format: 'date', example: '2026-09-15'),
        new OA\Property(property: 'confirmation_presence', type: 'boolean', example: true),
        new OA\Property(property: 'confirmation_installation', type: 'boolean', example: true),
        new OA\Property(property: 'confirmation_equipements', type: 'boolean', example: true),
        new OA\Property(property: 'observations', type: 'string', nullable: true),
    ]
)]
class PriseDeServiceRequest {}
