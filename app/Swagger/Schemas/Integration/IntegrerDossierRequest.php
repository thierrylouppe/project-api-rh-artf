<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'IntegrerDossierRequest',
    properties: [
        new OA\Property(
            property: 'numero_cnss',
            type: 'string',
            nullable: true,
            example: '101234567',
            description: 'Art. 47 — obligatoire à l\'intégration CDI/CDD si agent.numero_cnss est vide'
        ),
    ]
)]
class IntegrerDossierRequest {}
