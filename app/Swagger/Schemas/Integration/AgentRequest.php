<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AgentRequest',
    required: ['nom', 'prenom', 'date_naissance', 'genre', 'type_integration_id'],
    properties: [
        new OA\Property(property: 'nom', type: 'string', example: 'MOUKOKO'),
        new OA\Property(property: 'prenom', type: 'string', example: 'Jean'),
        new OA\Property(property: 'date_naissance', type: 'string', format: 'date', example: '1990-05-12'),
        new OA\Property(property: 'lieu_naissance', type: 'string', nullable: true, example: 'Brazzaville'),
        new OA\Property(property: 'nationalite', type: 'string', nullable: true, example: 'Congolaise'),
        new OA\Property(property: 'genre', type: 'string', enum: ['M', 'F'], example: 'M'),
        new OA\Property(property: 'telephone', type: 'string', nullable: true, example: '+242066123456'),
        new OA\Property(property: 'email_personnel', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'numero_cnss', type: 'string', nullable: true),
        new OA\Property(property: 'rib_bancaire', type: 'string', nullable: true),
        new OA\Property(property: 'diplome_id', type: 'integer', nullable: true),
        new OA\Property(property: 'grade_id', type: 'integer', nullable: true),
        new OA\Property(property: 'categorie_id', type: 'integer', nullable: true),
        new OA\Property(property: 'echelon_id', type: 'integer', nullable: true),
        new OA\Property(property: 'fonction_id', type: 'integer', nullable: true),
        new OA\Property(property: 'type_integration_id', type: 'integer', example: 1),
    ]
)]
class AgentRequest {}
