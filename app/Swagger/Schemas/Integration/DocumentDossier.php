<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DocumentDossier',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'dossier_integration_id', type: 'integer', example: 1),
        new OA\Property(property: 'type_document_id', type: 'integer', example: 2),
        new OA\Property(property: 'nom_original', type: 'string', example: 'cv.pdf'),
        new OA\Property(property: 'chemin_fichier', type: 'string'),
        new OA\Property(property: 'est_obligatoire', type: 'boolean', example: true),
        new OA\Property(property: 'est_valide', type: 'boolean', example: false),
        new OA\Property(property: 'date_validation', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'commentaire', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class DocumentDossier {}
