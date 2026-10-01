<?php

namespace App\Models;

use App\Enums\StatutDemandeConge;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class TypeConge extends Model
{
    use HasFilterScope;

    protected $table = 'type_conges';

    protected $fillable = [
        'nom',
        'description',
        'jours_max',
        'necessite_n1',
        'necessite_rh',
        'necessite_dg',
        'debite_solde',
        'justificatif_requis',
    ];

    protected $casts = [
        'necessite_n1'         => 'boolean',
        'necessite_rh'         => 'boolean',
        'necessite_dg'         => 'boolean',
        'debite_solde'         => 'boolean',
        'justificatif_requis'  => 'boolean',
    ];

    protected array $filterable = ['nom'];

    public function statutAttenduPourN1(): StatutDemandeConge
    {
        return StatutDemandeConge::SOUMISE;
    }

    public function statutAttenduPourRH(): StatutDemandeConge
    {
        return $this->necessite_n1
            ? StatutDemandeConge::VALIDEE_N1
            : StatutDemandeConge::SOUMISE;
    }

    public function statutAttenduPourDG(): StatutDemandeConge
    {
        if ($this->necessite_rh) {
            return StatutDemandeConge::VALIDEE_RH;
        }

        return $this->necessite_n1
            ? StatutDemandeConge::VALIDEE_N1
            : StatutDemandeConge::SOUMISE;
    }

    public function estAccordee(StatutDemandeConge $statut): bool
    {
        if ($this->necessite_dg) {
            return $statut === StatutDemandeConge::VALIDEE_DG;
        }

        if ($this->necessite_rh) {
            return $statut === StatutDemandeConge::VALIDEE_RH;
        }

        return $statut === StatutDemandeConge::VALIDEE_N1;
    }

    public function prochaineEtape(?StatutDemandeConge $statut): ?string
    {
        $statut ??= StatutDemandeConge::SOUMISE;

        if ($statut === StatutDemandeConge::SOUMISE) {
            if ($this->necessite_n1) {
                return 'valider-n1';
            }
            if ($this->necessite_rh) {
                return 'valider-rh';
            }
            if ($this->necessite_dg) {
                return 'valider-dg';
            }
        }

        if ($statut === StatutDemandeConge::VALIDEE_N1) {
            if ($this->necessite_rh) {
                return 'valider-rh';
            }
            if ($this->necessite_dg) {
                return 'valider-dg';
            }
        }

        if ($statut === StatutDemandeConge::VALIDEE_RH && $this->necessite_dg) {
            return 'valider-dg';
        }

        return null;
    }
}
