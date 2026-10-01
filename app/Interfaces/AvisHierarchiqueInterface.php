<?php

namespace App\Interfaces;

use App\Models\AvisHierarchique;
use Illuminate\Support\Collection;

interface AvisHierarchiqueInterface extends BaseInterface
{
    /** Tous les avis d'une fiche, ordonnés par `ordre`. */
    public function parEvaluation(int $evaluationId): Collection;

    /** Trouver un avis précis (évaluation + niveau). */
    public function trouverParNiveau(int $evaluationId, string $niveau): ?AvisHierarchique;

    /**
     * Tous les avis requis d'une fiche sont-ils signés ?
     * @param array<string> $niveauxRequis
     */
    public function tousNiveauxSignes(int $evaluationId, array $niveauxRequis): bool;
}
