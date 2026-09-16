<?php

namespace App\Swagger\Schemas\Integration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Contrat',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'agent_id', type: 'integer', example: 1),
        new OA\Property(property: 'type_contrat_id', type: 'integer', example: 1),
        new OA\Property(property: 'fonction_id', type: 'integer', nullable: true),
        new OA\Property(property: 'dossier_integration_id', type: 'integer', nullable: true),
        new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-09-01'),
        new OA\Property(property: 'date_fin', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'remuneration', type: 'number', nullable: true, example: 350000),
        new OA\Property(property: 'statut', type: 'string', example: 'actif'),
        new OA\Property(property: 'lieu_recrutement', type: 'string', nullable: true, example: 'Brazzaville'),
        new OA\Property(
            property: 'mentions',
            nullable: true,
            properties: [
                new OA\Property(property: 'noms', type: 'string'),
                new OA\Property(property: 'nationalite', type: 'string', nullable: true),
                new OA\Property(property: 'date_naissance', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'lieu_naissance', type: 'string', nullable: true),
                new OA\Property(property: 'sexe', type: 'string', nullable: true),
                new OA\Property(property: 'situation_matrimoniale', type: 'string', nullable: true),
                new OA\Property(property: 'date_recrutement', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'lieu_recrutement', type: 'string', nullable: true),
                new OA\Property(property: 'emploi', type: 'string', nullable: true),
                new OA\Property(property: 'remuneration', type: 'number', nullable: true),
                new OA\Property(property: 'lieu_travail', type: 'string', nullable: true, description: 'Nom de la structure d\'affectation active'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'essai',
            properties: [
                new OA\Property(property: 'statut', type: 'string', example: 'en_cours'),
                new OA\Property(property: 'statut_label', type: 'string', example: 'En cours'),
                new OA\Property(property: 'duree_mois', type: 'integer', example: 1),
                new OA\Property(property: 'date_debut', type: 'string', format: 'date'),
                new OA\Property(property: 'date_fin', type: 'string', format: 'date'),
                new OA\Property(property: 'renouvele', type: 'boolean', example: false),
                new OA\Property(property: 'date_confirmation', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'prochaine_etape', type: 'string', nullable: true, example: 'confirmer-essai'),
                new OA\Property(property: 'peut_renouveler', type: 'boolean', example: true),
            ],
            type: 'object'
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Contrat {}
