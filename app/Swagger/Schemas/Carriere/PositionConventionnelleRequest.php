<?php

namespace App\Swagger\Schemas\Carriere;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PositionConventionnelleRequest',
    required: ['agent_id', 'type', 'date_debut', 'date_fin'],
    properties: [
        new OA\Property(property: 'agent_id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', enum: ['detachement', 'disponibilite', 'position_exceptionnelle', 'sous_le_drapeau']),
        new OA\Property(property: 'date_debut', type: 'string', format: 'date', example: '2026-10-01'),
        new OA\Property(property: 'date_fin', type: 'string', format: 'date', example: '2028-09-30'),
        new OA\Property(property: 'organisme_accueil', type: 'string', nullable: true, example: 'Ministère des Finances'),
        new OA\Property(property: 'consentement_agent', type: 'boolean', example: true),
        new OA\Property(property: 'detachement_office', type: 'boolean', example: false),
        new OA\Property(property: 'commentaire', type: 'string', nullable: true),
        new OA\Property(property: 'piece_path', type: 'string', nullable: true),
    ]
)]
class PositionConventionnelleRequest {}
