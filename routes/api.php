<?php

use App\Http\Controllers\API\ClassegrillesalarialeController;
use App\Http\Controllers\API\ParametregrileController;
use App\Http\Controllers\API\SalaireController;
use App\Http\Controllers\API\AdministrationController;
use App\Http\Controllers\API\AuditLogController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BureauController;
use App\Http\Controllers\API\CategorieController;
use App\Http\Controllers\API\DirectionController;
use App\Http\Controllers\API\DiplomeController;
use App\Http\Controllers\API\EchelonController;
use App\Http\Controllers\API\FonctionController;
use App\Http\Controllers\API\GradeController;
use App\Http\Controllers\API\LocaliteController;
use App\Http\Controllers\API\MotifAdministratifController;
use App\Http\Controllers\API\ParametreApplicationController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TypeAbsenceController;
use App\Http\Controllers\API\TypeCongeController;
use App\Http\Controllers\API\TypeContratController;
use App\Http\Controllers\API\TypeDocumentController;
use App\Http\Controllers\API\TypeRecrutementController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

// ============================================================
// MODULE 1.1 — STRUCTURE ORGANISATIONNELLE
// ============================================================
Route::apiResource('localites', LocaliteController::class);

Route::apiResource('administrations', AdministrationController::class);
Route::get('localites/{localite}/administrations', [AdministrationController::class, 'byLocalite']);

Route::apiResource('directions', DirectionController::class);
Route::get('administrations/{administration}/directions', [DirectionController::class, 'byAdministration']);

Route::apiResource('services', ServiceController::class);
Route::get('directions/{direction}/services', [ServiceController::class, 'byDirection']);

Route::apiResource('bureaux', BureauController::class)->parameters(['bureaux' => 'bureau']);
Route::get('services/{service}/bureaux', [BureauController::class, 'byService']);

// ============================================================
// MODULE 1.2 — RÉFÉRENTIELS RH
// ============================================================
Route::apiResource('diplomes', DiplomeController::class);
Route::apiResource('grades', GradeController::class);
Route::apiResource('categories', CategorieController::class);
Route::apiResource('echelons', EchelonController::class);
Route::apiResource('fonctions', FonctionController::class);
Route::apiResource('types-contrats', TypeContratController::class);
Route::apiResource('types-documents', TypeDocumentController::class);
Route::apiResource('types-recrutements', TypeRecrutementController::class);
Route::apiResource('types-absences', TypeAbsenceController::class);
Route::apiResource('types-conges', TypeCongeController::class);
Route::apiResource('motifs-administratifs', MotifAdministratifController::class);

// ============================================================
// MODULE GRILLE SALARIALE
// ============================================================
Route::apiResource('grille-classes', ClassegrillesalarialeController::class)
    ->parameters(['grille-classes' => 'classegrillesalariale']);

Route::get('grille-parametres/current', [ParametregrileController::class, 'current']);
Route::put('grille-parametres/{parametregrile}', [ParametregrileController::class, 'update']);

Route::get('salaires', [SalaireController::class, 'index']);
Route::post('salaires/generation', [SalaireController::class, 'generate']);

// ============================================================
// MODULE 1.3 — AUTH & ADMINISTRATION SYSTÈME
// ============================================================
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::apiResource('users', UserController::class)->middleware([
        'index' => 'permission:consulter-utilisateurs',
        'store' => 'permission:creer-utilisateurs',
        'show' => 'permission:consulter-utilisateurs',
        'update' => 'permission:modifier-utilisateurs',
        'destroy' => 'permission:supprimer-utilisateurs',
    ]);

    Route::apiResource('roles', RoleController::class)->middleware([
        'index' => 'permission:consulter-roles',
        'store' => 'permission:creer-roles',
        'show' => 'permission:consulter-roles',
        'update' => 'permission:modifier-roles',
        'destroy' => 'permission:supprimer-roles',
    ]);
    Route::post('roles/{role}/dupliquer', [RoleController::class, 'dupliquer'])
        ->middleware('permission:creer-roles');

    Route::get('/permissions', [PermissionController::class, 'index'])
        ->middleware('permission:consulter-roles');
    Route::post('roles/{role}/permissions', [PermissionController::class, 'assignToRole'])
        ->middleware('permission:modifier-roles');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('role:admin');

    Route::apiResource('parametres-application', ParametreApplicationController::class)
        ->middleware('role:admin');
});
