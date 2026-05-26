# Roadmap — Gestion RH API
> Construction par module fonctionnel. Chaque module est autonome et livrable indépendamment.  
> Documents de référence : [`instruction_projet.md`](./instruction_projet.md) · [`architecture.md`](./architecture.md) · [`structuration_par_module.md`](./structuration_par_module.md)

---

## Principes de construction

### Architecture obligatoire (rappel)
```
Route → FormRequest → Controller → Service → Repository (via Interface) → Model
```

### Ordre de création dans chaque module
```
Migration(s) → Model(s) → Interface → Repository → Binding IoC → Service → FormRequests → Resource → Controller → Routes
```

### Optimisations transversales
| Levier | Impact |
|---|---|
| `BaseInterface` + `BaseRepository` + `BaseService` + `BaseController` | CRUD générique écrit une seule fois, hérité par tous les modules |
| `Route::apiResource()` | 5 routes CRUD en 1 ligne |
| Migrations groupées par module | 1 fichier par sous-domaine au lieu de N fichiers |
| `UpdateRequest extends CreateRequest` | Zéro duplication des règles de validation |
| Traits Eloquent partagés | `HasAutoSigle`, `HasFilterScope` réutilisables |

---

## Phase 0 — Socle technique (prérequis à tous les modules)

> À réaliser **une seule fois** avant tout module. Fournit les classes de base dont tous les modules héritent.

### 0.1 Installation & configuration

**Dépendances**
```bash
composer require laravel/sanctum:"^4.0" \
    spatie/laravel-permission:"^6.15" \
    barryvdh/laravel-dompdf:"^3.1" \
    darkaonline/l5-swagger:"^9.0"

composer require --dev pestphp/pest:"^3.7" pestphp/pest-plugin-laravel:"^3.1"

php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

**Fichiers de config à modifier**
- `.env` : `DB_DATABASE=gestion_rh_api`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database`
- `config/sanctum.php` : guard `api`, expiration token
- `config/permission.php` : guard `api`, cache activé

### 0.2 Bootstrap & middlewares

**`bootstrap/app.php`** — enregistrement des middlewares alias :
```php
$middleware->alias([
    'permission' => \App\Http\Middleware\CheckPermission::class,
    'role'       => \App\Http\Middleware\CheckRole::class,
]);
```

Fichiers à créer :
```
app/Http/Middleware/
├── CheckPermission.php   # $user->hasPermissionTo() → JSON 403
└── CheckRole.php         # $user->hasRole()        → JSON 403
```

### 0.3 Couche abstraite (le multiplicateur de productivité)

> Ces 4 fichiers éliminent ~80 % du code répétitif dans tous les modules.

```
app/Interfaces/
└── BaseInterface.php           # getAll, findById, create, update, delete

app/Repositories/
└── BaseRepository.php          # Implémente BaseInterface avec Eloquent générique
                                # méthode abstraite model(): string

app/Services/
└── BaseService.php             # Délègue au BaseRepository via BaseInterface
                                # hooks beforeCreate/afterCreate/beforeUpdate/afterUpdate

app/Http/Controllers/API/
└── BaseController.php          # Actions index, show, store, update, destroy
                                # méthode abstraite resource(): string
```

### 0.4 Traits partagés

```
app/Traits/
├── HasAutoSigle.php            # Génération automatique sigle depuis le nom
└── HasFilterScope.php          # Scope filter(array $filters) générique
```

### 0.5 Annotation Swagger globale

```
app/Swagger/
└── OpenApiDefinition.php       # @OA\Info, @OA\SecurityScheme bearerAuth
```

### 0.6 AppServiceProvider (structure initiale)

```
app/Providers/AppServiceProvider.php
```
- `register()` : tableau de bindings IoC (complété à chaque module)
- `boot()` : enregistrement des Observers (complété à chaque module)

---

## Module 1 — Paramétrage & Référentiels

> Premier module à construire. Aucune dépendance externe. Fournit les données de base utilisées par tous les autres modules.

### Sous-module 1.1 — Structures organisationnelles

**Objectif :** hiérarchie Localite → Administration → Direction → Service → Bureau

#### Migrations
```
database/migrations/
└── 0001_create_structure_organisationnelle_tables.php
    # Tables : localites, administrations, directions, services, bureaus
    # Chaque table : id, nom, sigle (nullable auto), description, parent_id (FK), timestamps
```

#### Modèles
```
app/Models/
├── Localite.php              # hasMany(Administration)
├── Administration.php        # belongsTo(Localite), hasMany(Direction), Trait HasAutoSigle
├── Direction.php             # belongsTo(Administration), hasMany(Service), Trait HasAutoSigle
├── Service.php               # belongsTo(Direction), hasMany(Bureau), Trait HasAutoSigle
└── Bureau.php                # belongsTo(Service), Trait HasAutoSigle
```

#### Interfaces & Repositories
```
app/Interfaces/
├── LocaliteInterface.php           # extends BaseInterface (rien à ajouter)
├── AdministrationInterface.php     # + getByLocalite(int $localiteId)
├── DirectionInterface.php          # + getByAdministration(int $adminId), getWithAgents(int $id)
├── ServiceInterface.php            # + getByDirection(int $directionId), getWithAgents(int $id)
└── BureauInterface.php             # + getByService(int $serviceId), getWithAgents(int $id)

app/Repositories/
├── LocaliteRepository.php
├── AdministrationRepository.php
├── DirectionRepository.php
├── ServiceRepository.php
└── BureauRepository.php
```

#### Observer
```
app/Observers/
└── SigleObserver.php    # creating() → génération sigle automatique depuis le nom
                         # Attaché à Administration, Direction, Service, Bureau dans AppServiceProvider::boot()
```

#### Services
```
app/Services/
├── LocaliteService.php           # extends BaseService
├── AdministrationService.php     # extends BaseService
├── DirectionService.php          # extends BaseService + getWithAgents()
├── ServiceService.php            # extends BaseService + getWithAgents()
└── BureauService.php             # extends BaseService + getWithAgents()
```

#### Form Requests
```
app/Http/Requests/
├── Localite/      CreateRequest.php, UpdateRequest.php
├── Administration/ CreateRequest.php, UpdateRequest.php
├── Direction/     CreateRequest.php, UpdateRequest.php
├── ServiceRH/     CreateRequest.php, UpdateRequest.php   # "ServiceRH" pour éviter conflit Laravel Service
└── Bureau/        CreateRequest.php, UpdateRequest.php
```

#### API Resources
```
app/Http/Resources/
├── LocaliteResource.php
├── AdministrationResource.php    # with whenLoaded('directions')
├── DirectionResource.php         # with whenLoaded('services', 'agents')
├── ServiceResource.php           # with whenLoaded('bureaux', 'agents')
└── BureauResource.php
```

#### Controllers
```
app/Http/Controllers/API/
├── LocaliteController.php           # extends BaseController
├── AdministrationController.php     # extends BaseController
├── DirectionController.php          # extends BaseController + agents()
├── ServiceController.php            # extends BaseController + agents()
└── BureauController.php             # extends BaseController + agents()
```

#### Routes (`routes/api.php` — section Module 1.1)
```php
// ============================================================
// MODULE 1.1 — STRUCTURE ORGANISATIONNELLE
// ============================================================
Route::apiResource('localites', LocaliteController::class);
Route::apiResource('administrations', AdministrationController::class);
Route::apiResource('directions', DirectionController::class);
Route::get('/directions/{id}/agents', [DirectionController::class, 'agents']);
Route::apiResource('services', ServiceController::class);
Route::get('/services/{id}/agents', [ServiceController::class, 'agents']);
Route::apiResource('bureaux', BureauController::class);
Route::get('/bureaux/{id}/agents', [BureauController::class, 'agents']);
```

---

### Sous-module 1.2 — Référentiels RH

**Objectif :** tables de paramétrage utilisées par les modules métier.

#### Migration
```
database/migrations/
└── 0002_create_referentiels_rh_tables.php
    # Tables : diplomes, grades, categories, echelons, fonctions,
    #          type_contrats, type_documents, type_recrutements,
    #          type_absences, type_conges, motifs_administratifs
```

#### Modèles (simples, peu de relations)
```
app/Models/
├── Diplome.php
├── Grade.php               # hasMany(Agent)
├── Categorie.php           # hasMany(Agent)
├── Echelon.php             # hasMany(Agent)
├── Fonction.php            # hasMany(Nomination)
├── TypeContrat.php         # hasMany(Contratagent)
├── TypeDocument.php        # hasMany(Document)
├── TypeRecrutement.php     # hasMany(Recrutement)
├── TypeAbsence.php         # hasMany(Absence)
├── TypeConge.php           # hasMany(DemandeConge)
└── MotifAdministratif.php
```

#### Interfaces, Repositories, Services
> Tous héritent de Base* sans ajout (CRUD pur).

```
app/Interfaces/    DiplomeInterface, GradeInterface, CategorieInterface, EchelonInterface,
                   FonctionInterface, TypeContratInterface, TypeDocumentInterface,
                   TypeRecrutementInterface, TypeAbsenceInterface, TypeCongeInterface,
                   MotifAdministratifInterface

app/Repositories/  (idem, héritent BaseRepository)
app/Services/      (idem, héritent BaseService)
```

#### Form Requests, Resources, Controllers
> Structure identique pour chaque référentiel : CreateRequest, UpdateRequest, Resource, Controller.

#### Routes (`routes/api.php` — section Module 1.2)
```php
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
```

---

### Sous-module 1.3 — Administration système

**Objectif :** gestion des utilisateurs, rôles, permissions et journal d'audit.

#### Migration
```
database/migrations/
└── 0003_create_system_admin_tables.php
    # Tables : users (modifier), personal_access_tokens, audit_logs, parametres_application
    # + tables Spatie : roles, permissions, model_has_roles, model_has_permissions, role_has_permissions
```

#### Modèles
```
app/Models/
├── User.php              # HasApiTokens, HasRoles (Spatie), belongsTo(Agent, 'agent_id')
├── AuditLog.php          # polymorphique : loggable_type, loggable_id, action, user_id
└── ParametreApplication.php  # clé/valeur système
```

#### Services spécifiques
```
app/Services/
├── AuthService.php           # login() → token Sanctum, logout() → révocation
├── UserService.php           # createUser(), assignRole(), revokeRole(), activer(), desactiver()
├── RoleService.php           # CRUD + dupliquer()
├── PermissionService.php     # CRUD + assignToRole()
├── AuditLogService.php       # log(string $action, Model $entity, User $user)
└── ParametreApplicationService.php  # get(string $cle), set(string $cle, mixed $valeur)
```

#### Controllers & Routes
```php
// ============================================================
// MODULE 1.3 — AUTH & ADMINISTRATION SYSTÈME
// ============================================================
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::apiResource('users', UserController::class)->middleware([
        'index'   => 'permission:consulter-utilisateurs',
        'store'   => 'permission:creer-utilisateurs',
        'update'  => 'permission:modifier-utilisateurs',
        'destroy' => 'permission:supprimer-utilisateurs',
    ]);

    Route::apiResource('roles', RoleController::class)
        ->middleware('index', 'permission:consulter-roles');
    Route::post('/roles/{id}/dupliquer', [RoleController::class, 'dupliquer']);

    Route::get('/permissions', [PermissionController::class, 'index']);

    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('role:admin');

    Route::apiResource('parametres-application', ParametreApplicationController::class)
        ->middleware('role:admin');
});
```

#### Seeder Module 1
```
database/seeders/
├── RoleSeeder.php              # Rôles : admin, rh, directeur, chef-service, agent
├── PermissionSeeder::class     # Toutes les permissions par module
├── LocaliteSeeder.php
├── AdministrationSeeder.php
├── DirectionSeeder.php
├── ServiceSeeder.php
├── BureauSeeder.php
├── GradeSeeder.php
├── CategorieSeeder.php
├── EchelonSeeder.php
├── FonctionSeeder.php
├── TypeContratSeeder.php
├── TypeDocumentSeeder.php
├── TypeCongeSeeder.php
└── MotifAdministratifSeeder.php
```

#### Tests Module 1
```
tests/Feature/
├── Auth/
│   └── AuthTest.php            # login valide, invalide, logout, token révoqué
├── Structure/
│   └── StructureOrgTest.php    # CRUD localites, administrations, sigle auto
└── Admin/
    └── UserRolePermissionTest.php  # CRUD users, assignation rôles, accès par permission
```

---

## Module 2 — Gestion du Personnel / Dossier Agent

> Dépend du Module 1 (structure org. + référentiels).

### Sous-module 2.1 — Fiche agent

#### Migration
```
database/migrations/
└── 0004_create_agents_tables.php
    # Tables :
    # agents              : matricule, nom, prenom, date_naissance, lieu_naissance,
    #                       nationalite, sexe, situation_familiale, statut, grade_id,
    #                       categorie_id, echelon_id, date_entree, timestamps, softDeletes
    # informationspersonnelles : agent_id (FK), adresse, ville, code_postal, pays,
    #                            telephone, email_perso, timestamps
    # informationsprofessionnelles : agent_id (FK), diplome_id, niveau_etude,
    #                                specialite, experience, timestamps
    # contacturgences     : agent_id (FK), nom, prenom, telephone, relation, timestamps
    # situation_familiales : agent_id (FK), statut_matrimonial, nb_enfants, timestamps
```

#### Modèles
```
app/Models/
├── Agent.php                        # hasOne(Contratagent, actif), hasOne(Affectation, active)
│                                    # hasOne(Nomination, active), hasOne(User, 'agent_id')
│                                    # hasMany(Document), hasMany(Sanction)
│                                    # hasOne(InformationsPersonnelle), hasOne(InformationsProfessionnelle)
│                                    # hasOne(ContactUrgence), hasOne(SituationFamiliale)
│                                    # hasMany(AffiliationSecuriteSociale)
│                                    # scope actif(), scope enRecrutement()
├── InformationsPersonnelle.php      # belongsTo(Agent)
├── InformationsProfessionnelle.php  # belongsTo(Agent), belongsTo(Diplome)
├── ContactUrgence.php               # belongsTo(Agent)
└── SituationFamiliale.php           # belongsTo(Agent)
```

#### Interface & Repository
```
app/Interfaces/
└── AgentInterface.php    # extends BaseInterface
                          # + findByMatricule(string $mat)
                          # + getWithRelations(int $id, array $relations)
                          # + filter(array $filters)  ← search, grade, statut, direction

app/Repositories/
└── AgentRepository.php
```

#### Service
```
app/Services/
└── AgentService.php    # generateMatricule() : génération automatique matricule unique
                        # getWithRelations()
                        # filter()
```

#### Form Requests
```
app/Http/Requests/Agent/
├── CreateRequest.php       # nom, prenom, date_naissance, sexe, grade_id...
├── UpdateRequest.php       # extends CreateRequest avec sometimes
└── FilterRequest.php       # search, grade_id, statut, direction_id, per_page

app/Http/Requests/InformationsPersonnelles/
├── CreateRequest.php
└── UpdateRequest.php

app/Http/Requests/InformationsProfessionnelles/
├── CreateRequest.php
└── UpdateRequest.php

app/Http/Requests/ContactUrgence/
├── CreateRequest.php
└── UpdateRequest.php
```

#### API Resource
```
app/Http/Resources/
└── AgentResource.php    # id, matricule, nom, prenom, grade, categorie, statut
                         # whenLoaded: contratActif, affectationActive, nominationActive,
                         #             informationsPersonnelles, informationsProfessionnelles
```

#### Routes
```php
// ============================================================
// MODULE 2.1 — AGENTS
// ============================================================
Route::apiResource('agents', AgentController::class);
Route::prefix('agents/{agent}')->group(function () {
    Route::apiResource('informations-personnelles',      InformationsPersonnellesController::class)->only(['index','store','update']);
    Route::apiResource('informations-professionnelles',  InformationsProfessionnellesController::class)->only(['index','store','update']);
    Route::apiResource('contacts-urgence',               ContactUrgenceController::class)->only(['index','store','update']);
    Route::apiResource('situation-familiale',            SituationFamilialeController::class)->only(['index','store','update']);
});
```

---

### Sous-module 2.2 — Gestion documentaire agent (GED)

**Objectif :** dossier numérique de l'agent (CNI, diplômes, CV, actes, photos…).

#### Migration
```
database/migrations/
└── 0005_create_documents_table.php
    # Table documents : agent_id, type_document_id, titre, fichier (path),
    #                   contexte, sous_dossier, ordre, taille, mime_type,
    #                   timestamps, softDeletes
```

#### Modèle
```
app/Models/
└── Document.php    # belongsTo(Agent), belongsTo(TypeDocument)
                    # scope parContexte(), parType()
```

#### Service spécifique
```
app/Services/
└── DocumentService.php    # upload(Agent, file, array $meta) : stockage + enregistrement
                           # uploadMultiple(Agent, array $files)
                           # deplacer(Document, string $nouveauSousDossier)
                           # reordonner(array $ids, array $ordres)
                           # arborescence(Agent) : retourne structure dossiers/fichiers
```

#### Routes
```php
// ============================================================
// MODULE 2.2 — DOCUMENTS AGENT
// ============================================================
Route::prefix('agents/{agent}')->group(function () {
    Route::apiResource('documents', DocumentController::class);
    Route::post('/documents/{document}/deplacer', [DocumentController::class, 'deplacer']);
    Route::post('/documents/reordonner', [DocumentController::class, 'reordonner']);
    Route::get('/documents/arborescence', [DocumentController::class, 'arborescence']);
});
```

---

### Sous-module 2.3 — Compte utilisateur lié

```php
// ============================================================
// MODULE 2.3 — COMPTE UTILISATEUR LIÉ À L'AGENT
// ============================================================
Route::prefix('agents/{agent}')->middleware('auth:sanctum')->group(function () {
    Route::post('/compte', [AgentCompteController::class, 'creer']);
    Route::patch('/compte/activer', [AgentCompteController::class, 'activer']);
    Route::patch('/compte/desactiver', [AgentCompteController::class, 'desactiver']);
});
```

#### Seeder Module 2
```
database/seeders/
├── AgentSeeder.php
├── InformationspersonnelleSeeder.php
├── InformationsprofessionnelleSeeder.php
├── ContacturgenceSeeder.php
├── SituationFamilialeSeeder.php
├── DocumentSeeder.php
└── UserSeeder.php
```

#### Tests Module 2
```
tests/Feature/
├── Agent/
│   ├── AgentCrudTest.php           # CRUD, matricule auto, filtres, pagination
│   ├── AgentRelationsTest.php      # infos perso, pro, contacts
│   └── DocumentTest.php            # upload, arborescence, déplacement
```

---

## Module 3 — Entrée dans l'administration

> Dépend des Modules 1 et 2.

### Sous-module 3.1 — Recrutement externe

#### Migration
```
database/migrations/
└── 0006_create_recrutement_tables.php
    # Tables :
    # demande_recrutements  : direction_id, poste, nb_postes, justification,
    #                         statut (en_attente|validé|rejeté), demandeur_id, timestamps
    # appels_candidatures   : demande_id, titre, description, date_ouverture, date_cloture,
    #                         statut, timestamps
    # candidats             : nom, prenom, email, telephone, cv_path, statut, timestamps
    # candidatures          : candidat_id, appel_id, statut, note, timestamps
```

#### Services
```
app/Services/
├── DemandeRecrutementService.php    # valider(), rejeter()
├── AppelCandidatureService.php      # publier(), cloture()
├── CandidatService.php              # CRUD + uploadCV()
└── CandidatureService.php           # trier(), shortlister()
```

#### Routes
```php
// ============================================================
// MODULE 3.1 — RECRUTEMENT EXTERNE
// ============================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('demandes-recrutement', DemandeRecrutementController::class);
    Route::post('/demandes-recrutement/{id}/valider', [DemandeRecrutementController::class, 'valider']);
    Route::post('/demandes-recrutement/{id}/rejeter', [DemandeRecrutementController::class, 'rejeter']);

    Route::apiResource('appels-candidatures', AppelCandidatureController::class);
    Route::post('/appels-candidatures/{id}/publier',  [AppelCandidatureController::class, 'publier']);
    Route::post('/appels-candidatures/{id}/cloturer', [AppelCandidatureController::class, 'cloturer']);

    Route::apiResource('candidats', CandidatController::class);
    Route::apiResource('candidatures', CandidatureController::class);
    Route::post('/candidatures/{id}/shortlister', [CandidatureController::class, 'shortlister']);
});
```

---

### Sous-module 3.2 — Autres modes d'intégration

#### Migration
```
database/migrations/
└── 0007_create_integrations_table.php
    # Table integrations : agent_id, type (mutation|detachement|mise_a_disposition|
    #                      integration_directe|reintegration), origine, destination,
    #                      date_effet, date_fin (nullable), statut, documents, timestamps
```

#### Routes
```php
// ============================================================
// MODULE 3.2 — INTÉGRATIONS (MUTATION, DÉTACHEMENT, ETC.)
// ============================================================
Route::apiResource('integrations', IntegrationController::class);
Route::post('/integrations/{id}/valider', [IntegrationController::class, 'valider']);
```

---

### Sous-module 3.3 — Workflow d'intégration (recrutement interne 5 étapes)

> Reprend le workflow documenté dans `instruction_projet.md` section Module 4.

#### Service
```
app/Services/RecrutementWorkflowService.php
    # etape1_creerAgent(array $data)
    # etape2_completerInfos(Agent $agent, array $data)
    # etape3_creerContrat(Agent $agent, array $data)
    #   → déclenche : affectation auto DRHL + nomination auto + salaire initial + notification
    # etape4_uploadDocuments(Agent $agent, array $fichiers)
    # etape5_integrer(Agent $agent)
    # enCours() : agents en cours d'intégration
    # statistiques()
```

#### Routes
```php
// ============================================================
// MODULE 3.3 — WORKFLOW D'INTÉGRATION
// ============================================================
Route::prefix('recrutement')->group(function () {
    Route::post('/agents',                             [RecrutementWorkflowController::class, 'creerAgent']);
    Route::post('/agents/{agent}/informations',        [RecrutementWorkflowController::class, 'completerInfos']);
    Route::post('/agents/{agent}/contrats',            [RecrutementWorkflowController::class, 'creerContrat']);
    Route::post('/agents/{agent}/documents',           [RecrutementWorkflowController::class, 'uploadDocuments']);
    Route::get('/agents/{agent}/documents',            [RecrutementWorkflowController::class, 'listDocuments']);
    Route::delete('/agents/{agent}/documents/{doc}',   [RecrutementWorkflowController::class, 'supprimerDocument']);
    Route::post('/agents/{agent}/integrer',            [RecrutementWorkflowController::class, 'integrer']);
    Route::get('/en-cours',                            [RecrutementWorkflowController::class, 'enCours']);
    Route::get('/statistiques',                        [RecrutementWorkflowController::class, 'statistiques']);
});
```

#### Tests Module 3
```
tests/Feature/Recrutement/
├── RecrutementExterneTest.php      # Workflow demande → appel → candidature → sélection
└── RecrutementWorkflowTest.php     # Workflow 5 étapes, automatismes contrat
```

---

## Module 4 — Contrats & Situation Administrative

> Dépend des Modules 1, 2, 3. Contient les automatismes les plus critiques.

### Sous-module 4.1 — Contrats

#### Migration
```
database/migrations/
└── 0008_create_contrats_affectations_nominations_tables.php
    # Tables :
    # contratagents    : agent_id, type_contrat_id, type (CDI|CDD|Stage|Consultant),
    #                    date_debut, date_fin (nullable), statut (actif|suspendu|terminé),
    #                    poste, salaire_base, timestamps, softDeletes
    # affectations     : agent_id, direction_id, service_id (nullable), bureau_id (nullable),
    #                    date_debut, date_fin (nullable), statut, motif, timestamps
    # nominations      : agent_id, fonction_id, date_debut, date_fin (nullable),
    #                    statut, decision_ref, timestamps
```

#### Services (critiques — contiennent les automatismes)
```
app/Services/
├── ContratagentService.php
│   # create() déclenche OBLIGATOIREMENT :
│   #   1. AffectationService::affecterAutomatiquement($agent, $directionDRHL)
│   #   2. NominationService::nommerAutomatiquement($agent, $fonction)
│   #      → "Agent" si CDI/CDD, "Stagiaire" si Stage, rien si Consultant
│   #   3. SalaireService::creerSalaireInitial($agent, $contrat) si CDI/CDD
│   #   4. NotificationService::notifier($agent, 'contrat_cree', $contrat)
│   # suspendre(), terminer(), reconduire()
│
├── AffectationService.php
│   # affecterAutomatiquement(Agent, Direction)
│   # affecter(Agent, array $data) : cloture l'affectation précédente
│   # historique(Agent)
│
└── NominationService.php
    # nommerAutomatiquement(Agent, Fonction)
    # nommer(Agent, array $data) : cloture la nomination précédente
    # getPostesVacants()
    # getAgentsSousAutorite(int $chefId)
```

#### Routes
```php
// ============================================================
// MODULE 4.1 — CONTRATS
// ============================================================
Route::apiResource('contrats', ContratagentController::class);
Route::prefix('contrats/{contrat}')->group(function () {
    Route::post('/suspendre',  [ContratagentController::class, 'suspendre']);
    Route::post('/terminer',   [ContratagentController::class, 'terminer']);
    Route::post('/reconduire', [ContratagentController::class, 'reconduire']);
});

// ============================================================
// MODULE 4.1 — AFFECTATIONS
// ============================================================
Route::prefix('agents/{agent}')->group(function () {
    Route::apiResource('affectations', AffectationController::class);
});

// ============================================================
// MODULE 4.1 — NOMINATIONS
// ============================================================
Route::apiResource('nominations', NominationController::class);
Route::get('/nominations/agent/{id}',               [NominationController::class, 'parAgent']);
Route::get('/nominations/historique/agent/{id}',    [NominationController::class, 'historique']);
Route::get('/nominations/chefs/{id}/sous-autorite', [NominationController::class, 'sousAutorite']);
Route::get('/nominations/postes-vacants',           [NominationController::class, 'postesVacants']);
```

---

### Sous-module 4.2 — Carrière (grades & échelons)

```
app/Services/
└── CarriereService.php    # changerGrade(Agent, Grade), changerEchelon(Agent, Echelon)
                           # historique(Agent) : retourne tous les changements de grade/échelon
                           # getEligiblesAvancement() : agents éligibles selon ancienneté
```

```php
// ============================================================
// MODULE 4.2 — CARRIÈRE
// ============================================================
Route::prefix('agents/{agent}')->middleware('auth:sanctum')->group(function () {
    Route::post('/changer-grade',   [CarriereController::class, 'changerGrade']);
    Route::post('/changer-echelon', [CarriereController::class, 'changerEchelon']);
    Route::get('/historique-carriere', [CarriereController::class, 'historique']);
});
```

---

### Sous-module 4.3 — Notes administratives

#### Migration
```
database/migrations/
└── 0009_create_notes_administratives_table.php
    # Table notes_administratives : agent_id (nullable), type (note_service|decision|arrete),
    #                               titre, reference, contenu, date_effet, auteur_id, timestamps
```

```php
// ============================================================
// MODULE 4.3 — NOTES ADMINISTRATIVES
// ============================================================
Route::apiResource('notes-administratives', NoteAdministrativeController::class);
```

#### Job planifié (contrats en fin de date)
```
app/Jobs/
└── ContratEnFinDateJob.php    # Alerte 15 jours avant échéance, tous jours ouvrables à 08:00

// routes/console.php
Schedule::job(new ContratEnFinDateJob(15))->weekdays()->at('08:00')->withoutOverlapping();
```

#### Tests Module 4
```
tests/Feature/Contrats/
├── ContratagentTest.php        # Création + automatismes (affectation, nomination, salaire)
├── AffectationTest.php         # Affectation manuelle, historique
└── NominationTest.php          # Nomination, postes vacants, sous-autorité
```

---

## Module 5 — Salaires & Grilles Salariales

> Dépend du Module 4 (contrats).

#### Migration
```
database/migrations/
└── 0010_create_salaires_tables.php
    # Tables :
    # classegrillesalariales  : nom, description, timestamps
    # parametregrilles        : classe_id, echelon, coefficient, indice, timestamps
    # salaires                : nom, description, base_calcul, timestamps
    # salaires_agents         : agent_id, salaire_id, classe_id, echelon, montant_base,
    #                           montant_net, date_debut, date_fin (nullable), statut, timestamps
```

#### Service
```
app/Services/
├── ClasseGrilleService.php         # CRUD
├── ParametreGrilleService.php      # CRUD
├── SalaireService.php              # CRUD + creerSalaireInitial(Agent, Contratagent)
└── SalaireAgentService.php         # cloturerActuel(Agent), avancerEchelon(Agent)
                                    # getMontantActuel(Agent)
```

#### Routes
```php
// ============================================================
// MODULE 5 — SALAIRES
// ============================================================
Route::apiResource('salaires', SalaireController::class);
Route::apiResource('salaires-agents', SalaireAgentController::class);
Route::apiResource('classes-grilles', ClasseGrilleController::class);
Route::apiResource('parametres-grilles', ParametreGrilleController::class);
Route::post('/salaires-agents/{id}/cloturer', [SalaireAgentController::class, 'cloturer']);
```

---

## Module 6 — Congés & Absences

> Dépend des Modules 1, 2, 4. Module avec la logique métier de calcul la plus complexe.

### Sous-module 6.1 — Paramétrage congés

#### Migration
```
database/migrations/
└── 0011_create_conges_tables.php
    # Tables :
    # jour_feries               : nom, date, recurrent (bool), timestamps
    # regle_acquisition_conges  : type_conge_id, jours_par_mois, jours_max, timestamps
    # conge_soldes              : agent_id, type_conge_id, solde_initial, solde_actuel,
    #                             annee, timestamps
    # demande_conges            : agent_id, type_conge_id, date_debut, date_fin,
    #                             nb_jours (calculé), motif, statut, timestamps
    # justificatifs             : demande_id, fichier_path, timestamps
    # validation_conges         : demande_id, validateur_id, niveau (n1|rh), decision,
    #                             commentaire, timestamps
    # notification_conges       : demande_id, destinataire_id, type, lu, timestamps
    # document_conges           : demande_id, type (fiche|attestation), path, timestamps
```

#### Service (logique de calcul critique)
```
app/Services/
├── JourFerieService.php             # CRUD + estFerie(Carbon $date)
├── RegleAcquisitionCongeService.php # CRUD
├── CongeSoldeService.php            # calculerSolde(Agent, TypeConge, int $annee)
│                                    # debiter(Agent, int $jours)
│                                    # crediter(Agent, int $jours)
│
└── DemandeCongeService.php          # Le service le plus complexe du module
    # calculerJoursOuvrables(date_debut, date_fin)
    #   → CarbonPeriod excluant week-ends + jours fériés
    # verifierSolde(Agent, TypeConge, int $jours)
    # soumettre(Agent, array $data)
    # validerN1(DemandeConge, User $validateur) → notifier agent
    # rejeterN1(DemandeConge, User $validateur) → notifier agent
    # validerRH(DemandeConge, User $validateur) → notifier agent, générer PDF
    # rejeterRH(DemandeConge, User $validateur) → notifier agent
    # statistiques(array $filters)
```

### Sous-module 6.2 — Absences

#### Migration (dans 0011 ou séparé)
```
# Table absences : agent_id, type_absence_id, date_debut, date_fin, justifiee (bool),
#                  motif, nb_jours, statut, timestamps
```

#### Routes
```php
// ============================================================
// MODULE 6 — CONGÉS & ABSENCES
// ============================================================
Route::apiResource('jours-feries', JourFerieController::class);
Route::apiResource('regles-acquisition-conges', RegleAcquisitionCongeController::class);
Route::apiResource('conge-soldes', CongeSoldeController::class);

Route::apiResource('demandes-conges', DemandeCongeController::class);
Route::prefix('demandes-conges/{id}')->group(function () {
    Route::post('/valider-n1',  [DemandeCongeController::class, 'validerN1']);
    Route::post('/rejeter-n1',  [DemandeCongeController::class, 'rejeterN1']);
    Route::post('/valider-rh',  [DemandeCongeController::class, 'validerRH']);
    Route::post('/rejeter-rh',  [DemandeCongeController::class, 'rejeterRH']);
    Route::get('/fiche-pdf',    [DemandeCongeController::class, 'fichePdf']);
    Route::get('/attestation',  [DemandeCongeController::class, 'attestation']);
});

Route::middleware('auth:sanctum')->get('/statistiques-conges', [DemandeCongeController::class, 'statistiques']);

Route::apiResource('absences', AbsenceController::class);
```

#### PDF Congés
```
resources/views/pdf/
├── fiche-conge.blade.php
└── attestation-conge.blade.php

app/Services/
└── PdfCongeService.php    # genererFicheConge(DemandeConge)
                           # genererAttestationConge(DemandeConge)
```

#### Tests Module 6
```
tests/Feature/Conges/
└── DemandeCongeTest.php    # Calcul jours ouvrables, workflow N+1→RH, notifications, PDF
tests/Unit/
└── DemandeCongeServiceTest.php    # Cas limites calcul : pont, semaine fériée, solde insuffisant
```

---

## Module 7 — Évaluation des Performances & Avancements

> Module le plus complexe. Dépend des Modules 1, 2, 4. Toutes les routes sous `auth:sanctum`.

### Sous-module 7.1 — Campagnes d'évaluation

#### Migration
```
database/migrations/
└── 0012_create_evaluations_tables.php
    # Tables :
    # session_evaluations         : titre, annee, date_debut, date_fin, statut
    #                               (brouillon|ouverte|cloturee), timestamps
    # question_evaluations        : libelle, type (competence|assiduite|relation),
    #                               coefficient, ordre, actif, timestamps
    # evaluations                 : session_id, agent_id, evaluateur_id, statut
    #                               (en_attente|en_cours|soumis|valide), timestamps
    # note_evaluations            : evaluation_id, question_id, note, commentaire, timestamps
    # reclamations                : evaluation_id, agent_id, motif, statut, timestamps
    # connaissances_complementaires : evaluation_id, description, timestamps
    # avis_hierarchiques          : evaluation_id, auteur_id, role (chef_service|directeur),
    #                               avis, timestamps
    # note_syntheses              : evaluation_id, note_finale (/20), appreciation,
    #                               recommandation, timestamps
    # commissions_preparatoires   : session_id, date, membres, pv_path, timestamps
    # commissions_avancements     : session_id, date, decision, timestamps
```

### Sous-module 7.2 — Workflow évaluation (12 étapes)

```
app/Services/
├── SessionEvaluationService.php
│   # ouvrir(SessionEvaluation) → génère automatiquement 1 fiche par agent
│   # cloturer(SessionEvaluation)
│   # getEligibles(Session) : agents avec supérieur hiérarchique actif
│
├── EvaluationService.php
│   # genererFiche(Agent, Session)
│   # noter(Evaluation, array $notes) : calcul note /20 automatique
│   #   Compétences /10 + Assiduité /3 + Relations /7
│   # signer(Evaluation, Agent) → notifier supérieur
│   # reclamer(Evaluation, Agent, string $motif) → notifier supérieur
│   # valider(Evaluation, User) → notifier agent
│
├── SuperieurHierarchiqueService.php
│   # getSuperieur(Agent) : trouve le chef de service ou directeur
│   # validerEvaluation(Evaluation) → notifier agent
│   # getAgentsSousAutorite(User)
│
├── AvisHierarchiqueService.php    # soumettre(Evaluation, User, string $avis)
│
├── NoteSyntheseService.php
│   # generer(Evaluation) : compile les notes et avis
│   # exportPdf(NoteSynthese) → DomPDF
│
├── CommissionPreparatoireService.php  # CRUD + genererPV()
│
└── CommissionAvancementService.php
    # decision(Commission, string $decision) : accordé|différé|maintien
```

#### Routes
```php
// ============================================================
// MODULE 7 — ÉVALUATIONS & AVANCEMENTS (auth:sanctum requis)
// ============================================================
Route::middleware('auth:sanctum')->prefix('avancements')->group(function () {

    // Sessions
    Route::apiResource('sessions', SessionEvaluationController::class);
    Route::post('/sessions/{id}/ouvrir',   [SessionEvaluationController::class, 'ouvrir']);
    Route::post('/sessions/{id}/cloturer', [SessionEvaluationController::class, 'cloturer']);

    // Questions
    Route::apiResource('questions-evaluation', QuestionEvaluationController::class);

    // Évaluations
    Route::apiResource('evaluations', EvaluationController::class);
    Route::prefix('evaluations/{evaluation}')->group(function () {
        Route::post('/noter',    [EvaluationController::class, 'noter']);
        Route::post('/signer',   [EvaluationController::class, 'signer']);
        Route::post('/valider',  [EvaluationController::class, 'valider']);
        Route::apiResource('reclamations', ReclamationController::class)->only(['index','store']);
        Route::apiResource('connaissances', ConnaissanceComplementaireController::class)->only(['index','store']);
    });

    // Avis hiérarchiques
    Route::apiResource('avis-hierarchiques', AvisHierarchiqueController::class);

    // Validation RH
    Route::post('/validation-rh/{evaluation}', [ValidationRhController::class, 'valider']);

    // Notes de synthèse
    Route::apiResource('notes-synthese', NoteSyntheseController::class);
    Route::get('/notes-synthese/{id}/pdf', [NoteSyntheseController::class, 'exportPdf']);

    // Commissions
    Route::apiResource('commissions', CommissionPreparatoireController::class);
    Route::apiResource('commissions-avancement', CommissionAvancementController::class);
    Route::post('/commissions-avancement/{id}/decision', [CommissionAvancementController::class, 'decision']);

    // Rapports
    Route::get('/rapports', [RapportAvancementController::class, 'index']);
});
```

#### PDF Évaluation
```
resources/views/pdf/
└── note-synthese.blade.php

app/Services/
└── PdfEvaluationService.php    # genererNoteSynthese(NoteSynthese)
```

#### Tests Module 7
```
tests/Feature/Evaluations/
└── EvaluationWorkflowTest.php    # Workflow 12 étapes complet
tests/Unit/
└── EvaluationServiceTest.php     # Calcul note /20, coefficients, cas limites
```

---

## Module 8 — Formation & Développement

> Nouveau module. Dépend des Modules 1 et 2.

#### Migration
```
database/migrations/
└── 0013_create_formations_tables.php
    # Tables :
    # formations           : titre, description, organisme, duree_jours, cout,
    #                        type (interne|externe), timestamps
    # plans_formation      : annee, statut, description, timestamps
    # plan_formation_items : plan_id, formation_id, agent_id, priorite, timestamps
    # inscriptions         : agent_id, formation_id, date_inscription, statut,
    #                        date_debut, date_fin, timestamps
    # certifications       : agent_id, formation_id, date_obtention, reference,
    #                        fichier_path, timestamps
```

#### Services
```
app/Services/
├── FormationService.php         # CRUD catalogue
├── PlanFormationService.php     # CRUD + valider()
├── InscriptionService.php       # inscrire(), annuler(), confirmerPresence()
└── CertificationService.php     # CRUD + uploadCertificat()
```

#### Routes
```php
// ============================================================
// MODULE 8 — FORMATION & DÉVELOPPEMENT
// ============================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('formations', FormationController::class);
    Route::apiResource('plans-formation', PlanFormationController::class);
    Route::apiResource('inscriptions-formations', InscriptionController::class);
    Route::post('/inscriptions-formations/{id}/confirmer', [InscriptionController::class, 'confirmer']);
    Route::apiResource('certifications', CertificationController::class);
    Route::get('/agents/{agent}/formations', [FormationController::class, 'parAgent']);
});
```

---

## Module 9 — Discipline & Contentieux

> Dépend des Modules 1 et 2.

#### Migration
```
database/migrations/
└── 0014_create_sanctions_tables.php
    # Tables :
    # type_sanctions          : nom, gravite (leger|moyen|grave), description, timestamps
    # sanctions               : agent_id, type_sanction_id, motif, date_faits, date_decision,
    #                           decision, statut (en_attente|validée|rejetée),
    #                           validateur_id, timestamps, softDeletes
    # avertissements          : agent_id, motif, date, emetteur_id, timestamps
    # procedures_disciplinaires : sanction_id, etape, description, date, responsable_id, timestamps
```

#### Services
```
app/Services/
├── TypeSanctionService.php          # CRUD
├── SanctionService.php              # CRUD + valider() + rejeter()
│                                    # historique(Agent)
├── AvertissementService.php         # CRUD
└── ProcedureDisciplinaireService.php # CRUD + avancerEtape()
```

#### Routes
```php
// ============================================================
// MODULE 9 — DISCIPLINE & CONTENTIEUX
// ============================================================
Route::apiResource('types-sanctions', TypeSanctionController::class);
Route::apiResource('sanctions', SanctionController::class);
Route::get('/sanctions/agent/{agentId}', [SanctionController::class, 'parAgent']);
Route::post('/sanctions/{id}/valider', [SanctionController::class, 'valider']);
Route::post('/sanctions/{id}/rejeter', [SanctionController::class, 'rejeter']);
Route::apiResource('avertissements', AvertissementController::class);
Route::apiResource('procedures-disciplinaires', ProcedureDisciplinaireController::class);
```

---

## Module 10 — Tableau de Bord & Reporting

> Dépend de tous les modules précédents. Routes en lecture seule.

#### Service
```
app/Services/
├── DashboardService.php        # effectifTotal(), repartitionParDirection()
│                               # repartitionParGrade(), repartitionParSexe()
│                               # repartitionParAge(), congesEnCours(), evaluationsEnCours()
│
└── RapportService.php          # genererRapportEffectifs(array $filters) → PDF
                                # genererRapportConges(array $filters) → PDF
                                # genererRapportEvaluations(array $filters) → PDF
                                # exportExcel(string $type, array $filters) → Excel/CSV
```

#### Routes
```php
// ============================================================
// MODULE 10 — TABLEAU DE BORD & REPORTING
// ============================================================
Route::middleware('auth:sanctum')->prefix('reporting')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/effectifs', [DashboardController::class, 'effectifs']);
    Route::get('/repartitions', [DashboardController::class, 'repartitions']);

    // Statistiques
    Route::get('/stats/genre',      [StatistiqueController::class, 'genre']);
    Route::get('/stats/age',        [StatistiqueController::class, 'age']);
    Route::get('/stats/directions', [StatistiqueController::class, 'directions']);
    Route::get('/stats/conges',     [StatistiqueController::class, 'conges']);
    Route::get('/stats/evaluations',[StatistiqueController::class, 'evaluations']);

    // Exports
    Route::get('/rapports/effectifs',   [RapportController::class, 'effectifs']);
    Route::get('/rapports/conges',      [RapportController::class, 'conges']);
    Route::get('/rapports/evaluations', [RapportController::class, 'evaluations']);
    Route::get('/exports/csv/{type}',   [RapportController::class, 'exportCsv']);
});
```

---

## Module 11 — Notifications & Communication

> Transversal. Utilisé par tous les modules dès la Phase 0.

#### Migration (déjà créée par Laravel)
```
# Table notifications : polymorphique Laravel (notifiable_type, notifiable_id, type, data, read_at)
```

#### Notifications Laravel
```
app/Notifications/
├── ContratCreerNotification.php
├── ContratFinProchainNotification.php
├── EvaluationSigneeNotification.php
├── EvaluationReclameeNotification.php
├── EvaluationValideeNotification.php
├── CongeValideN1Notification.php
├── CongeRejeteN1Notification.php
├── CongeValideRHNotification.php
├── CongeRejeteRHNotification.php
└── RecrutementDecisionNotification.php
```

#### Service
```
app/Services/
└── NotificationService.php    # notifier(User $destinataire, string $type, mixed $payload)
                               # notifierGroupe(Collection $destinataires, ...)
                               # marquerLu(Notification)
                               # getNonLues(User)
```

#### Routes
```php
// ============================================================
// MODULE 11 — NOTIFICATIONS
// ============================================================
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/',           [NotificationController::class, 'index']);
    Route::get('/non-lues',   [NotificationController::class, 'nonLues']);
    Route::post('/{id}/lu',   [NotificationController::class, 'marquerLu']);
    Route::post('/tout-lire', [NotificationController::class, 'toutLire']);
});
```

#### Job d'alerte
```
app/Jobs/
└── ContratEnFinDateJob.php    # Alerte 15j avant échéance, weekdays à 08:00

// routes/console.php
Schedule::job(new ContratEnFinDateJob(15))->weekdays()->at('08:00')->withoutOverlapping();
```

---

## Module 12 — GED RH (Gestion Électronique de Documents)

> Étend le sous-module 2.2. Ajoute archivage, versioning, recherche.

#### Migration
```
database/migrations/
└── 0015_create_ged_tables.php
    # Tables :
    # document_versions   : document_id, version, fichier_path, taille, createur_id, timestamps
    # document_archives   : document_id, raison, archiveur_id, timestamps
    # document_recherches : (table de recherche full-text ou index)
```

#### Service
```
app/Services/
└── GedService.php    # archiverDocument(Document)
                      # creerVersion(Document, file)
                      # getVersions(Document)
                      # restaurerVersion(DocumentVersion)
                      # rechercherDocuments(string $query, array $filters)
```

#### Routes
```php
// ============================================================
// MODULE 12 — GED RH
// ============================================================
Route::middleware('auth:sanctum')->prefix('ged')->group(function () {
    Route::get('/recherche', [GedController::class, 'recherche']);
    Route::prefix('documents/{document}')->group(function () {
        Route::get('/versions',                [GedController::class, 'versions']);
        Route::post('/versions',               [GedController::class, 'creerVersion']);
        Route::post('/versions/{v}/restaurer', [GedController::class, 'restaurerVersion']);
        Route::post('/archiver',               [GedController::class, 'archiver']);
    });
});
```

---

## Modules complémentaires identifiés

### Sécurité sociale
```
# Migration : 0016_create_securite_sociale_tables.php
# Tables : organisme_securite_sociales, affiliation_securite_sociales
# Routes : apiResource organismes-securite-sociale, affiliations-securite-sociale
```

### Représentants externes
```
# Migration : 0017_create_representants_externes_table.php
# Routes : apiResource representants-externes
```

---

## Plan de déploiement des seeders

```php
// DatabaseSeeder.php — ordre strict (FK)
$this->call([
    // Module 1
    RoleSeeder::class, PermissionSeeder::class,
    LocaliteSeeder::class, AdministrationSeeder::class,
    DirectionSeeder::class, ServiceSeeder::class, BureauSeeder::class,
    GradeSeeder::class, CategorieSeeder::class, EchelonSeeder::class,
    FonctionSeeder::class, TypeContratSeeder::class,
    TypeDocumentSeeder::class, TypeCongeSeeder::class,
    JourFerieSeeder::class, RegleAcquisitionCongeSeeder::class,
    TypeSanctionSeeder::class, MotifAdministratifSeeder::class,
    OrganismeSecuriteSocialeSeeder::class,

    // Module 2
    AgentSeeder::class, UserSeeder::class,
    InformationspersonnelleSeeder::class,
    InformationsprofessionnelleSeeder::class,
    ContacturgenceSeeder::class, SituationFamilialeSeeder::class,
    DocumentSeeder::class,

    // Module 4
    ContratagentSeeder::class,      // ← déclenche affectation + nomination + salaire auto
    AffectationSeeder::class,
    NominationSeeder::class,

    // Module 5
    ClassegrillesalarialeSeeder::class,
    ParametregrillesalarialeSeeder::class,
    SalaireSeeder::class, SalaireAgentSeeder::class,

    // Module 6
    CongeSoldeSeeder::class,

    // Module 7
    SessionEvaluationSeeder::class,
    QuestionEvaluationSeeder::class,
    EvaluationSeeder::class,
]);
```

---

## Récapitulatif des modules

| Module | Sous-modules | Migrations | Modèles | Services | Routes | Durée estimée |
|---|---|---|---|---|---|---|
| **0 — Socle** | Config, Base*, Traits | 0 | 0 | 4 | 0 | 2 j |
| **1 — Paramétrage** | Structure, Référentiels, Admin système | 3 | 14 | 14 | ~60 | 4 j |
| **2 — Dossier Agent** | Fiche, GED, Compte | 2 | 5 | 4 | ~20 | 3 j |
| **3 — Entrée administration** | Recrutement ext., Intégrations, Workflow | 2 | 5 | 5 | ~25 | 3 j |
| **4 — Contrats & Carrière** | Contrats, Carrière, Notes admin | 2 | 3 | 4 | ~20 | 4 j |
| **5 — Salaires** | Grilles, Salaires agents | 1 | 4 | 4 | ~10 | 2 j |
| **6 — Congés & Absences** | Paramétrage, Demandes, PDF | 1 | 8 | 5 | ~20 | 4 j |
| **7 — Évaluations** | Sessions, Notation, Commissions, PDF | 1 | 10 | 8 | ~25 | 6 j |
| **8 — Formation** | Catalogue, Plans, Inscriptions | 1 | 4 | 4 | ~10 | 2 j |
| **9 — Discipline** | Sanctions, Avertissements, Procédures | 1 | 4 | 4 | ~12 | 2 j |
| **10 — Reporting** | Dashboard, Stats, Exports | 0 | 0 | 2 | ~12 | 2 j |
| **11 — Notifications** | Notifications, Job alerte | 0 | 0 | 1 | ~5 | 1 j |
| **12 — GED RH** | Versioning, Archivage, Recherche | 1 | 2 | 1 | ~8 | 2 j |
| **Complémentaires** | Sécu. sociale, Repr. externes | 2 | 3 | 3 | ~8 | 1 j |
| **Tests & Swagger** | Feature + Unit + Annotations | — | — | — | — | 6 j |
| **Total** | | **~17 migrations** | **~62 modèles** | **~63 services** | **~235 routes** | **~44 j** |

---

## Checklist de validation par module

Avant de passer au module suivant :

- [ ] `php artisan migrate` — sans erreur
- [ ] `php artisan route:list --path=<prefix>` — toutes les routes du module présentes
- [ ] `php artisan db:seed --class=<ModuleSeeder>` — données de test insérées
- [ ] Tests Feature du module — tous verts (`php artisan test --filter=<Module>`)
- [ ] Linter/stan — aucune injection Repository dans Controller, aucun Eloquent dans Controller
- [ ] Swagger — annotations `@OA` présentes sur le Controller du module

---

*Dernière mise à jour : Mai 2026*
