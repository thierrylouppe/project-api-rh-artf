<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Nomination',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'agent_id', type: 'integer', example: 1),
        new OA\Property(property: 'poste', type: 'string', example: 'Chef de Service'),
        new OA\Property(property: 'structurable_type', type: 'string', example: 'App\\Models\\Service'),
        new OA\Property(property: 'structurable_id', type: 'integer', example: 1),
        new OA\Property(property: 'date_debut', type: 'string', format: 'date'),
        new OA\Property(property: 'date_fin', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'type_acte', type: 'string', nullable: true, example: 'decision'),
        new OA\Property(property: 'statut', type: 'string', example: 'en_attente', enum: ['en_attente', 'approuvee', 'active', 'cloturee', 'rejetee']),
        new OA\Property(property: 'statut_label', type: 'string', example: 'En attente de validation'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Nomination {}
