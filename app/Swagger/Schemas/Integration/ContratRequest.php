<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ContratRequest',
    required: ['agent_id', 'type_contrat_id', 'date_debut'],
    properties: [
        new OA\Property(property: 'agent_id', type: 'integer', example: 1),
        new OA\Property(property: 'type_contrat_id', type: 'integer', example: 1),
        new OA\Property(property: 'dossier_integration_id', type: 'integer', nullable: true),
        new OA\Property(property: 'fonction_id', type: 'integer', nullable: true),
        new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-09-01'),
        new OA\Property(property: 'date_fin', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'remuneration', type: 'number', nullable: true, example: 350000),
    ]
)]
class ContratRequest {}
