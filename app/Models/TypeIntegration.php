<?php

namespace App\Models;

use App\Enums\TypeActeAdministratif;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class TypeIntegration extends Model
{
    use HasFilterScope;

    protected $table = 'type_integrations';

    protected $fillable = [
        'nom',
        'description',
        'type_acte_administratif',
        'necessite_contrat',
    ];

    protected $casts = [
        'necessite_contrat' => 'boolean',
    ];

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
