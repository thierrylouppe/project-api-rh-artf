<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DossierIntegration',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'reference', type: 'string', example: 'INT-2026-0001'),
        new OA\Property(property: 'statut', type: 'string', example: 'BROUILLON'),
        new OA\Property(property: 'statut_label', type: 'string', example: 'Brouillon'),
        new OA\Property(property: 'date_demande', type: 'string', format: 'date', nullable: true, example: '2026-08-10'),
        new OA\Property(property: 'poste_demande', type: 'string', nullable: true, example: 'Chargé de mission'),
        new OA\Property(property: 'nombre_postes', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'motif', type: 'string', nullable: true),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'type_integration_id', type: 'integer', example: 1),
        new OA\Property(property: 'demandeur_id', type: 'integer', nullable: true),
        new OA\Property(property: 'agent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class DossierIntegration {}
