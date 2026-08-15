<?php

namespace App\Swagger\Schemas\Referentiels;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TypeIntegration',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nom', type: 'string', example: 'Recrutement externe'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'type_acte_administratif', type: 'string', example: 'decision_recrutement'),
        new OA\Property(property: 'necessite_contrat', type: 'boolean', example: true),
        new OA\Property(property: 'necessite_validation_dg', type: 'boolean', example: true),
        new OA\Property(property: 'necessite_compte_utilisateur', type: 'boolean', example: true),
        new OA\Property(property: 'prefixe_matricule', type: 'string', nullable: true, example: 'ARTF'),
        new OA\Property(property: 'duree_max_mois', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class TypeIntegration {}
