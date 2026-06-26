<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Format uniforme pour toutes les requêtes API (Accept: application/json ou préfixe /api)
        $isApi = fn (Request $req): bool =>
            $req->expectsJson() || str_starts_with($req->getPathInfo(), '/api');

        // 401 — Non authentifié
        $exceptions->render(function (AuthenticationException $e, Request $req) use ($isApi) {
            if ($isApi($req)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié. Veuillez vous connecter.',
                ], 401);
            }
        });

        // 403 — Accès refusé
        $exceptions->render(function (AccessDeniedHttpException $e, Request $req) use ($isApi) {
            if ($isApi($req)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Action non autorisée.',
                ], 403);
            }
        });

        // 404 — Ressource introuvable (ModelNotFoundException + route inconnue)
        $exceptions->render(function (ModelNotFoundException $e, Request $req) use ($isApi) {
            if ($isApi($req)) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'message' => "{$model} introuvable.",
                ], 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $req) use ($isApi) {
            if ($isApi($req)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Route ou ressource introuvable.',
                ], 404);
            }
        });

        // 422 — Erreurs de validation FormRequest
        $exceptions->render(function (ValidationException $e, Request $req) use ($isApi) {
            if ($isApi($req)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les données fournies sont invalides.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // Toute autre HttpException (abort(xxx, 'message'))
        $exceptions->render(function (HttpException $e, Request $req) use ($isApi) {
            if ($isApi($req)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Une erreur est survenue.',
                ], $e->getStatusCode());
            }
        });

    })->create();
