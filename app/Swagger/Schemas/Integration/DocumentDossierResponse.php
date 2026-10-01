<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DocumentDossierResponse',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/DocumentDossier'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
    ]
)]
class DocumentDossierResponse {}
