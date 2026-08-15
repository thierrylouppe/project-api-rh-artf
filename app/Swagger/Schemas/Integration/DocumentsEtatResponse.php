<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DocumentsEtatResponse',
    properties: [
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(
                    property: 'deposes',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/DocumentDossier')
                ),
                new OA\Property(
                    property: 'manquants',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'est_obligatoire', type: 'boolean'),
                        ],
                        type: 'object'
                    )
                ),
                new OA\Property(
                    property: 'resume',
                    type: 'object',
                    additionalProperties: true
                ),
            ],
            type: 'object'
        ),
    ]
)]
class DocumentsEtatResponse {}
