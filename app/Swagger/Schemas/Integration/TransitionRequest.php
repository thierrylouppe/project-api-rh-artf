<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TransitionRequest',
    properties: [
        new OA\Property(
            property: 'commentaire',
            type: 'string',
            nullable: true,
            maxLength: 1000,
            example: 'Pièces manquantes : CV et diplôme'
        ),
    ]
)]
class TransitionRequest {}
