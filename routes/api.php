<?php

use App\Http\Controllers\API\AbsenceController;
use App\Http\Controllers\API\ActeAdministratifController;
use App\Http\Controllers\API\AdministrationController;
use App\Http\Controllers\API\AffectationController;
use App\Http\Controllers\API\AffiliationSocialeController;
use App\Http\Controllers\API\AgentController;
use App\Http\Controllers\API\ArretSanteController;
use App\Http\Controllers\API\AuditLogController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AvancementExceptionnelController;
use App\Http\Controllers\API\AvertissementController;
use App\Http\Controllers\API\AvisHierarchiqueController;
use App\Http\Controllers\API\AyantDroitController;
use App\Http\Controllers\API\BonificationStageController;
use App\Http\Controllers\API\BureauController;
use App\Http\Controllers\API\CarriereAgentController;
use App\Http\Controllers\API\CatalogueFormationController;
use App\Http\Controllers\API\CategorieController;
use App\Http\Controllers\API\CertificationFormationController;
use App\Http\Controllers\API\CircuitValidationController;
use App\Http\Controllers\API\ClassegrillesalarialeController;
use App\Http\Controllers\API\CommissionAvancementController;
use App\Http\Controllers\API\CommissionPreparatoireController;
use App\Http\Controllers\API\CompteIntegrationController;
use App\Http\Controllers\API\CongeSoldeController;
use App\Http\Controllers\API\ConnaissanceComplementaireController;
use App\Http\Controllers\API\ContactUrgenceController;
use App\Http\Controllers\API\ContratController;
use App\Http\Controllers\API\ConventionStageController;
use App\Http\Controllers\API\DemandeCongeController;
use App\Http\Controllers\API\DiplomeController;
use App\Http\Controllers\API\DirectionController;
use App\Http\Controllers\API\DocumentAgentController;
use App\Http\Controllers\API\DocumentDossierController;
use App\Http\Controllers\API\DossierIntegrationController;
use App\Http\Controllers\API\DossierSocialController;
// Module Évaluation / Notation / Avancement
use App\Http\Controllers\API\EchelonController;
use App\Http\Controllers\API\EvaluationController;
use App\Http\Controllers\API\FonctionController;
use App\Http\Controllers\API\GradeController;
use App\Http\Controllers\API\InformationsPersonnelleController;
use App\Http\Controllers\API\InformationsProfessionnelleController;
use App\Http\Controllers\API\InscriptionFormationController;
use App\Http\Controllers\API\JourFerieController;
use App\Http\Controllers\API\LocaliteController;
use App\Http\Controllers\API\LotAffectationController;
use App\Http\Controllers\API\LotNominationController;
use App\Http\Controllers\API\MotifAdministratifController;
use App\Http\Controllers\API\NominationController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\OrganismeSocialController;
use App\Http\Controllers\API\PaieAffectationController;
use App\Http\Controllers\API\PaieElementController;
use App\Http\Controllers\API\PaieLotController;
use App\Http\Controllers\API\PalierAncienneteCongeController;
use App\Http\Controllers\API\ParametreApplicationController;
use App\Http\Controllers\API\ParametregrileController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\PersonnelController;
use App\Http\Controllers\API\PlanFormationController;
use App\Http\Controllers\API\PositionConventionnelleController;
use App\Http\Controllers\API\PrestationController;
use App\Http\Controllers\API\PriseDeServiceController;
use App\Http\Controllers\API\PriseEnChargeController;
use App\Http\Controllers\API\QuestionEvaluationController;
use App\Http\Controllers\API\ReclamationController;
use App\Http\Controllers\API\ReclassementController;
use App\Http\Controllers\API\RegleAcquisitionCongeController;
use App\Http\Controllers\API\RemiseMaterielController;
use App\Http\Controllers\API\ReportingController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\SalaireAgentController;
use App\Http\Controllers\API\SalaireController;
use App\Http\Controllers\API\SanctionController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\SessionEvaluationController;
use App\Http\Controllers\API\SituationFamilialeController;
use App\Http\Controllers\API\StructureSanitaireController;
use App\Http\Controllers\API\TypeAbsenceController;
use App\Http\Controllers\API\TypeCongeController;
use App\Http\Controllers\API\TypeContratController;
use App\Http\Controllers\API\TypeDocumentController;
use App\Http\Controllers\API\TypeIntegrationController;
use App\Http\Controllers\API\TypeSanctionController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ValidationWorkflowController;
use App\Http\Controllers\API\VisiteMedicaleController;
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

    Route::get('contrats/alertes/delai-30-jours', [ContratController::class, 'alertesDelai30Jours'])
        ->middleware('permission:consulter-contrats');
    Route::apiResource('contrats', ContratController::class)->only(['index', 'store', 'show']);
    Route::post('contrats/{contrat}/resilier', [ContratController::class, 'resilier']);
    Route::post('contrats/{contrat}/renouveler-essai', [ContratController::class, 'renouvelerEssai'])
        ->middleware('permission:modifier-contrats');
    Route::post('contrats/{contrat}/confirmer-essai', [ContratController::class, 'confirmerEssai'])
        ->middleware('permission:modifier-contrats');
    Route::post('contrats/{contrat}/rompre-essai', [ContratController::class, 'rompreEssai'])
        ->middleware('permission:modifier-contrats');

    Route::post('affectations/groupee', [LotAffectationController::class, 'store']);
    Route::get('affectations/lots/{lot}', [LotAffectationController::class, 'detail']);
    Route::post('affectations/lots/{lot}/activer', [LotAffectationController::class, 'activer']);
    Route::post('affectations/lots/{lot}/rejeter', [LotAffectationController::class, 'rejeter']);
    Route::get('affectations/lots/{lot}/acte', [LotAffectationController::class, 'acte']);
    Route::post('affectations/notes-service/lot', [AffectationController::class, 'noteServiceLot']);
    Route::apiResource('affectations', AffectationController::class)->only(['index', 'store', 'show']);
    Route::post('affectations/{affectation}/activer', [AffectationController::class, 'activer']);
    Route::post('affectations/{affectation}/rejeter', [AffectationController::class, 'rejeter']);
    Route::post('affectations/{affectation}/terminer', [AffectationController::class, 'terminer']);
    Route::get('affectations/{affectation}/note-service', [AffectationController::class, 'noteService']);

    Route::get('nominations/postes-vacants', [NominationController::class, 'postesVacants']);
    Route::get('nominations/chefs/{chef}/agents-sous-autorite', [NominationController::class, 'agentsSousAutorite']);
    Route::post('nominations/groupee', [LotNominationController::class, 'store']);
    Route::get('nominations/lots/{lot}', [LotNominationController::class, 'detail']);
    Route::post('nominations/lots/{lot}/activer', [LotNominationController::class, 'activer']);
    Route::post('nominations/lots/{lot}/rejeter', [LotNominationController::class, 'rejeter']);
    Route::get('nominations/lots/{lot}/acte', [LotNominationController::class, 'acte']);
    Route::apiResource('nominations', NominationController::class)->only(['index', 'store', 'show', 'update']);
    Route::post('nominations/{nomination}/activer', [NominationController::class, 'activer']);
    Route::post('nominations/{nomination}/cloturer', [NominationController::class, 'cloturer']);
    Route::post('nominations/{nomination}/rejeter', [NominationController::class, 'rejeter']);
    Route::post('nominations/{nomination}/confirmer-essai', [NominationController::class, 'confirmerEssai']);
    Route::post('nominations/{nomination}/rompre-essai', [NominationController::class, 'rompreEssai']);
    Route::get('nominations/{nomination}/acte', [NominationController::class, 'acte']);
};

Route::prefix('carriere')->middleware('auth:sanctum')->group(function () use ($routesCarriere) {
    $routesCarriere();
    // Synthèse carrière uniquement ici : pas d'alias /integration (conflit avec GET /integration/agents/{id}).
    Route::get('agents/{agent}', [CarriereAgentController::class, 'synthese']);

    // Reclassement / hors classe / reconversion (CCN art. 73–75)
    Route::get('reclassements', [ReclassementController::class, 'index'])
        ->middleware('permission:consulter-salaires');
    Route::post('reclassements', [ReclassementController::class, 'store'])
        ->middleware('permission:gerer-salaires');
    Route::get('reclassements/{id}', [ReclassementController::class, 'show'])
        ->middleware('permission:consulter-salaires');
    Route::post('reclassements/{id}/approuver', [ReclassementController::class, 'approuver'])
        ->middleware('permission:consulter-salaires');
    Route::post('reclassements/{id}/rejeter', [ReclassementController::class, 'rejeter'])
        ->middleware('permission:consulter-salaires');
    Route::post('reclassements/{id}/appliquer', [ReclassementController::class, 'appliquer'])
        ->middleware('permission:gerer-salaires');
    Route::get('agents/{id}/reclassements', [ReclassementController::class, 'parAgent'])
        ->middleware('permission:consulter-salaires');

    Route::get('positions', [PositionConventionnelleController::class, 'index'])
        ->middleware('permission:consulter-salaires');
    Route::post('positions', [PositionConventionnelleController::class, 'store'])
        ->middleware('permission:gerer-salaires');
    Route::get('positions/{id}', [PositionConventionnelleController::class, 'show'])
        ->middleware('permission:consulter-salaires');
    Route::post('positions/{id}/approuver', [PositionConventionnelleController::class, 'approuver'])
        ->middleware('permission:consulter-salaires');
    Route::post('positions/{id}/rejeter', [PositionConventionnelleController::class, 'rejeter'])
        ->middleware('permission:consulter-salaires');
    Route::post('positions/{id}/cloturer', [PositionConventionnelleController::class, 'cloturer'])
        ->middleware('permission:gerer-salaires');
    Route::post('positions/{id}/renouveler', [PositionConventionnelleController::class, 'renouveler'])
        ->middleware('permission:consulter-salaires');
    Route::get('agents/{id}/positions', [PositionConventionnelleController::class, 'parAgent'])
        ->middleware('permission:consulter-salaires');
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
    Route::post('dossiers/{dossier}/soumettre', [DossierIntegrationController::class, 'soumettre']);
    Route::post('dossiers/{dossier}/passer-en-etude-rh', [DossierIntegrationController::class, 'passerEnEtudeRH']);
    Route::post('dossiers/{dossier}/marquer-incomplet', [DossierIntegrationController::class, 'marquerIncomplet']);
    Route::post('dossiers/{dossier}/marquer-complet', [DossierIntegrationController::class, 'marquerComplet']);
    Route::post('dossiers/{dossier}/valider-rh', [DossierIntegrationController::class, 'validerRH']);
    Route::post('dossiers/{dossier}/rejeter-rh', [DossierIntegrationController::class, 'rejeterRH']);
    Route::post('dossiers/{dossier}/valider-dg', [DossierIntegrationController::class, 'validerDG']);
    Route::post('dossiers/{dossier}/generer-acte', [DossierIntegrationController::class, 'genererActe']);
    Route::post('dossiers/{dossier}/assigner-matricule', [DossierIntegrationController::class, 'assignerMatricule']);
    Route::post('dossiers/{dossier}/marquer-acte-genere', [DossierIntegrationController::class, 'marquerActeGenere']);
    Route::post('dossiers/{dossier}/marquer-contrat-signe', [DossierIntegrationController::class, 'marquerContratSigne']);
    Route::post('dossiers/{dossier}/suspendre', [DossierIntegrationController::class, 'suspendre']);
    Route::post('dossiers/{dossier}/annuler', [DossierIntegrationController::class, 'annuler']);
    Route::get('dossiers/{dossier}/historique', [DossierIntegrationController::class, 'historique']);
    Route::get('dossiers/{dossier}/taches-post-integration', [DossierIntegrationController::class, 'tachesPostIntegration']);

    // — Documents du dossier ——————————————————————————————
    Route::post('dossiers/{dossier}/documents', [DocumentDossierController::class, 'store']);
    Route::get('dossiers/{dossier}/documents', [DocumentDossierController::class, 'parDossier']);
    Route::post('documents/{document}/valider', [DocumentDossierController::class, 'valider']);
    Route::delete('documents/{document}', [DocumentDossierController::class, 'destroy']);

    // — Circuit de validation ——————————————————————————————
    Route::get('dossiers/{dossier}/circuit', [ValidationWorkflowController::class, 'circuit']);
    Route::post('validations/{validation}/approuver', [ValidationWorkflowController::class, 'approuver']);
    Route::post('validations/{validation}/rejeter', [ValidationWorkflowController::class, 'rejeter']);
    Route::post('validations/{validation}/renvoyer', [ValidationWorkflowController::class, 'renvoyer']);

    // — Actes administratifs ——————————————————————————————
    Route::get('dossiers/{dossier}/actes', [ActeAdministratifController::class, 'byDossier']);
    Route::post('dossiers/{dossier}/actes', [ActeAdministratifController::class, 'generer']);
    Route::post('actes/{acte}/signer', [ActeAdministratifController::class, 'signer']);

    // Alias carrière (contrats, affectations, nominations, salaires agent)
    $routesCarriere();

    // — Comptes utilisateurs ——————————————————————————————
    Route::post('comptes/provisionner', [CompteIntegrationController::class, 'provisionner']);

    // — Remises de matériel ———————————————————————————————
    Route::apiResource('remises-materiel', RemiseMaterielController::class)
        ->only(['index', 'store', 'show'])
        ->parameters(['remises-materiel' => 'remise']);

    // — Prises de service — étape finale ———————————————————
    Route::post('prises-de-service', [PriseDeServiceController::class, 'store']);
    Route::post('dossiers/{dossier}/integrer', [PriseDeServiceController::class, 'integrer']);

    // — Stages (ConventionStage) ———————————————————————————
    Route::get('stages', [ConventionStageController::class, 'index']);
    Route::get('stages/{stage}', [ConventionStageController::class, 'show']);
    Route::patch('stages/{stage}/prolonger', [ConventionStageController::class, 'prolonger']);
    Route::post('stages/{stage}/cloturer', [ConventionStageController::class, 'cloturer']);
    Route::get('stages/{stage}/attestation', [ConventionStageController::class, 'attestation']);
    Route::post('stages/{stage}/convertir-agent', [ConventionStageController::class, 'convertirAgent'])
        ->middleware('permission:gerer-formations|creer-recrutement');
});

// ============================================================
// MODULE PERSONNEL — AGENTS INTÉGRÉS & STAGIAIRES
// ============================================================
Route::prefix('personnel')->middleware('auth:sanctum')->group(function () {
    Route::get('agents', [PersonnelController::class, 'agents']);
    Route::get('stagiaires', [PersonnelController::class, 'stagiaires']);
    Route::get('agents/{agent}', [PersonnelController::class, 'afficher']);
    Route::post('agents/{agent}/archiver', [PersonnelController::class, 'archiver']);
    Route::post('agents/{agent}/desarchiver', [PersonnelController::class, 'desarchiver']);

    Route::get('agents/{agent}/informations-personnelles', [InformationsPersonnelleController::class, 'afficher']);
    Route::put('agents/{agent}/informations-personnelles', [InformationsPersonnelleController::class, 'upsert']);

    Route::get('agents/{agent}/informations-professionnelles', [InformationsProfessionnelleController::class, 'afficher']);
    Route::put('agents/{agent}/informations-professionnelles', [InformationsProfessionnelleController::class, 'upsert']);

    Route::get('agents/{agent}/contacts-urgence', [ContactUrgenceController::class, 'lister']);
    Route::post('agents/{agent}/contacts-urgence', [ContactUrgenceController::class, 'store']);
    Route::put('agents/{agent}/contacts-urgence/{id}', [ContactUrgenceController::class, 'update']);
    Route::delete('agents/{agent}/contacts-urgence/{id}', [ContactUrgenceController::class, 'supprimer']);

    Route::get('agents/{agent}/situation-familiale', [SituationFamilialeController::class, 'afficher']);
    Route::put('agents/{agent}/situation-familiale', [SituationFamilialeController::class, 'upsert']);

    Route::get('agents/{agent}/documents/arborescence', [DocumentAgentController::class, 'arborescence']);
    Route::get('agents/{agent}/documents', [DocumentAgentController::class, 'lister']);
    Route::post('agents/{agent}/documents', [DocumentAgentController::class, 'store']);
    Route::get('agents/{agent}/documents/{id}', [DocumentAgentController::class, 'afficher']);
    Route::get('agents/{agent}/documents/{id}/fichier', [DocumentAgentController::class, 'telecharger']);
    Route::delete('agents/{agent}/documents/{id}', [DocumentAgentController::class, 'supprimer']);
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
Route::get('types-integrations/{typeIntegration}/circuit', [CircuitValidationController::class, 'lister']);
Route::put('types-integrations/{typeIntegration}/circuit', [CircuitValidationController::class, 'remplacer']);
Route::post('types-integrations/{typeIntegration}/circuit', [CircuitValidationController::class, 'store']);
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
            'index' => 'permission:consulter-salaires',
            'show' => 'permission:consulter-salaires',
            'store' => 'permission:gerer-salaires',
            'update' => 'permission:gerer-salaires',
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
// MODULE PAIE D.5 — éléments, affectations, lots
// ============================================================
Route::middleware('auth:sanctum')->prefix('paie')->group(function () {
    Route::get('elements', [PaieElementController::class, 'index'])
        ->middleware('permission:consulter-salaires');
    Route::post('elements', [PaieElementController::class, 'store'])
        ->middleware('permission:gerer-salaires');
    Route::get('elements/{id}', [PaieElementController::class, 'show'])
        ->middleware('permission:consulter-salaires');
    Route::put('elements/{id}', [PaieElementController::class, 'update'])
        ->middleware('permission:gerer-salaires');
    Route::delete('elements/{id}', [PaieElementController::class, 'destroy'])
        ->middleware('permission:gerer-salaires');

    Route::get('affectations', [PaieAffectationController::class, 'index'])
        ->middleware('permission:consulter-salaires');
    Route::post('affectations', [PaieAffectationController::class, 'store'])
        ->middleware('permission:gerer-salaires');
    Route::get('agents/{agent}/affectations', [PaieAffectationController::class, 'byAgent'])
        ->middleware('permission:consulter-salaires');
    Route::get('affectations/{id}', [PaieAffectationController::class, 'show'])
        ->middleware('permission:consulter-salaires');
    Route::put('affectations/{id}', [PaieAffectationController::class, 'update'])
        ->middleware('permission:gerer-salaires');
    Route::delete('affectations/{id}', [PaieAffectationController::class, 'destroy'])
        ->middleware('permission:gerer-salaires');

    Route::get('lots', [PaieLotController::class, 'index'])
        ->middleware('permission:consulter-salaires');
    Route::post('lots', [PaieLotController::class, 'store'])
        ->middleware('permission:gerer-salaires');
    Route::get('lots/{id}', [PaieLotController::class, 'show'])
        ->middleware('permission:consulter-salaires');
    Route::put('lots/{id}', [PaieLotController::class, 'update'])
        ->middleware('permission:gerer-salaires');
    Route::delete('lots/{id}', [PaieLotController::class, 'destroy'])
        ->middleware('permission:gerer-salaires');
    Route::post('lots/{id}/generer', [PaieLotController::class, 'generer'])
        ->middleware('permission:gerer-salaires');
    Route::post('lots/{id}/controler', [PaieLotController::class, 'controler'])
        ->middleware('permission:gerer-salaires');
    Route::post('lots/{id}/valider', [PaieLotController::class, 'valider'])
        ->middleware('permission:gerer-salaires');
    Route::post('lots/{id}/cloturer', [PaieLotController::class, 'cloturer'])
        ->middleware('permission:gerer-salaires');
    Route::get('lots/{id}/export', [PaieLotController::class, 'export'])
        ->middleware('permission:consulter-salaires');
    Route::get('lots/{id}/lignes', [PaieLotController::class, 'lignes'])
        ->middleware('permission:consulter-salaires');
    Route::get('lots/{id}/lignes/{ligneId}/bulletin', [PaieLotController::class, 'bulletin'])
        ->middleware('permission:consulter-salaires');
    Route::get('lots/{id}/lignes/{ligneId}', [PaieLotController::class, 'ligne'])
        ->middleware('permission:consulter-salaires');
    Route::get('agents/{agent}/bulletins', [PaieLotController::class, 'bulletinsAgent'])
        ->middleware('permission:consulter-salaires');
});

// ============================================================
// MODULE REPORTING D.6 — dashboard, stats, alertes, exports
// ============================================================
Route::middleware(['auth:sanctum', 'permission:consulter-reporting'])->prefix('reporting')->group(function () {
    Route::get('dashboard', [ReportingController::class, 'dashboard']);
    Route::get('effectifs', [ReportingController::class, 'effectifs']);
    Route::get('repartitions', [ReportingController::class, 'repartitions']);
    Route::get('stats/conges', [ReportingController::class, 'statsConges']);
    Route::get('stats/evaluations', [ReportingController::class, 'statsEvaluations']);
    Route::get('alertes', [ReportingController::class, 'alertes']);
    Route::get('exports/{type}', [ReportingController::class, 'export']);
});

// ============================================================
// MODULE CONGÉS & ABSENCES
// ============================================================
Route::middleware('auth:sanctum')->prefix('conges')->group(function () {
    Route::get('jours-feries', [JourFerieController::class, 'index'])->middleware('permission:consulter-conges');
    Route::post('jours-feries', [JourFerieController::class, 'store'])->middleware('permission:valider-conges');
    Route::put('jours-feries/{id}', [JourFerieController::class, 'update'])->middleware('permission:valider-conges');
    Route::delete('jours-feries/{id}', [JourFerieController::class, 'destroy'])->middleware('permission:valider-conges');

    Route::get('paliers-anciennete', [PalierAncienneteCongeController::class, 'index'])->middleware('permission:consulter-conges');
    Route::post('paliers-anciennete', [PalierAncienneteCongeController::class, 'store'])->middleware('permission:valider-conges');
    Route::put('paliers-anciennete/{id}', [PalierAncienneteCongeController::class, 'update'])->middleware('permission:valider-conges');
    Route::delete('paliers-anciennete/{id}', [PalierAncienneteCongeController::class, 'destroy'])->middleware('permission:valider-conges');

    Route::get('regles-acquisition', [RegleAcquisitionCongeController::class, 'index'])->middleware('permission:consulter-conges');
    Route::post('regles-acquisition', [RegleAcquisitionCongeController::class, 'store'])->middleware('permission:valider-conges');
    Route::put('regles-acquisition/{id}', [RegleAcquisitionCongeController::class, 'update'])->middleware('permission:valider-conges');
    Route::delete('regles-acquisition/{id}', [RegleAcquisitionCongeController::class, 'destroy'])->middleware('permission:valider-conges');

    Route::get('soldes', [CongeSoldeController::class, 'index'])->middleware('permission:consulter-conges');
    Route::get('agents/{agent}/soldes', [CongeSoldeController::class, 'byAgent'])->middleware('permission:consulter-conges');

    Route::get('statistiques', [DemandeCongeController::class, 'statistiques'])->middleware('permission:consulter-conges');
    Route::get('agents/{agent}/demandes', [DemandeCongeController::class, 'byAgent'])->middleware('permission:consulter-conges');
    Route::get('demandes/a-valider', [DemandeCongeController::class, 'aValider'])->middleware('permission:valider-conges');
    Route::get('demandes', [DemandeCongeController::class, 'index'])->middleware('permission:consulter-conges');
    Route::post('demandes', [DemandeCongeController::class, 'store'])->middleware('permission:creer-conges');
    Route::get('demandes/{id}', [DemandeCongeController::class, 'show'])->middleware('permission:consulter-conges');
    Route::post('demandes/{id}/valider-n1', [DemandeCongeController::class, 'validerN1'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/rejeter-n1', [DemandeCongeController::class, 'rejeterN1'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/valider-rh', [DemandeCongeController::class, 'validerRH'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/rejeter-rh', [DemandeCongeController::class, 'rejeterRH'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/valider-dg', [DemandeCongeController::class, 'validerDG'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/rejeter-dg', [DemandeCongeController::class, 'rejeterDG'])->middleware('permission:valider-conges');
    Route::post('demandes/{id}/annuler', [DemandeCongeController::class, 'annuler'])->middleware('permission:creer-conges');
    Route::get('demandes/{id}/justificatif', [DemandeCongeController::class, 'justificatif'])->middleware('permission:consulter-conges');
    Route::get('demandes/{id}/fiche-pdf', [DemandeCongeController::class, 'fichePdf'])->middleware('permission:consulter-conges');
    Route::get('demandes/{id}/attestation', [DemandeCongeController::class, 'attestation'])->middleware('permission:consulter-conges');
});

Route::middleware('auth:sanctum')->prefix('absences')->group(function () {
    Route::get('/', [AbsenceController::class, 'index'])->middleware('permission:consulter-absences');
    Route::post('/', [AbsenceController::class, 'store'])->middleware('permission:creer-absences');
    Route::get('a-valider', [AbsenceController::class, 'aValider'])->middleware('permission:valider-absences');
    Route::get('agents/{agent}', [AbsenceController::class, 'byAgent'])->middleware('permission:consulter-absences');
    Route::get('{id}', [AbsenceController::class, 'show'])->middleware('permission:consulter-absences');
    Route::post('{id}/valider', [AbsenceController::class, 'valider'])->middleware('permission:valider-absences');
    Route::post('{id}/rejeter', [AbsenceController::class, 'rejeter'])->middleware('permission:valider-absences');
});

// ============================================================
// MODULE 9 — DISCIPLINE
// ============================================================
Route::middleware('auth:sanctum')->prefix('discipline')->group(function () {
    Route::get('moi/historique', [SanctionController::class, 'moiHistorique']);
    Route::get('moi/sanctions', [SanctionController::class, 'mesSanctions']);
    Route::get('moi/sanctions/{id}/pdf-decision', [SanctionController::class, 'maDecisionPdf']);
    Route::get('moi/sanctions/{id}', [SanctionController::class, 'maSanction']);
    Route::get('moi/avertissements', [AvertissementController::class, 'mesAvertissements']);
    Route::get('moi/avertissements/{id}', [AvertissementController::class, 'monAvertissement']);

    Route::get('types-sanctions', [TypeSanctionController::class, 'index'])->middleware('permission:consulter-discipline|proposer-discipline');
    Route::post('types-sanctions', [TypeSanctionController::class, 'store'])->middleware('permission:gerer-discipline');
    Route::get('types-sanctions/{id}', [TypeSanctionController::class, 'show'])->middleware('permission:consulter-discipline|proposer-discipline');
    Route::put('types-sanctions/{id}', [TypeSanctionController::class, 'update'])->middleware('permission:gerer-discipline');
    Route::delete('types-sanctions/{id}', [TypeSanctionController::class, 'destroy'])->middleware('permission:gerer-discipline');

    Route::get('sanctions', [SanctionController::class, 'index'])->middleware('permission:consulter-discipline');
    Route::post('sanctions', [SanctionController::class, 'store'])->middleware('permission:proposer-discipline');
    Route::get('sanctions/a-instruire', [SanctionController::class, 'aInstruire'])->middleware('permission:gerer-discipline');
    Route::get('sanctions/a-prononcer', [SanctionController::class, 'aPrononcer'])->middleware('permission:prononcer-discipline');
    Route::get('sanctions/a-valider', [SanctionController::class, 'aValider'])->middleware('permission:prononcer-discipline');
    Route::get('sanctions/mes-rapports', [SanctionController::class, 'mesRapports'])->middleware('permission:proposer-discipline');
    Route::get('agents/{agent}/sanctions', [SanctionController::class, 'byAgent'])->middleware('permission:consulter-discipline');
    Route::get('agents/{agent}/historique', [SanctionController::class, 'historique'])->middleware('permission:consulter-discipline');
    Route::get('sanctions/{id}/pieces', [SanctionController::class, 'pieces'])->middleware('permission:consulter-discipline|proposer-discipline|prononcer-discipline|gerer-discipline');
    Route::post('sanctions/{id}/pieces', [SanctionController::class, 'storePiece'])->middleware('permission:proposer-discipline|gerer-discipline');
    Route::get('sanctions/{id}/pieces/{pieceId}', [SanctionController::class, 'downloadPiece'])->middleware('permission:consulter-discipline|proposer-discipline|prononcer-discipline|gerer-discipline');
    Route::delete('sanctions/{id}/pieces/{pieceId}', [SanctionController::class, 'destroyPiece'])->middleware('permission:proposer-discipline|gerer-discipline');
    Route::get('sanctions/{id}/pdf-rapport', [SanctionController::class, 'rapportPdf'])->middleware('permission:consulter-discipline|proposer-discipline|prononcer-discipline|gerer-discipline');
    Route::get('sanctions/{id}/pdf-decision', [SanctionController::class, 'decisionPdf'])->middleware('permission:consulter-discipline|proposer-discipline|prononcer-discipline|gerer-discipline');
    Route::get('sanctions/{id}', [SanctionController::class, 'show'])->middleware('permission:consulter-discipline|proposer-discipline|prononcer-discipline|gerer-discipline');
    Route::put('sanctions/{id}', [SanctionController::class, 'update'])->middleware('permission:proposer-discipline|gerer-discipline');
    Route::post('sanctions/{id}/instruire', [SanctionController::class, 'instruire'])->middleware('permission:gerer-discipline');
    Route::post('sanctions/{id}/valider', [SanctionController::class, 'valider'])->middleware('permission:prononcer-discipline');
    Route::post('sanctions/{id}/rejeter', [SanctionController::class, 'rejeter'])->middleware('permission:prononcer-discipline');
    Route::delete('sanctions/{id}', [SanctionController::class, 'destroy'])->middleware('permission:proposer-discipline|gerer-discipline');

    Route::get('avertissements', [AvertissementController::class, 'index'])->middleware('permission:consulter-discipline');
    Route::post('avertissements', [AvertissementController::class, 'store'])->middleware('permission:gerer-discipline');
    Route::get('agents/{agent}/avertissements', [AvertissementController::class, 'byAgent'])->middleware('permission:consulter-discipline');
    Route::get('avertissements/{id}', [AvertissementController::class, 'show'])->middleware('permission:consulter-discipline');
    Route::put('avertissements/{id}', [AvertissementController::class, 'update'])->middleware('permission:gerer-discipline');
    Route::delete('avertissements/{id}', [AvertissementController::class, 'destroy'])->middleware('permission:gerer-discipline');
});

// ============================================================
// MODULE 10 — AFFAIRES SOCIALES (D.3 P1)
// ============================================================
Route::middleware('auth:sanctum')->prefix('affaires-sociales')->group(function () {
    Route::get('organismes', [OrganismeSocialController::class, 'index'])->middleware('permission:consulter-affaires-sociales');
    Route::post('organismes', [OrganismeSocialController::class, 'store'])->middleware('permission:gerer-affaires-sociales');
    Route::get('organismes/{id}', [OrganismeSocialController::class, 'show'])->middleware('permission:consulter-affaires-sociales');
    Route::put('organismes/{id}', [OrganismeSocialController::class, 'update'])->middleware('permission:gerer-affaires-sociales');
    Route::delete('organismes/{id}', [OrganismeSocialController::class, 'destroy'])->middleware('permission:gerer-affaires-sociales');

    Route::get('affiliations', [AffiliationSocialeController::class, 'index'])->middleware('permission:consulter-affaires-sociales');
    Route::post('affiliations', [AffiliationSocialeController::class, 'store'])->middleware('permission:gerer-affaires-sociales');
    Route::get('alertes/sans-affiliation-cnss', [AffiliationSocialeController::class, 'sansAffiliationCnss'])->middleware('permission:consulter-affaires-sociales');
    Route::get('agents/{agent}/affiliations', [AffiliationSocialeController::class, 'byAgent'])->middleware('permission:consulter-affaires-sociales');
    Route::get('agents/{agent}/ayants-droit', [AyantDroitController::class, 'byAgent'])->middleware('permission:consulter-affaires-sociales');
    Route::get('agents/{agent}/dossier-social', [DossierSocialController::class, 'show'])->middleware('permission:consulter-affaires-sociales');
    Route::get('affiliations/{id}', [AffiliationSocialeController::class, 'show'])->middleware('permission:consulter-affaires-sociales');
    Route::put('affiliations/{id}', [AffiliationSocialeController::class, 'update'])->middleware('permission:gerer-affaires-sociales');
    Route::delete('affiliations/{id}', [AffiliationSocialeController::class, 'destroy'])->middleware('permission:gerer-affaires-sociales');

    Route::get('ayants-droit', [AyantDroitController::class, 'index'])->middleware('permission:consulter-affaires-sociales');
    Route::post('ayants-droit', [AyantDroitController::class, 'store'])->middleware('permission:gerer-affaires-sociales');
    Route::get('ayants-droit/{id}/pieces', [AyantDroitController::class, 'pieces'])->middleware('permission:consulter-affaires-sociales');
    Route::post('ayants-droit/{id}/pieces', [AyantDroitController::class, 'storePiece'])->middleware('permission:gerer-affaires-sociales');
    Route::get('ayants-droit/{id}/pieces/{pieceId}', [AyantDroitController::class, 'downloadPiece'])->middleware('permission:consulter-affaires-sociales');
    Route::delete('ayants-droit/{id}/pieces/{pieceId}', [AyantDroitController::class, 'destroyPiece'])->middleware('permission:gerer-affaires-sociales');
    Route::get('ayants-droit/{id}', [AyantDroitController::class, 'show'])->middleware('permission:consulter-affaires-sociales');
    Route::put('ayants-droit/{id}', [AyantDroitController::class, 'update'])->middleware('permission:gerer-affaires-sociales');
    Route::delete('ayants-droit/{id}', [AyantDroitController::class, 'destroy'])->middleware('permission:gerer-affaires-sociales');

    Route::get('prestations', [PrestationController::class, 'index'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::post('prestations', [PrestationController::class, 'store'])->middleware('permission:gerer-affaires-sociales');
    Route::get('agents/{agent}/prestations', [PrestationController::class, 'byAgent'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('prestations/{id}/simulation', [PrestationController::class, 'simulation'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('prestations/{id}/pdf-decision', [PrestationController::class, 'decisionPdf'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('prestations/{id}/pieces', [PrestationController::class, 'pieces'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::post('prestations/{id}/pieces', [PrestationController::class, 'storePiece'])->middleware('permission:gerer-affaires-sociales');
    Route::get('prestations/{id}/pieces/{pieceId}', [PrestationController::class, 'downloadPiece'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::delete('prestations/{id}/pieces/{pieceId}', [PrestationController::class, 'destroyPiece'])->middleware('permission:gerer-affaires-sociales');
    Route::post('prestations/{id}/soumettre', [PrestationController::class, 'soumettre'])->middleware('permission:gerer-affaires-sociales');
    Route::post('prestations/{id}/instruire', [PrestationController::class, 'instruire'])->middleware('permission:gerer-affaires-sociales');
    Route::post('prestations/{id}/accorder', [PrestationController::class, 'accorder'])->middleware('permission:decider-prestations');
    Route::post('prestations/{id}/refuser', [PrestationController::class, 'refuser'])->middleware('permission:decider-prestations');
    Route::post('prestations/{id}/classer', [PrestationController::class, 'classer'])->middleware('permission:gerer-affaires-sociales');
    Route::get('prestations/{id}', [PrestationController::class, 'show'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::put('prestations/{id}', [PrestationController::class, 'update'])->middleware('permission:gerer-affaires-sociales');
    Route::delete('prestations/{id}', [PrestationController::class, 'destroy'])->middleware('permission:gerer-affaires-sociales');

    Route::get('structures-sanitaires', [StructureSanitaireController::class, 'index'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::post('structures-sanitaires', [StructureSanitaireController::class, 'store'])->middleware('permission:gerer-affaires-sociales');
    Route::get('structures-sanitaires/{id}', [StructureSanitaireController::class, 'show'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::put('structures-sanitaires/{id}', [StructureSanitaireController::class, 'update'])->middleware('permission:gerer-affaires-sociales');
    Route::delete('structures-sanitaires/{id}', [StructureSanitaireController::class, 'destroy'])->middleware('permission:gerer-affaires-sociales');

    Route::get('alertes/visites-annuelles-manquantes', [VisiteMedicaleController::class, 'alertesAnnuelles'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('visites-medicales', [VisiteMedicaleController::class, 'index'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::post('visites-medicales', [VisiteMedicaleController::class, 'store'])->middleware('permission:gerer-affaires-sociales');
    Route::get('agents/{agent}/visites-medicales', [VisiteMedicaleController::class, 'byAgent'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('visites-medicales/{id}', [VisiteMedicaleController::class, 'show'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::put('visites-medicales/{id}', [VisiteMedicaleController::class, 'update'])->middleware('permission:gerer-affaires-sociales');
    Route::delete('visites-medicales/{id}', [VisiteMedicaleController::class, 'destroy'])->middleware('permission:gerer-affaires-sociales');

    Route::get('prises-en-charge', [PriseEnChargeController::class, 'index'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::post('prises-en-charge', [PriseEnChargeController::class, 'store'])->middleware('permission:gerer-affaires-sociales');
    Route::get('agents/{agent}/prises-en-charge', [PriseEnChargeController::class, 'byAgent'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('prises-en-charge/{id}/simulation', [PriseEnChargeController::class, 'simulation'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('prises-en-charge/{id}/pdf-decision', [PriseEnChargeController::class, 'decisionPdf'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('prises-en-charge/{id}/pieces', [PriseEnChargeController::class, 'pieces'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::post('prises-en-charge/{id}/pieces', [PriseEnChargeController::class, 'storePiece'])->middleware('permission:gerer-affaires-sociales');
    Route::get('prises-en-charge/{id}/pieces/{pieceId}', [PriseEnChargeController::class, 'downloadPiece'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::delete('prises-en-charge/{id}/pieces/{pieceId}', [PriseEnChargeController::class, 'destroyPiece'])->middleware('permission:gerer-affaires-sociales');
    Route::post('prises-en-charge/{id}/soumettre', [PriseEnChargeController::class, 'soumettre'])->middleware('permission:gerer-affaires-sociales');
    Route::post('prises-en-charge/{id}/instruire', [PriseEnChargeController::class, 'instruire'])->middleware('permission:gerer-affaires-sociales');
    Route::post('prises-en-charge/{id}/accorder', [PriseEnChargeController::class, 'accorder'])->middleware('permission:decider-prestations');
    Route::post('prises-en-charge/{id}/refuser', [PriseEnChargeController::class, 'refuser'])->middleware('permission:decider-prestations');
    Route::post('prises-en-charge/{id}/classer', [PriseEnChargeController::class, 'classer'])->middleware('permission:gerer-affaires-sociales');
    Route::get('prises-en-charge/{id}', [PriseEnChargeController::class, 'show'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::put('prises-en-charge/{id}', [PriseEnChargeController::class, 'update'])->middleware('permission:gerer-affaires-sociales');
    Route::delete('prises-en-charge/{id}', [PriseEnChargeController::class, 'destroy'])->middleware('permission:gerer-affaires-sociales');

    Route::get('arrets', [ArretSanteController::class, 'index'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::post('arrets', [ArretSanteController::class, 'store'])->middleware('permission:gerer-affaires-sociales');
    Route::get('agents/{agent}/arrets', [ArretSanteController::class, 'byAgent'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('arrets/{id}/simulation', [ArretSanteController::class, 'simulation'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('arrets/{id}/pdf-decision', [ArretSanteController::class, 'decisionPdf'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::get('arrets/{id}/pieces', [ArretSanteController::class, 'pieces'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::post('arrets/{id}/pieces', [ArretSanteController::class, 'storePiece'])->middleware('permission:gerer-affaires-sociales');
    Route::get('arrets/{id}/pieces/{pieceId}', [ArretSanteController::class, 'downloadPiece'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::delete('arrets/{id}/pieces/{pieceId}', [ArretSanteController::class, 'destroyPiece'])->middleware('permission:gerer-affaires-sociales');
    Route::post('arrets/{id}/soumettre', [ArretSanteController::class, 'soumettre'])->middleware('permission:gerer-affaires-sociales');
    Route::post('arrets/{id}/instruire', [ArretSanteController::class, 'instruire'])->middleware('permission:gerer-affaires-sociales');
    Route::post('arrets/{id}/accorder', [ArretSanteController::class, 'accorder'])->middleware('permission:decider-prestations');
    Route::post('arrets/{id}/refuser', [ArretSanteController::class, 'refuser'])->middleware('permission:decider-prestations');
    Route::post('arrets/{id}/classer', [ArretSanteController::class, 'classer'])->middleware('permission:gerer-affaires-sociales');
    Route::get('arrets/{id}', [ArretSanteController::class, 'show'])->middleware('permission:consulter-affaires-sociales|decider-prestations');
    Route::put('arrets/{id}', [ArretSanteController::class, 'update'])->middleware('permission:gerer-affaires-sociales');
    Route::delete('arrets/{id}', [ArretSanteController::class, 'destroy'])->middleware('permission:gerer-affaires-sociales');
});

// ============================================================
// MODULE 11 — FORMATION CONTINUE (D.4)
// ============================================================
Route::middleware('auth:sanctum')->prefix('formations')->group(function () {
    Route::get('catalogue', [CatalogueFormationController::class, 'index'])->middleware('permission:consulter-formations');
    Route::post('catalogue', [CatalogueFormationController::class, 'store'])->middleware('permission:gerer-formations');
    Route::get('catalogue/{id}', [CatalogueFormationController::class, 'show'])->middleware('permission:consulter-formations');
    Route::put('catalogue/{id}', [CatalogueFormationController::class, 'update'])->middleware('permission:gerer-formations');
    Route::delete('catalogue/{id}', [CatalogueFormationController::class, 'destroy'])->middleware('permission:gerer-formations');

    Route::get('plans', [PlanFormationController::class, 'index'])->middleware('permission:consulter-formations');
    Route::post('plans', [PlanFormationController::class, 'store'])->middleware('permission:gerer-formations');
    Route::get('plans/{id}', [PlanFormationController::class, 'show'])->middleware('permission:consulter-formations');
    Route::put('plans/{id}', [PlanFormationController::class, 'update'])->middleware('permission:gerer-formations');
    Route::delete('plans/{id}', [PlanFormationController::class, 'destroy'])->middleware('permission:gerer-formations');
    Route::post('plans/{id}/lignes', [PlanFormationController::class, 'storeLigne'])->middleware('permission:gerer-formations');
    Route::delete('plans/{id}/lignes/{ligneId}', [PlanFormationController::class, 'destroyLigne'])->middleware('permission:gerer-formations');
    Route::post('plans/{id}/valider', [PlanFormationController::class, 'valider'])->middleware('permission:gerer-formations');
    Route::post('plans/{id}/executer', [PlanFormationController::class, 'executer'])->middleware('permission:gerer-formations');
    Route::post('plans/{id}/cloturer', [PlanFormationController::class, 'cloturer'])->middleware('permission:gerer-formations');

    Route::get('inscriptions', [InscriptionFormationController::class, 'index'])->middleware('permission:consulter-formations');
    Route::post('inscriptions', [InscriptionFormationController::class, 'store'])->middleware('permission:gerer-formations');
    Route::get('agents/{agent}/inscriptions', [InscriptionFormationController::class, 'byAgent'])->middleware('permission:consulter-formations');
    Route::get('agents/{agent}/certifications', [CertificationFormationController::class, 'byAgent'])->middleware('permission:consulter-formations');
    Route::get('inscriptions/{id}', [InscriptionFormationController::class, 'show'])->middleware('permission:consulter-formations');
    Route::post('inscriptions/{id}/confirmer-presence', [InscriptionFormationController::class, 'confirmerPresence'])->middleware('permission:gerer-formations');
    Route::post('inscriptions/{id}/cloturer', [InscriptionFormationController::class, 'cloturer'])->middleware('permission:gerer-formations');
    Route::post('inscriptions/{id}/annuler', [InscriptionFormationController::class, 'annuler'])->middleware('permission:gerer-formations');
    Route::delete('inscriptions/{id}', [InscriptionFormationController::class, 'destroy'])->middleware('permission:gerer-formations');

    Route::get('certifications', [CertificationFormationController::class, 'index'])->middleware('permission:consulter-formations');
    Route::post('certifications', [CertificationFormationController::class, 'store'])->middleware('permission:gerer-formations');
    Route::get('certifications/{id}/fichier', [CertificationFormationController::class, 'fichier'])->middleware('permission:consulter-formations');
    Route::get('certifications/{id}', [CertificationFormationController::class, 'show'])->middleware('permission:consulter-formations');
    Route::delete('certifications/{id}', [CertificationFormationController::class, 'destroy'])->middleware('permission:gerer-formations');
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

// ============================================================
// MODULE ÉVALUATION / NOTATION / AVANCEMENT — CCN ARTF art. 60-75
// ============================================================
Route::middleware('auth:sanctum')->prefix('avancements')->group(function () {

    // ---- Grille de critères (référentiel RH) ----
    Route::apiResource('questions-evaluation', QuestionEvaluationController::class)->middleware([
        'index' => 'permission:consulter-evaluations',
        'show' => 'permission:consulter-evaluations',
        'store' => 'permission:creer-evaluations',
        'update' => 'permission:creer-evaluations',
        'destroy' => 'permission:creer-evaluations',
    ]);

    // ---- Sessions d'évaluation (cycle annuel) ----
    Route::get('sessions', [SessionEvaluationController::class, 'index'])
        ->middleware('permission:consulter-evaluations');
    Route::get('sessions/{id}', [SessionEvaluationController::class, 'show'])
        ->middleware('permission:consulter-evaluations');
    Route::post('sessions', [SessionEvaluationController::class, 'store'])
        ->middleware('permission:creer-evaluations');
    Route::put('sessions/{id}', [SessionEvaluationController::class, 'update'])
        ->middleware('permission:creer-evaluations');
    Route::post('sessions/{id}/cloturer', [SessionEvaluationController::class, 'cloturer'])
        ->middleware('permission:creer-evaluations');
    Route::post('sessions/{id}/annuler', [SessionEvaluationController::class, 'annuler'])
        ->middleware('permission:creer-evaluations');
    Route::post('sessions/{id}/generer-fiches', [SessionEvaluationController::class, 'genererFiches'])
        ->middleware('permission:creer-evaluations');
    Route::get('sessions/{id}/sans-superieur', [SessionEvaluationController::class, 'sansSupereur'])
        ->middleware('permission:creer-evaluations');
    Route::get('sessions/{id}/stats', [SessionEvaluationController::class, 'stats'])
        ->middleware('permission:consulter-evaluations');
    Route::get('sessions/{id}/tableau', [EvaluationController::class, 'tableau'])
        ->middleware('permission:consulter-evaluations');

    // ---- Fiches d'évaluation ----

    // Vue RH — toutes les fiches
    Route::get('evaluations', [EvaluationController::class, 'index'])
        ->middleware('permission:consulter-evaluations');
    Route::get('evaluations/{id}', [EvaluationController::class, 'show'])
        ->middleware('permission:consulter-evaluations');

    // Vue notateur (N+1)
    Route::get('evaluations/superieur/mes-evaluations', [EvaluationController::class, 'mesEvaluationsSuperieur'])
        ->middleware('permission:consulter-evaluations');
    Route::post('evaluations/{id}/noter', [EvaluationController::class, 'noter'])
        ->middleware('permission:valider-evaluations');
    Route::put('evaluations/{id}/contexte', [EvaluationController::class, 'updateContexte'])
        ->middleware('permission:valider-evaluations');
    Route::post('evaluations/{id}/signer-evaluateur', [EvaluationController::class, 'signerEvaluateur'])
        ->middleware('permission:valider-evaluations');
    // Phase 2 : avis obligatoire + signature (remplace signer-evaluateur)
    Route::post('evaluations/{id}/avis-et-signer', [EvaluationController::class, 'avisEtSigner'])
        ->middleware('permission:valider-evaluations');

    // Vue agent évalué
    Route::get('evaluations/agent/mes-evaluations', [EvaluationController::class, 'mesEvaluationsAgent'])
        ->middleware('permission:consulter-evaluations');
    Route::post('evaluations/{id}/signer-evalue', [EvaluationController::class, 'signerEvalue'])
        ->middleware('permission:consulter-evaluations');
    // Phase 2 : réclamation + envoi RH
    Route::post('evaluations/{id}/reclamer', [EvaluationController::class, 'reclamer'])
        ->middleware('permission:consulter-evaluations');
    Route::post('evaluations/{id}/envoyer-rh', [EvaluationController::class, 'envoyerRh'])
        ->middleware('permission:consulter-evaluations');

    // Validation RH
    Route::post('evaluations/{id}/valider-rh', [EvaluationController::class, 'validerRh'])
        ->middleware('permission:valider-evaluations');
    Route::post('evaluations/{id}/annuler', [EvaluationController::class, 'annulerFiche'])
        ->middleware('permission:valider-evaluations');
    Route::put('evaluations/{id}/superieur', [EvaluationController::class, 'reattribuerSuperieur'])
        ->middleware('permission:creer-evaluations');
    Route::post('evaluations/{id}/inscrire-tableau', [EvaluationController::class, 'inscrireTableau'])
        ->middleware('permission:valider-evaluations');
    Route::post('evaluations/{id}/retirer-tableau', [EvaluationController::class, 'retirerTableau'])
        ->middleware('permission:valider-evaluations');
    Route::get('evaluations/{id}/fiche-pdf', [EvaluationController::class, 'fichePdf'])
        ->middleware('permission:consulter-evaluations');

    // ---- Réclamations (RH — `valider-evaluations`) ----
    Route::get('reclamations', [ReclamationController::class, 'index'])
        ->middleware('permission:valider-evaluations');
    Route::get('reclamations/en-attente', [ReclamationController::class, 'enAttente'])
        ->middleware('permission:valider-evaluations');
    Route::get('reclamations/{id}', [ReclamationController::class, 'show'])
        ->middleware('permission:valider-evaluations');
    Route::post('reclamations/{id}/traiter', [ReclamationController::class, 'traiter'])
        ->middleware('permission:valider-evaluations');

    // ---- Avis hiérarchiques Phase 3 (CCN art. 64) ----
    Route::get('evaluations/{evaluationId}/avis-hierarchiques', [AvisHierarchiqueController::class, 'indexParEvaluation'])
        ->middleware('permission:consulter-evaluations');
    Route::get('evaluations/{evaluationId}/niveaux-requis', [AvisHierarchiqueController::class, 'niveauxRequis'])
        ->middleware('permission:consulter-evaluations');
    Route::post('evaluations/{evaluationId}/avis-hierarchiques', [AvisHierarchiqueController::class, 'poster'])
        ->middleware('permission:valider-evaluations');
    Route::put('avis-hierarchiques/{id}', [AvisHierarchiqueController::class, 'update'])
        ->middleware('permission:valider-evaluations');
    Route::post('avis-hierarchiques/{id}/signer', [AvisHierarchiqueController::class, 'signer'])
        ->middleware('permission:valider-evaluations');

    // ---- Commission préparatoire Phase 4 (CCN art. 68) ----
    Route::post('sessions/{sessionId}/commission-preparatoire', [CommissionPreparatoireController::class, 'ouvrir'])
        ->middleware('permission:valider-evaluations');
    Route::get('sessions/{sessionId}/commission-preparatoire', [CommissionPreparatoireController::class, 'parSession'])
        ->middleware('permission:consulter-evaluations');
    Route::post('commissions-preparatoires/{id}/noter', [CommissionPreparatoireController::class, 'noter'])
        ->middleware('permission:valider-evaluations');
    Route::get('commissions-preparatoires/{id}/alertes', [CommissionPreparatoireController::class, 'alertes'])
        ->middleware('permission:valider-evaluations');
    Route::post('commissions-preparatoires/{id}/cloturer', [CommissionPreparatoireController::class, 'cloturer'])
        ->middleware('permission:valider-evaluations');
    Route::get('commissions-preparatoires/{id}/synthese-pdf', [CommissionPreparatoireController::class, 'synthesePdf'])
        ->middleware('permission:consulter-evaluations');

    // ---- Commission d'avancement Phase 4 (CCN art. 69-70) ----
    Route::post('sessions/{sessionId}/commission-avancement', [CommissionAvancementController::class, 'ouvrir'])
        ->middleware('permission:valider-evaluations');
    Route::get('sessions/{sessionId}/commission-avancement', [CommissionAvancementController::class, 'parSession'])
        ->middleware('permission:consulter-evaluations');
    Route::post('commissions-avancements/{id}/decider', [CommissionAvancementController::class, 'decider'])
        ->middleware('permission:valider-evaluations');
    Route::post('evaluations/{evaluationId}/avancer-echelon', [CommissionAvancementController::class, 'avancerEchelon'])
        ->middleware('permission:valider-evaluations');
    Route::post('commissions-avancements/{id}/cloturer', [CommissionAvancementController::class, 'cloturer'])
        ->middleware('permission:valider-evaluations');

    // ---- Phase 5.1 — Bonifications stage (CCN art. 71) ----
    Route::get('bonifications-stage', [BonificationStageController::class, 'index'])
        ->middleware('permission:valider-evaluations');
    Route::get('bonifications-stage/en-attente', [BonificationStageController::class, 'enAttente'])
        ->middleware('permission:valider-evaluations');
    Route::post('bonifications-stage', [BonificationStageController::class, 'store'])
        ->middleware('permission:consulter-evaluations');
    Route::post('bonifications-stage/{id}/traiter', [BonificationStageController::class, 'traiter'])
        ->middleware('permission:valider-evaluations');
    Route::post('bonifications-stage/{id}/appliquer', [BonificationStageController::class, 'appliquer'])
        ->middleware('permission:valider-evaluations');

    // ---- Phase 5.2 — Avancements exceptionnels (CCN art. 72) ----
    Route::get('avancements-exceptionnels', [AvancementExceptionnelController::class, 'index'])
        ->middleware('permission:valider-evaluations');
    Route::get('avancements-exceptionnels/en-attente', [AvancementExceptionnelController::class, 'enAttente'])
        ->middleware('permission:valider-evaluations');
    Route::post('avancements-exceptionnels', [AvancementExceptionnelController::class, 'store'])
        ->middleware('permission:valider-evaluations');
    Route::post('avancements-exceptionnels/{id}/traiter', [AvancementExceptionnelController::class, 'traiter'])
        ->middleware('permission:valider-evaluations');
    Route::post('avancements-exceptionnels/{id}/appliquer', [AvancementExceptionnelController::class, 'appliquer'])
        ->middleware('permission:valider-evaluations');

    // ---- Phase 5.3 — Connaissances complémentaires ----
    Route::get('evaluations/{evaluationId}/connaissances', [ConnaissanceComplementaireController::class, 'parEvaluation'])
        ->middleware('permission:consulter-evaluations');
    Route::post('evaluations/{evaluationId}/connaissances', [ConnaissanceComplementaireController::class, 'store'])
        ->middleware('permission:consulter-evaluations');
    Route::delete('connaissances/{id}', [ConnaissanceComplementaireController::class, 'destroy'])
        ->middleware('permission:consulter-evaluations');
});

// ============================================================
// VAGUE F — CLOISONNEMENT PAR BUREAU DRHL
// ============================================================
// Rattachement utilisateur → bureau DRHL.
// Le scope 'scope.bureau' est appliqué sur les listes d'agents
// via le middleware ScopeByBureau (injecte bureau_scope_user dans la Request).
// Seul un admin peut affecter / retirer le bureau d'un utilisateur.
Route::middleware(['auth:sanctum', 'permission:modifier-utilisateurs'])->group(function () {
    // POST   /users/{user}/bureau  → rattache (ou change) le bureau
    Route::post('users/{user}/bureau', [UserController::class, 'rattacherBureau'])
        ->name('users.bureau.rattacher');

    // DELETE /users/{user}/bureau  → retire le bureau (accès global)
    Route::delete('users/{user}/bureau', [UserController::class, 'retirerBureau'])
        ->name('users.bureau.retirer');
});
