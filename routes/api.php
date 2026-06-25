<?php

use App\Http\Controllers\API\ActeAdministratifController;
use App\Http\Controllers\API\ConventionStageController;
use App\Http\Controllers\API\AffectationController;
use App\Http\Controllers\API\AgentController;
use App\Http\Controllers\API\CompteIntegrationController;
use App\Http\Controllers\API\ContratController;
use App\Http\Controllers\API\DocumentDossierController;
use App\Http\Controllers\API\DossierIntegrationController;
use App\Http\Controllers\API\NominationController;
use App\Http\Controllers\API\PriseDeServiceController;
use App\Http\Controllers\API\RemiseMaterielController;
use App\Http\Controllers\API\ValidationWorkflowController;
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
use App\Http\Controllers\API\TypeIntegrationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

// ============================================================
// MODULE 2 — INTÉGRATION ADMINISTRATIVE DES AGENTS
// ============================================================
Route::prefix('integration')->middleware('auth:sanctum')->group(function () {

    // — Agents ————————————————————————————————————————————
    Route::apiResource('agents', AgentController::class);
    Route::get('agents/{agent}/contrats', [ContratController::class, 'byAgent']);
    Route::get('agents/{agent}/affectations', [AffectationController::class, 'byAgent']);
    Route::get('agents/{agent}/nominations', [NominationController::class, 'byAgent']);
    Route::get('agents/{agent}/remises-materiel', [RemiseMaterielController::class, 'byAgent']);
    Route::get('agents/{agent}/compte', [CompteIntegrationController::class, 'byAgent']);

    // — Dossiers d'intégration ————————————————————————————
    Route::apiResource('dossiers', DossierIntegrationController::class);
    Route::post('dossiers/{dossier}/soumettre',           [DossierIntegrationController::class, 'soumettre']);
    Route::post('dossiers/{dossier}/passer-en-etude-rh',  [DossierIntegrationController::class, 'passerEnEtudeRH']);
    Route::post('dossiers/{dossier}/marquer-incomplet',   [DossierIntegrationController::class, 'marquerIncomplet']);
    Route::post('dossiers/{dossier}/marquer-complet',     [DossierIntegrationController::class, 'marquerComplet']);
    Route::post('dossiers/{dossier}/valider-rh',          [DossierIntegrationController::class, 'validerRH']);
    Route::post('dossiers/{dossier}/rejeter-rh',          [DossierIntegrationController::class, 'rejeterRH']);
    Route::post('dossiers/{dossier}/valider-dg',          [DossierIntegrationController::class, 'validerDG']);
    Route::post('dossiers/{dossier}/generer-acte',        [DossierIntegrationController::class, 'genererActe']);
    Route::post('dossiers/{dossier}/assigner-matricule',  [DossierIntegrationController::class, 'assignerMatricule']);
    Route::post('dossiers/{dossier}/marquer-acte-genere', [DossierIntegrationController::class, 'marquerActeGenere']);
    Route::post('dossiers/{dossier}/marquer-contrat-signe', [DossierIntegrationController::class, 'marquerContratSigne']);
    Route::post('dossiers/{dossier}/suspendre',           [DossierIntegrationController::class, 'suspendre']);
    Route::post('dossiers/{dossier}/annuler',             [DossierIntegrationController::class, 'annuler']);
    Route::get('dossiers/{dossier}/historique',           [DossierIntegrationController::class, 'historique']);

    // — Documents du dossier ——————————————————————————————
    Route::post('dossiers/{dossier}/documents',            [DocumentDossierController::class, 'store']);
    Route::get('dossiers/{dossier}/documents',             [DocumentDossierController::class, 'parDossier']);
    Route::post('documents/{document}/valider',            [DocumentDossierController::class, 'valider']);
    Route::delete('documents/{document}',                  [DocumentDossierController::class, 'destroy']);

    // — Circuit de validation ——————————————————————————————
    Route::get('dossiers/{dossier}/circuit',              [ValidationWorkflowController::class, 'circuit']);
    Route::post('validations/{validation}/approuver',     [ValidationWorkflowController::class, 'approuver']);
    Route::post('validations/{validation}/rejeter',       [ValidationWorkflowController::class, 'rejeter']);
    Route::post('validations/{validation}/renvoyer',      [ValidationWorkflowController::class, 'renvoyer']);

    // — Actes administratifs ——————————————————————————————
    Route::get('dossiers/{dossier}/actes',                [ActeAdministratifController::class, 'byDossier']);
    Route::post('dossiers/{dossier}/actes',               [ActeAdministratifController::class, 'generer']);
    Route::post('actes/{acte}/signer',                    [ActeAdministratifController::class, 'signer']);

    // — Contrats ——————————————————————————————————————————
    Route::apiResource('contrats', ContratController::class)->only(['index', 'store', 'show']);
    Route::post('contrats/{contrat}/resilier',            [ContratController::class, 'resilier']);

    // — Affectations ——————————————————————————————————————
    Route::apiResource('affectations', AffectationController::class)->only(['index', 'store', 'show']);
    Route::post('affectations/{affectation}/activer',     [AffectationController::class, 'activer']);
    Route::post('affectations/{affectation}/rejeter',     [AffectationController::class, 'rejeter']);
    Route::post('affectations/{affectation}/terminer',    [AffectationController::class, 'terminer']);

    // — Nominations ———————————————————————————————————————
    Route::apiResource('nominations', NominationController::class)->only(['index', 'store', 'show']);
    Route::post('nominations/{nomination}/activer',       [NominationController::class, 'activer']);
    Route::post('nominations/{nomination}/cloturer',      [NominationController::class, 'cloturer']);
    Route::post('nominations/{nomination}/rejeter',       [NominationController::class, 'rejeter']);

    // — Comptes utilisateurs ——————————————————————————————
    Route::post('comptes/provisionner',                   [CompteIntegrationController::class, 'provisionner']);

    // — Remises de matériel ———————————————————————————————
    Route::apiResource('remises-materiel', RemiseMaterielController::class)
        ->only(['index', 'store', 'show'])
        ->parameters(['remises-materiel' => 'remise']);

    // — Prises de service — étape finale ———————————————————
    Route::post('prises-de-service',                              [PriseDeServiceController::class, 'store']);
    Route::post('dossiers/{dossier}/integrer',                    [PriseDeServiceController::class, 'integrer']);

    // — Stages (ConventionStage) ———————————————————————————
    Route::get('stages',                                          [ConventionStageController::class, 'index']);
    Route::get('stages/{stage}',                                  [ConventionStageController::class, 'show']);
    Route::patch('stages/{stage}/prolonger',                      [ConventionStageController::class, 'prolonger']);
    Route::post('stages/{stage}/cloturer',                        [ConventionStageController::class, 'cloturer']);
    Route::get('stages/{stage}/attestation',                      [ConventionStageController::class, 'attestation']);
});

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
Route::apiResource('types-integrations', TypeIntegrationController::class);
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
