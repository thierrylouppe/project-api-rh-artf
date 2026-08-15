<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@arft.cg'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Admin@2026'),
    ]
)]
class LoginRequest {}
