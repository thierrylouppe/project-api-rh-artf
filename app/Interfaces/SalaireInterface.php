<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface SalaireInterface extends BaseInterface
{
    /** Supprime toute la grille puis insère les nouvelles lignes en bulk. */
    public function generateGrille(array $salaries): void;

    /** Liste complète avec eager-loading classe → categorie + grade. */
    public function getAllWithClasse(): Collection;
}
