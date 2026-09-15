<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        foreach (explode('|', $permission) as $name) {
            if ($user?->hasPermissionTo(trim($name), 'api')) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Accès refusé.'], 403);
    }
}
