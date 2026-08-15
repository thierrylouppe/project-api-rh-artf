<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Agent',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'matricule', type: 'string', nullable: true, example: 'ARTF-2026-001'),
        new OA\Property(property: 'nom', type: 'string', example: 'MOUKOKO'),
        new OA\Property(property: 'prenom', type: 'string', example: 'Jean'),
        new OA\Property(property: 'nom_complet', type: 'string', example: 'Jean MOUKOKO'),
        new OA\Property(property: 'date_naissance', type: 'string', format: 'date', example: '1990-05-12'),
        new OA\Property(property: 'lieu_naissance', type: 'string', nullable: true),
        new OA\Property(property: 'nationalite', type: 'string', nullable: true, example: 'Congolaise'),
        new OA\Property(property: 'genre', type: 'string', enum: ['M', 'F'], example: 'M'),
        new OA\Property(property: 'telephone', type: 'string', nullable: true),
        new OA\Property(property: 'email_personnel', type: 'string', nullable: true),
        new OA\Property(property: 'email_professionnel', type: 'string', nullable: true),
        new OA\Property(property: 'statut', type: 'string', example: 'EN_COURS_INTEGRATION'),
        new OA\Property(property: 'type_integration_id', type: 'integer', example: 1),
        new OA\Property(property: 'grade_id', type: 'integer', nullable: true),
        new OA\Property(property: 'categorie_id', type: 'integer', nullable: true),
        new OA\Property(property: 'echelon_id', type: 'integer', nullable: true),
        new OA\Property(property: 'fonction_id', type: 'integer', nullable: true),
        new OA\Property(property: 'date_prise_service', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Agent {}
