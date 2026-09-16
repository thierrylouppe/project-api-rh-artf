<?php

namespace App\Models;

use App\Enums\ModeCalculPaieElement;
use App\Enums\NaturePaieElement;
use App\Enums\PeriodicitePaieElement;
use App\Enums\SensPaieElement;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class PaieElement extends Model
{
    use HasFilterScope;

    protected $table = 'paie_elements';

    protected $fillable = [
        'code',
        'libelle',
        'nature',
        'sens',
        'periodicite',
        'mode_calcul',
        'montant_defaut',
        'taux_defaut',
        'article_ccn',
        'fonction_sigles',
        'mois_declenchement',
        'actif',
        'systeme',
    ];

    protected $casts = [
        'nature' => NaturePaieElement::class,
        'sens' => SensPaieElement::class,
        'periodicite' => PeriodicitePaieElement::class,
        'mode_calcul' => ModeCalculPaieElement::class,
        'montant_defaut' => 'decimal:2',
        'taux_defaut' => 'decimal:4',
        'fonction_sigles' => 'array',
        'mois_declenchement' => 'array',
        'actif' => 'boolean',
        'systeme' => 'boolean',
    ];

    protected array $filterable = ['code', 'nature', 'actif', 'systeme', 'periodicite', 'mode_calcul'];

    protected $attributes = [
        'actif' => true,
        'systeme' => false,
    ];

    public function aParametrer(): bool
    {
        if ($this->mode_calcul === ModeCalculPaieElement::MONTANT_FIXE) {
            return $this->montant_defaut === null;
        }

        if ($this->mode_calcul === ModeCalculPaieElement::POURCENTAGE_BASE) {
            return $this->taux_defaut === null;
        }

        return false;
    }
}
