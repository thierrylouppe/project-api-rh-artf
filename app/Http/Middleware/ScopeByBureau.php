<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vague F — Middleware de cloisonnement par bureau DRHL.
 *
 * Injecte le périmètre de l'utilisateur dans la requête sous la clé
 * `bureau_scope` afin que les Services / Repositories puissent appliquer
 * le scope Eloquent `maStructure` sans accéder directement à Auth.
 *
 * Utilisation dans les routes :
 *   ->middleware('scope.bureau')           // niveau auto (fonction de l'utilisateur)
 *   ->middleware('scope.bureau:direction') // niveau 'direction' (forcé)
 *   ->middleware('scope.bureau:bureau')    // niveau 'bureau' (forcé)
 *
 * Dans un Repository/Service :
 *   $user = $request->get('bureau_scope_user');  // User|null
 *   Agent::query()->maStructure($user)->get();
 *
 * Pas de blocage : si l'utilisateur n'a pas de bureau de rattachement
 * (admin, directeur-général), la requête passe sans filtre.
 */
class ScopeByBureau
{
    /** Niveaux valides : du plus restrictif au plus large. */
    private const NIVEAUX = ['bureau', 'service', 'direction'];

    public function handle(Request $request, Closure $next, string $niveau = 'auto'): Response
    {
        $user = $request->user();

        if ($niveau === 'auto' || ! in_array($niveau, self::NIVEAUX, true)) {
            $niveau = $user?->niveauCloisonnement() ?? 'service';
        }

        // Injecter l'utilisateur et le niveau souhaité dans la requête
        // pour que les couches inférieures puissent appliquer le scope.
        $request->merge([
            'bureau_scope_user'   => $user,
            'bureau_scope_niveau' => $niveau,
        ]);

        return $next($request);
    }
}
