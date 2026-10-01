<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ActeAdministratif',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'dossier_integration_id', type: 'integer', example: 1),
        new OA\Property(property: 'type_acte', type: 'string', example: 'decision_recrutement'),
        new OA\Property(property: 'type_acte_label', type: 'string', example: 'Décision de recrutement'),
        new OA\Property(property: 'numero', type: 'string', example: 'ACTE-2026-0001'),
        new OA\Property(property: 'contenu', type: 'string', nullable: true),
        new OA\Property(property: 'fichier_path', type: 'string', nullable: true),
        new OA\Property(property: 'signe', type: 'boolean', example: false),
        new OA\Property(property: 'date_signature', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class ActeAdministratif {}
