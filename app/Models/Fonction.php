<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class Fonction extends Model
{
    use HasFilterScope;

    protected $fillable = ['nom', 'sigle', 'description'];

    protected array $filterable = ['nom'];

    /** Annexe 1 art. 2 — salaire fonctionnel, hors grille indiciaire. */
    public const SIGLES_HORS_GRILLE = ['DG', 'DC', 'DD'];

    public const NOMS_HORS_GRILLE = [
        'Directeur Général',
        'Directeur Central',
        'Directeur Départemental',
    ];

    public function estHorsGrille(): bool
    {
        return in_array($this->sigle, self::SIGLES_HORS_GRILLE, true);
    }

    public static function estNomHorsGrille(?string $nom): bool
    {
        return $nom !== null && in_array($nom, self::NOMS_HORS_GRILLE, true);
    }
}
