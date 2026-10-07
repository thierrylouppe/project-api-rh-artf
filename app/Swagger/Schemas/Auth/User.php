<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Administrateur'),
        new OA\Property(property: 'email', type: 'string', example: 'admin@artf.cg'),
        new OA\Property(property: 'agent_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'bureau_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'vue_personnel', type: 'string', example: 'service', enum: ['globale', 'direction', 'service', 'bureau']),
        new OA\Property(
            property: 'bureau',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'nom', type: 'string', example: 'Bureau Personnel'),
                new OA\Property(property: 'sigle', type: 'string', nullable: true, example: 'B.P'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'structure',
            description: 'Structure du périmètre (nom complet), selon vue_personnel. Null si vue globale.',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 2),
                new OA\Property(property: 'nom', type: 'string', example: 'Service RH'),
                new OA\Property(property: 'sigle', type: 'string', nullable: true, example: 'S.R.H'),
                new OA\Property(property: 'type', type: 'string', enum: ['direction', 'service', 'bureau']),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'fonction',
            description: 'Fonction de l\'agent rattaché.',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 4),
                new OA\Property(property: 'nom', type: 'string', example: 'Chef de service'),
                new OA\Property(property: 'sigle', type: 'string', nullable: true, example: 'C.S'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'roles',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Role')
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class User {}
