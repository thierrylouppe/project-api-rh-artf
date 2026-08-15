<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginResponse',
    properties: [
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Administrateur'),
                new OA\Property(property: 'email', type: 'string', example: 'admin@arft.cg'),
                new OA\Property(property: 'agent_id', type: 'integer', nullable: true, example: null),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'token', type: 'string', example: '1|xxxxxxxxxxxxxxxx'),
            ],
            type: 'object'
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Connexion réussie'),
    ]
)]
class LoginResponse {}
