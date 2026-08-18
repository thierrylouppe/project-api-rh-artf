<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginResponse',
    properties: [
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                new OA\Property(property: 'token', type: 'string', example: '1|xxxxxxxxxxxxxxxx'),
            ],
            type: 'object'
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Connexion réussie'),
    ]
)]
class LoginResponse {}
