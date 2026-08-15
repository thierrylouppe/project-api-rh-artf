<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DossierIntegrationRequest',
    required: ['type_integration_id'],
    properties: [
        new OA\Property(property: 'type_integration_id', type: 'integer', example: 1),
        new OA\Property(property: 'demandeur_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(
            property: 'structurable_type',
            type: 'string',
            nullable: true,
            enum: ['App\\Models\\Direction', 'App\\Models\\Service', 'App\\Models\\Bureau'],
            example: 'App\\Models\\Direction'
        ),
        new OA\Property(property: 'structurable_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'poste_demande', type: 'string', nullable: true, example: 'Chargé de mission'),
        new OA\Property(property: 'nombre_postes', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'date_demande', type: 'string', format: 'date', nullable: true, example: '2026-08-10'),
        new OA\Property(property: 'motif', type: 'string', nullable: true, example: 'Besoin de renfort'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
    ]
)]
class DossierIntegrationRequest {}
