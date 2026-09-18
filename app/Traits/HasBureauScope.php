<?php

namespace App\Traits;

use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Vague F — Cloisonnement par bureau DRHL.
 *
 * Ce trait ajoute le scope `maStructure` aux modèles dont les listes
 * doivent être filtrées par le bureau de rattachement de l'utilisateur.
 *
 * Prérequis : le modèle doit avoir une relation `affectationActive` (morphTo)
 * qui expose les colonnes `structurable_type` / `structurable_id`.
 *
 * Niveaux de périmètre (du plus restrictif au plus large) :
 *   - 'bureau'    : uniquement les agents affectés au bureau exact de l'utilisateur
 *   - 'service'   : agents du bureau + ceux affectés au service parent (défaut)
 *   - 'direction' : agents du bureau + service + direction parente
 */
trait HasBureauScope
{
    /**
     * Scope `maStructure`.
     *
     * Appelé par le middleware ScopeByBureau avec l'utilisateur authentifié.
     * Si $user n'est pas cloisonné (bureau_id = null), aucun filtre n'est appliqué.
     *
     * @param  Builder   $query
     * @param  User|null $user   Utilisateur authentifié (null = pas de filtre)
     * @param  string    $niveau 'bureau' | 'service' | 'direction' (défaut : 'service')
     */
    /**
     * Scope `parMaStructure`.
     *
     * À utiliser sur les modèles qui ont une relation `agent()` (BelongsTo).
     * Filtre les lignes dont l'agent appartient à la structure de l'utilisateur.
     *
     * Usage : DemandeConge, Absence, Sanction, Evaluation, etc.
     *
     * @param  Builder   $query
     * @param  User|null $user   Utilisateur authentifié
     * @param  string    $niveau 'bureau' | 'service' | 'direction' (défaut : 'service')
     */
    public function scopeParMaStructure(
        Builder $query,
        ?User $user,
        string $niveau = 'service'
    ): Builder {
        if ($user === null || $user->voitPersonnelGlobal()) {
            return $query;
        }

        return $query->whereHas(
            'agent',
            fn (Builder $agentQuery) => $agentQuery->maStructure($user, $niveau)
        );
    }

    /**
     * Scope `maStructure`.
     *
     * @param  Builder   $query
     * @param  User|null $user   Utilisateur authentifié (null = pas de filtre)
     * @param  string    $niveau 'bureau' | 'service' | 'direction' (défaut : 'service')
     */
    public function scopeMaStructure(
        Builder $query,
        ?User $user,
        string $niveau = 'service'
    ): Builder {
        // Pas de cloisonnement : admin / DG / métier RH transverse (`consulter-agents-global`)
        if ($user === null || $user->voitPersonnelGlobal()) {
            return $query;
        }

        $bureau = Bureau::with('service.direction')->find($user->bureau_id);
        if (! $bureau) {
            return $query;
        }

        $service   = $bureau->service;
        $direction = $service?->direction;

        $bureauIds    = [$bureau->id];
        $serviceIds   = $service   ? [$service->id]   : [];
        $directionIds = $direction ? [$direction->id] : [];

        // Chef de service : tous les bureaux de son service
        if ($niveau === 'service' && $service) {
            $bureauIds  = Bureau::where('service_id', $service->id)->pluck('id')->all();
            $serviceIds = [$service->id];
        }

        // Directeur : tous les services et bureaux de sa direction
        if ($niveau === 'direction' && $direction) {
            $serviceIds   = Service::where('direction_id', $direction->id)->pluck('id')->all();
            $bureauIds    = Bureau::whereIn('service_id', $serviceIds)->pluck('id')->all();
            $directionIds = [$direction->id];
        }

        $morphBureau    = Bureau::class;
        $morphService   = Service::class;
        $morphDirection = Direction::class;

        return $query->whereHas('affectationActive', function (Builder $q) use (
            $niveau,
            $bureauIds, $serviceIds, $directionIds,
            $morphBureau, $morphService, $morphDirection
        ) {
            $q->where(function (Builder $sub) use (
                $niveau,
                $bureauIds, $serviceIds, $directionIds,
                $morphBureau, $morphService, $morphDirection
            ) {
                // Toujours : bureau exact
                $sub->where(fn (Builder $b) => $b
                    ->where('structurable_type', $morphBureau)
                    ->whereIn('structurable_id', $bureauIds)
                );

                if ($niveau === 'service' || $niveau === 'direction') {
                    if ($serviceIds) {
                        $sub->orWhere(fn (Builder $s) => $s
                            ->where('structurable_type', $morphService)
                            ->whereIn('structurable_id', $serviceIds)
                        );
                    }
                }

                if ($niveau === 'direction') {
                    if ($directionIds) {
                        $sub->orWhere(fn (Builder $d) => $d
                            ->where('structurable_type', $morphDirection)
                            ->whereIn('structurable_id', $directionIds)
                        );
                    }
                }
            });
        });
    }
}
