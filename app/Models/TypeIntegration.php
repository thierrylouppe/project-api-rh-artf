<?php

namespace App\Models;

use App\Enums\TypeActeAdministratif;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TypeIntegration extends Model
{
    use HasFilterScope;

    protected $table = 'type_integrations';

    protected $fillable = [
        'nom',
        'description',
        'type_acte_administratif',
        'necessite_contrat',
        'necessite_validation_dg',
        'necessite_compte_utilisateur',
        'prefixe_matricule',
        'duree_max_mois',
    ];

    protected $casts = [
        'necessite_contrat'            => 'boolean',
        'necessite_validation_dg'      => 'boolean',
        'necessite_compte_utilisateur' => 'boolean',
    ];

    public function documentsObligatoires(): BelongsToMany
    {
        return $this->belongsToMany(
            TypeDocument::class,
            'type_integration_type_document',
            'type_integration_id',
            'type_document_id'
        );
    }

    /**
     * Un type d'intégration est un stage si son nom commence par « Stage ».
     * Cette convention permet de brancher le hook ConventionStage sans migration supplémentaire.
     */
    public function estUnStage(): bool
    {
        return str_starts_with($this->nom, 'Stage');
    }

    protected array $filterable = ['nom'];

    /**
     * Retourne l'enum TypeActeAdministratif associé à ce type d'intégration.
     * Retourne null si aucun acte n'est défini (ne devrait pas arriver en production).
     */
    public function acteAdministratifEnum(): ?TypeActeAdministratif
    {
        if ($this->type_acte_administratif === null) {
            return null;
        }

        return TypeActeAdministratif::tryFrom($this->type_acte_administratif);
    }
}
