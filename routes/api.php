<?php

use App\Http\Controllers\API\ActeAdministratifController;
use App\Http\Controllers\API\ConventionStageController;
use App\Http\Controllers\API\AffectationController;
use App\Http\Controllers\API\AgentController;
use App\Http\Controllers\API\CompteIntegrationController;
use App\Http\Controllers\API\ContratController;
use App\Http\Controllers\API\DocumentDossierController;
use App\Http\Controllers\API\DossierIntegrationController;
use App\Http\Controllers\API\CarriereAgentController;
use App\Http\Controllers\API\LotAffectationController;
use App\Http\Controllers\API\LotNominationController;
use App\Http\Controllers\API\AbsenceController;
use App\Http\Controllers\API\CongeSoldeController;
use App\Http\Controllers\API\DemandeCongeController;
use App\Http\Controllers\API\JourFerieController;
use App\Http\Controllers\API\RegleAcquisitionCongeController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\NominationController;
use App\Http\Controllers\API\PriseDeServiceController;
use App\Http\Controllers\API\RemiseMaterielController;
use App\Http\Controllers\API\ValidationWorkflowController;
use App\Http\Controllers\API\ClassegrillesalarialeController;
use App\Http\Controllers\API\ParametregrileController;
use App\Http\Controllers\API\SalaireAgentController;
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
use App\Http\Controllers\API\PersonnelController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TypeAbsenceController;
use App\Http\Controllers\API\TypeCongeController;
use App\Http\Controllers\API\TypeContratController;
use App\Http\Controllers\API\TypeDocumentController;
use App\Http\Controllers\API\CircuitValidationController;
use App\Http\Controllers\API\TypeIntegrationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

// ============================================================
// MODULE CARRIÈRE — situation administrative vivante
// (alias identiques sous /integration pour compatibilité FE)
// ============================================================
$routesCarriere = function (): void {
    Route::get('agents/{agent}/contrats', [ContratController::class, 'byAgent']);
    Route::get('agents/{agent}/affectations', [AffectationController::class, 'byAgent']);
    Route::get('agents/{agent}/nominations/historique', [NominationController::class, 'historique']);
    Route::get('agents/{agent}/nominations', [NominationController::class, 'byAgent']);
    Route::get('agents/{agent}/salaires/actuel', [SalaireAgentController::class, 'actuel'])
        ->middleware('permission:consulter-salaires');
    Route::get('agents/{agent}/salaires/historique', [SalaireAgentController::class, 'historique'])
        ->middleware('permission:consulter-salaires');
    Route::get('agents/{agent}/salaires/bulletin', [SalaireAgentController::class, 'bulletin'])
        ->middleware('permission:consulter-salaires');
    Route::post('agents/{agent}/salaires/avancer-echelon', [SalaireAgentController::class, 'avancerEchelon'])
        ->middleware('permission:gerer-salaires');
    Route::get('agents/{agent}/salaires', [SalaireAgentController::class, 'byAgent'])
        ->middleware('permission:consulter-salaires');

    Route::apiResource('contrats', ContratController::class)->only(['index', 'store', 'show']);
    Route::post('contrats/{contrat}/resilier', [ContratController::class, 'resilier']);

    Route::post('affectations/groupee',               [LotAffectationController::class, 'store']);
    Route::get('affectations/lots/{lot}',             [LotAffectationController::class, 'detail']);
    Route::post('affectations/lots/{lot}/activer',    [LotAffectationController::class, 'activer']);
    Route::post('affectations/lots/{lot}/rejeter',    [LotAffectationController::class, 'rejeter']);
    Route::get('affectations/lots/{lot}/acte',        [LotAffectationController::class, 'acte']);
    Route::post('affectations/notes-service/lot',     [AffectationController::class, 'noteServiceLot']);
    Route::apiResource('affectations', AffectationController::class)->only(['index', 'store', 'show']);
    Route::post('affectations/{affectation}/activer',     [AffectationController::class, 'activer']);
    Route::post('affectations/{affectation}/rejeter',     [AffectationController::class, 'rejeter']);
    Route::post('affectations/{affectation}/terminer',    [AffectationController::class, 'terminer']);
    Route::get('affectations/{affectation}/note-service', [AffectationController::class, 'noteService']);

    Route::get('nominations/postes-vacants', [NominationController::class, 'postesVacants']);
    Route::get('nominations/chefs/{chef}/agents-sous-autorite', [NominationController::class, 'agentsSousAutorite']);
    Route::post('nominations/groupee', [LotNominationController::class, 'store']);
    Route::get('nominations/lots/{lot}', [LotNominationController::class, 'detail']);
    Route::post('nominations/lots/{lot}/activer', [LotNominationController::class, 'activer']);
    Route::post('nominations/lots/{lot}/rejeter', [LotNominationController::class, 'rejeter']);
    Route::get('nominations/lots/{lot}/acte', [LotNominationController::class, 'acte']);
    Route::apiResource('nominations', NominationController::class)->only(['index', 'store', 'show', 'update']);
    Route::post('nominations/{nomination}/activer',       [NominationController::class, 'activer']);
    Route::post('nominations/{nomination}/cloturer',      [NominationController::class, 'cloturer']);
    Route::post('nominations/{nomination}/rejeter',       [NominationController::class, 'rejeter']);
    Route::get('nominations/{nomination}/acte',           [NominationController::class, 'acte']);
};

Route::prefix('carriere')->middleware('auth:sanctum')->group(function () use ($routesCarriere) {
    $routesCarriere();
    // Synthèse carrière uniquement ici : pas d'alias /integration (conflit avec GET /integration/agents/{id}).
    Route::get('agents/{agent}', [CarriereAgentController::class, 'synthese']);
});

// ============================================================
// MODULE 2 — INTÉGRATION ADMINISTRATIVE DES AGENTS
// ============================================================
Route::prefix('integration')->middleware('auth:sanctum')->group(function () use ($routesCarriere) {

    // — Agents ————————————————————————————————————————————
    Route::apiResource('agents', AgentController::class);
    Route::patch('agents/{agent}/matricule', [AgentController::class, 'modifierMatricule']);
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
    Route::get('dossiers/{dossier}/taches-post-integration', [DossierIntegrationController::class, 'tachesPostIntegration']);

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

    // Alias carrière (contrats, affectations, nominations, salaires agent)
    $routesCarriere();

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
// MODULE PERSONNEL — AGENTS INTÉGRÉS & STAGIAIRES
// ============================================================
Route::prefix('personnel')->middleware('auth:sanctum')->group(function () {
    Route::get('agents', [PersonnelController::class, 'agents']);
    Route::get('stagiaires', [PersonnelController::class, 'stagiaires']);
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
// Circuit de validation configurable par type d'intégration
Route::get('types-integrations/{typeIntegration}/circuit',         [CircuitValidationController::class, 'lister']);
Route::put('types-integrations/{typeIntegration}/circuit',         [CircuitValidationController::class, 'remplacer']);
Route::post('types-integrations/{typeIntegration}/circuit',        [CircuitValidationController::class, 'store']);
Route::delete('types-integrations/{typeIntegration}/circuit/{circuitStep}', [CircuitValidationController::class, 'retirerNiveau']);
Route::apiResource('types-absences', TypeAbsenceController::class);
Route::apiResource('types-conges', TypeCongeController::class);
Route::apiResource('motifs-administratifs', MotifAdministratifController::class);

// ============================================================
// MODULE GRILLE SALARIALE & SALAIRES AGENTS
// ============================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('grille-classes', ClassegrillesalarialeController::class)
        ->parameters(['grille-classes' => 'classegrillesalariale'])
        ->middleware([
            'index'   => 'permission:consulter-salaires',
            'show'    => 'permission:consulter-salaires',
            'store'   => 'permission:gerer-salaires',
            'update'  => 'permission:gerer-salaires',
            'destroy' => 'permission:gerer-salaires',
        ]);

    Route::get('grille-parametres/current', [ParametregrileController::class, 'current'])
        ->middleware('permission:consulter-salaires');
    Route::put('grille-parametres/{parametregrile}', [ParametregrileController::class, 'update'])
        ->middleware('permission:gerer-salaires');

    Route::get('salaires', [SalaireController::class, 'index'])
        ->middleware('permission:consulter-salaires');
    Route::post('salaires/generation', [SalaireController::class, 'generate'])
        ->middleware('permission:gerer-salaires');

    Route::get('salaires-agents', [SalaireAgentController::class, 'index'])
        ->middleware('permission:consulter-salaires');
    Route::post('salaires-agents', [SalaireAgentController::class, 'store'])
        ->middleware('permission:gerer-salaires');
    Route::get('salaires-agents/{id}', [SalaireAgentController::class, 'show'])
        ->middleware('permission:consulter-salaires');
    Route::post('salaires-agents/{id}/cloturer', [SalaireAgentController::class, 'cloturer'])
        ->middleware('permission:gerer-salaires');
    Route::get('salaires-agents/{id}/bulletin', [SalaireAgentController::class, 'bulletinById'])
        ->middleware('permission:consulter-salaires');
});

// ============================================================
// MODULE CONGÉS & ABSENCES
// ============================================================
Route::middleware('auth:sanctum')->prefix('conges')->group(function () {
    Route::get('jours-feries', [JourFerieController::class, 'index'])->middleware('permission:consulter-conges');
    Route::post('jours-feries', [JourFerieController::class, 'store'])->middleware('permission:valider-conges');
    Route::put('jours-feries/{id}', [JourFerieController::class, 'update'])->middleware('permission:valider-conges');
    Route::delete('jours-feries/{id}', [JourFerieController::class, 'destroy'])->middleware('permission:valider-conges');

    Route::get('regles-acquisition', [RegleAcquisitionCongeController::class, 'index'])->middleware('permission:consulter-conges');
    Route::post('regles-acquisition', [RegleAcquisitionCongeController::class, 'store'])->middleware('permission:valider-conges');
    Route::put('regles-acquisition/{id}', [RegleAcquisitionCongeController::class, 'update'])->middleware('permission:valider-conges');
    Route::delete('regles-acquisition/{id}', [RegleAcquisitionCongeController::class, 'destroy'])->middleware('permission:valider-conges');

    Route::get('soldes', [CongeSoldeController::class, 'index'])->middleware('permission:consulter-conges');
    Route::get('agents/{agent}/soldes', [CongeSoldeController::class, 'byAgent'])->middleware('permission:consulter-conges');

    Route::get('statistiques', [DemandeCongeController::class, 'statistiques'])->middleware('permission:consulter-conges');
    Route::get('agents/{agent}/demandes', [DemandeCongeController::class, 'byAgent'])->middleware('permission:consulter-conges');
    Route::get('demandes', [DemandeCongeController::class, 'index'])->middleware('permission:consulter-conges');
    Route::post('demandes', [DemandeCongeController::class, 'store'])->middleware('permission:creer-conges');
    Route::get('demandes/{id}', [DemandeCongeController::class, 'show'])->middleware('permission:consulter-conges');
    Route::post('demandes/{id}/valider-n1', [DemandeCongeController::class, 'validerN1'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/rejeter-n1', [DemandeCongeController::class, 'rejeterN1'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/valider-rh', [DemandeCongeController::class, 'validerRH'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/rejeter-rh', [DemandeCongeController::class, 'rejeterRH'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/valider-dg', [DemandeCongeController::class, 'validerDG'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/rejeter-dg', [DemandeCongeController::class, 'rejeterDG'])->middleware('permission:valider-conges');
    Route::get('demandes/{id}/fiche-pdf', [DemandeCongeController::class, 'fichePdf'])->middleware('permission:consulter-conges');
    Route::get('demandes/{id}/attestation', [DemandeCongeController::class, 'attestation'])->middleware('permission:consulter-conges');
});

Route::middleware('auth:sanctum')->prefix('absences')->group(function () {
    Route::get('/', [AbsenceController::class, 'index'])->middleware('permission:consulter-absences');
    Route::post('/', [AbsenceController::class, 'store'])->middleware('permission:creer-absences');
    Route::get('agents/{agent}', [AbsenceController::class, 'byAgent'])->middleware('permission:consulter-absences');
    Route::get('{id}', [AbsenceController::class, 'show'])->middleware('permission:consulter-absences');
    Route::post('{id}/valider', [AbsenceController::class, 'valider'])->middleware('permission:valider-absences');
    Route::post('{id}/rejeter', [AbsenceController::class, 'rejeter'])->middleware('permission:valider-absences');
});

// ============================================================
// MODULE 1.3 — AUTH & ADMINISTRATION SYSTÈME
// ============================================================
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // ============================================================
    // MODULE NOTIFICATIONS — inbox utilisateur
    // ============================================================
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/non-lues', [NotificationController::class, 'nonLues']);
        Route::post('/tout-lire', [NotificationController::class, 'toutLire']);
        Route::post('/{id}/lu', [NotificationController::class, 'marquerLu']);
    });

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
