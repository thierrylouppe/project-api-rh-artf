<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AgentIdentite',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'matricule', type: 'string', nullable: true, example: 'ARTF-2026-001'),
        new OA\Property(property: 'nom', type: 'string', example: 'MOUKOKO'),
        new OA\Property(property: 'prenom', type: 'string', example: 'Jean'),
        new OA\Property(property: 'nom_complet', type: 'string', example: 'Jean MOUKOKO'),
    ]
)]
class AgentIdentite {}
