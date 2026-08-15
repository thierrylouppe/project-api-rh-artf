<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Affectation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'agent_id', type: 'integer', example: 1),
        new OA\Property(property: 'structurable_type', type: 'string', example: 'App\\Models\\Direction'),
        new OA\Property(property: 'structurable_id', type: 'integer', example: 1),
        new OA\Property(property: 'motif', type: 'string', nullable: true),
        new OA\Property(property: 'date_affectation', type: 'string', format: 'date'),
        new OA\Property(property: 'date_fin', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'statut', type: 'string', example: 'EN_ATTENTE'),
        new OA\Property(property: 'statut_label', type: 'string', example: 'En attente'),
        new OA\Property(property: 'superieur_hierarchique_id', type: 'integer', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Affectation {}
