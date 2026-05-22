# Instructions IA — Gestion RH API (Administration Publique)

> Ce fichier est destiné à guider une IA pour reproduire ce projet de manière cohérente, en respectant l'architecture, les conventions et la logique métier existantes.

---

## 1. Contexte du projet

**Nom :** Gestion RH API  
**Type :** API REST pour la gestion des ressources humaines d'une administration publique  
**Objectif :** Couvrir le cycle de vie complet d'un agent : recrutement → contrat → affectation/nomination → évaluation → avancement → congés → salaires → sanctions  
**Statut actuel :** ~90–95 % complet sur les fonctionnalités principales

---

## 2. Stack technique

| Composant | Version / Détail |
|---|---|
| **Langage** | PHP 8.2+ |
| **Framework** | Laravel 11.31 |
| **Authentification** | Laravel Sanctum 4.0 |
| **Permissions** | Spatie Laravel Permission 6.15 |
| **PDF** | barryvdh/laravel-dompdf 3.1 |
| **Documentation API** | darkaonline/l5-swagger 9.0 |
| **Tests** | Pest 3.7 + pest-plugin-laravel 3.1 |
| **Base de données** | MySQL (nom : `gestion_rh_api`) |
| **Queue** | driver `database` |
| **Cache/Session** | driver `database` |
| **Build frontend** | Vite 6 + TailwindCSS 3 (uniquement pour vues PDF/Swagger) |

### Installation initiale

```bash
composer create-project laravel/laravel gestion-rh-api "^11.31"
composer require laravel/sanctum:"^4.0" spatie/laravel-permission:"^6.15" barryvdh/laravel-dompdf:"^3.1" darkaonline/l5-swagger:"^9.0"
composer require --dev pestphp/pest:"^3.7" pestphp/pest-plugin-laravel:"^3.1"
```

---

## 3. Architecture du projet

### 3.1 Pattern général

Le projet suit strictement le pattern **Controller → Service → Repository → Model**.

```
HTTP Request
    │
    ▼
Route (routes/api.php)
    │
    ▼
FormRequest (validation)
    │
    ▼
Controller (app/Http/Controllers/API/)
    │  — injecte un Service via constructeur
    ▼
Service (app/Services/)
    │  — contient la logique métier
    │  — peut utiliser d'autres Services
    ▼
Repository (app/Repositories/)
    │  — implémente une Interface
    │  — gère les accès Eloquent
    ▼
Model (app/Models/)
    │
    ▼
MySQL
```

### 3.2 Injection de dépendances (IoC)

Chaque Repository implémente une Interface. Les bindings sont tous centralisés dans `app/Providers/AppServiceProvider.php` :

```php
// Exemple de binding
$this->app->bind(AgentInterface::class, AgentRepository::class);
```

Les Controllers injectent directement le **Service** (pas le Repository) :

```php
class AgentController extends Controller
{
    public function __construct(private AgentService $agentService) {}
}
```

Les Services injectent l'**Interface** du Repository (jamais le Repository concret) :

```php
class AgentService
{
    public function __construct(private AgentInterface $agentRepository) {}
}
```

### 3.3 Structure des dossiers

```
app/
├── Console/Commands/         # Commandes Artisan (ex: CheckContractEnFinDate)
├── Http/
│   ├── Controllers/
│   │   ├── API/              # Tous les contrôleurs API (namespace API)
│   │   └── Controller.php    # Contrôleur de base
│   ├── Middleware/
│   │   ├── CheckPermission.php   # alias: permission
│   │   └── CheckRole.php         # alias: role
│   ├── Requests/             # Form Requests (1 dossier par domaine)
│   └── Resources/            # API Resources (transformateurs JSON)
├── Interfaces/               # Contrats des Repositories (1 fichier par domaine)
├── Jobs/                     # Jobs de queue
├── Models/                   # Modèles Eloquent
├── Notifications/            # Notifications Laravel
├── Observers/                # Observers Eloquent
├── Providers/
│   └── AppServiceProvider.php  # Tous les bindings IoC
├── Repositories/             # Implémentations des interfaces
├── Services/                 # Logique métier
├── Swagger/                  # Annotations Swagger globales
└── Traits/                   # Traits réutilisables
```

### 3.4 Exemple de code — Interface

```php
// app/Interfaces/AgentInterface.php
namespace App\Interfaces;

interface AgentInterface
{
    public function getAll(array $filters = []): mixed;
    public function findById(int $id): mixed;
    public function create(array $data): mixed;
    public function update(int $id, array $data): mixed;
    public function delete(int $id): bool;
}
```

### 3.5 Exemple de code — Repository

```php
// app/Repositories/AgentRepository.php
namespace App\Repositories;

use App\Interfaces\AgentInterface;
use App\Models\Agent;

class AgentRepository implements AgentInterface
{
    public function getAll(array $filters = []): mixed
    {
        return Agent::query()
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where('nom', 'like', "%$s%"))
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findById(int $id): mixed
    {
        return Agent::findOrFail($id);
    }

    public function create(array $data): mixed
    {
        return Agent::create($data);
    }

    public function update(int $id, array $data): mixed
    {
        $agent = Agent::findOrFail($id);
        $agent->update($data);
        return $agent;
    }

    public function delete(int $id): bool
    {
        return Agent::findOrFail($id)->delete();
    }
}
```

### 3.6 Exemple de code — Service

```php
// app/Services/AgentService.php
namespace App\Services;

use App\Interfaces\AgentInterface;

class AgentService
{
    public function __construct(private AgentInterface $agentRepository) {}

    public function getAllAgents(array $filters = []): mixed
    {
        return $this->agentRepository->getAll($filters);
    }

    public function createAgent(array $data): mixed
    {
        // Logique métier ici (validations, transformations, événements...)
        return $this->agentRepository->create($data);
    }
}
```

### 3.7 Exemple de code — Controller

```php
// app/Http/Controllers/API/AgentController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\CreateRequest;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;

class AgentController extends Controller
{
    public function __construct(private AgentService $agentService) {}

    public function index(Request $request): JsonResponse
    {
        $agents = $this->agentService->getAllAgents($request->validated());
        return response()->json(['data' => $agents]);
    }

    public function store(CreateRequest $request): JsonResponse
    {
        $agent = $this->agentService->createAgent($request->validated());
        return response()->json(['data' => $agent, 'message' => 'Agent créé avec succès'], 201);
    }
}
```

---

## 4. Authentification et autorisation

### 4.1 Sanctum

- Guard API : `auth:sanctum`
- Les tokens sont créés via `POST /api/login`
- Logout via `POST /api/logout` (révoque le token courant)

### 4.2 Spatie Permission

- Rôles et permissions gérés via Spatie
- Le modèle `User` utilise `HasRoles` et `HasApiTokens`
- Deux middlewares personnalisés :
  - `permission:nom-permission` → vérifie `$user->hasPermissionTo()`
  - `role:nom-role` → vérifie `$user->hasRole()`
- Ces middlewares retournent des réponses JSON (pas de redirections HTML)

### 4.3 Stratégie de protection des routes

```php
// Routes publiques (pas de middleware auth)
Route::post('/login', [AuthController::class, 'login']);

// Routes authentifiées seulement
Route::middleware('auth:sanctum')->group(function () { ... });

// Routes authentifiées avec permission
Route::middleware(['auth:sanctum', 'permission:consulter-agents'])->group(function () { ... });

// Routes authentifiées avec rôle
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () { ... });
```

**Note importante :** Beaucoup d'endpoints métier (structure org., recrutement, congés) ne sont actuellement pas protégés par `auth:sanctum`. Seuls les modules users/rôles/permissions et avancements exigent explicitement l'authentification.

### 4.4 Enregistrement des middlewares

Dans `bootstrap/app.php` :

```php
$middleware->alias([
    'permission' => \App\Http\Middleware\CheckPermission::class,
    'role'       => \App\Http\Middleware\CheckRole::class,
]);
```

---

## 5. Modules et domaines fonctionnels

### Module 1 — Authentification et utilisateurs

**Tables :** `users`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `personal_access_tokens`

**Modèles clés :**
- `User` : `HasApiTokens`, `HasRoles` (Spatie), relation `belongsTo(Agent::class, 'agent_id')`

**Routes principales :**
```
POST   /api/login
POST   /api/logout              [auth:sanctum]
GET    /api/user                [auth:sanctum]
GET    /api/users               [auth:sanctum, permission:consulter-utilisateurs]
POST   /api/users               [auth:sanctum, permission:creer-utilisateurs]
PUT    /api/users/{id}          [auth:sanctum, permission:modifier-utilisateurs]
DELETE /api/users/{id}          [auth:sanctum, permission:supprimer-utilisateurs]
GET    /api/roles               [auth:sanctum, permission:consulter-roles]
POST   /api/roles/{id}/dupliquer [auth:sanctum]
GET    /api/permissions         [auth:sanctum]
```

---

### Module 2 — Structure organisationnelle

**Tables :** `localites`, `administrations`, `directions`, `services`, `bureaus`

**Hiérarchie :** Localite → Administration → Direction → Service → Bureau

**Observers :** `SigleObservers` attaché à Administration, Direction, Service, Bureau (génération automatique de sigle)

**Routes :**
```
GET/POST/PUT/DELETE  /api/localites/{id?}
GET/POST/PUT/DELETE  /api/administrations/{id?}
GET/POST/PUT/DELETE  /api/directions/{id?}
GET                  /api/directions/{id}/agents
GET/POST/PUT/DELETE  /api/services/{id?}
GET                  /api/services/{id}/agents
GET/POST/PUT/DELETE  /api/bureaux/{id?}
GET                  /api/bureaux/{id}/agents
```

---

### Module 3 — Agents

**Table principale :** `agents`

**Tables liées :** `informationspersonnelles`, `informationsprofessionnelles`, `contacturgences`

**Modèle `Agent` — Relations clés :**
- `hasOne(Contratagent::class)` — contrat actif
- `hasOne(Affectation::class)` — affectation active
- `hasOne(Nomination::class)` — nomination active
- `hasMany(Document::class)`
- `hasMany(Sanction::class)`
- `hasMany(AffiliationSecuriteSociale::class)`
- `hasOne(User::class, 'agent_id')`

**Routes :**
```
GET/POST         /api/agents
GET/PUT/DELETE   /api/agents/{agent}
GET/POST/PUT     /api/agents/{agent}/informations-personnelles
GET/POST/PUT     /api/agents/{agent}/informations-professionnelles
GET/POST/PUT     /api/agents/{agent}/contacts-urgence
```

---

### Module 4 — Recrutement (workflow en 5 étapes)

**Étape 1 — Création agent :**
```
POST /api/recrutement/agents
```

**Étape 2 — Infos complémentaires :**
```
POST /api/recrutement/agents/{agent}/informations-professionnelles
POST /api/recrutement/agents/{agent}/contacts-urgence
```

**Étape 3 — Contrat (déclenche automatismes) :**
```
POST /api/recrutement/agents/{agent}/contrats
```
Automatismes déclenchés :
1. **Affectation automatique** à la Direction RH (D.R.H.L)
2. **Nomination automatique** : fonction "Agent" (CDI/CDD) ou "Stagiaire" (Stage)
3. **Salaire automatique** : créé pour CDI/CDD uniquement
4. **Notification automatique** envoyée

**Étape 4 — Documents :**
```
POST   /api/recrutement/agents/{agent}/documents
GET    /api/recrutement/agents/{agent}/documents
DELETE /api/recrutement/agents/{agent}/documents/{document}
```

**Étape 5 — Finalisation :**
```
POST /api/recrutement/agents/{agent}/integrer
GET  /api/recrutement/en-cours
GET  /api/recrutement/statistiques
```

---

### Module 5 — Contrats et affectations

**Tables :** `contratagents`, `type_contrats`, `affectations`

**Types de contrats :** CDI, CDD, Stage, Consultant

**Actions sur contrats :**
```
POST /api/contrats/{contrat}/suspendre
POST /api/contrats/{contrat}/terminer
POST /api/contrats/{contrat}/reconduire
```

**Routes affectations :**
```
GET/POST         /api/agents/{agent}/affectations
GET/PUT/DELETE   /api/agents/{agent}/affectations/{affectation}
```

---

### Module 6 — Nominations (carrière)

**Table :** `nominations`

**Routes :**
```
GET  /api/nominations/agent/{id}
GET  /api/nominations/historique/agent/{id}
GET  /api/nominations/chefs/{id}/agents-sous-autorite
GET  /api/nominations/postes-vacants
POST /api/nominations
PUT  /api/nominations/{id}
```

---

### Module 7 — Documents

**Tables :** `documents` (avec colonnes `contexte`, `type_document`, `sous_dossier`, `ordre`)

**Gestion :** upload multiple, arborescence par type/sous-dossier, numérotation automatique, déplacement

**Routes :**
```
GET/POST         /api/agents/{agent}/documents
GET/PUT/DELETE   /api/agents/{agent}/documents/{document}
POST             /api/agents/{agent}/documents/{document}/deplacer
```

---

### Module 8 — Évaluations et avancements

**Tables :** `session_evaluations`, `evaluations`, `question_evaluations`, `note_evaluations`, `reclamations`, `connaissances_complementaires`, `avis_hierarchiques`, `note_syntheses`, `commissions_preparatoires`, `commissions_avancements`

**Workflow complet :**
1. Création d'une session d'évaluation
2. Attribution automatique des agents à leurs supérieurs hiérarchiques
3. Génération automatique des fiches d'évaluation
4. Notation par le supérieur (3 blocs : Compétences /10, Assiduité /3, Relations /7 → Total /20)
5. Validation et signature par l'agent
6. Réclamations éventuelles
7. Avis du Chef de Service puis du Directeur
8. Validation RH (DRHL)
9. Commission Préparatoire
10. Notes de synthèse (avec export PDF)
11. Commission d'Avancement
12. Décision finale (accordé / différé / maintien)

**Toutes les routes sous le préfixe `/api/avancements/` sont protégées par `auth:sanctum`.**

**Routes :**
```
/api/avancements/sessions/*
/api/avancements/evaluations/*
/api/avancements/questions-evaluation/*
/api/avancements/commissions/*               # Commissions préparatoires
/api/avancements/commissions-avancement/*    # Commissions d'avancement
/api/avancements/notes-synthese/*
/api/avancements/rapports/*
/api/avancements/avis-hierarchiques/*
/api/avancements/validation-rh/*
```

**TODOs connus (à implémenter) :**
- Notification du supérieur lors d'une réclamation
- Notification du supérieur lors d'une signature
- Notification de l'agent lors de la validation
- Suppression de session d'évaluation (actuellement commentée)

---

### Module 9 — Salaires

**Tables :** `salaires`, `salaires_agents`, `classegrillesalariales`, `parametregrilles`

**Logique :**
- Grilles salariales organisées en classes avec paramètres
- Salaire créé automatiquement lors de la création d'un contrat CDI/CDD
- Clôture de salaire actuel possible

**Routes :**
```
GET/POST/PUT/DELETE  /api/salaires/{id?}
GET/POST/PUT/DELETE  /api/salaires-agents/{id?}
GET/POST/PUT/DELETE  /api/classes/{id?}
GET/POST/PUT/DELETE  /api/parametres/{id?}
```

---

### Module 10 — Congés

**Tables :** `type_conges`, `jour_feries`, `regle_acquisition_conges`, `conge_soldes`, `demande_conges`, `justificatifs`, `validation_conges`, `notification_conges`, `document_conges`

**Workflow de validation :** Agent → N+1 (supérieur direct) → RH

**Calcul des jours :** excluant les jours fériés et les week-ends

**Routes :**
```
GET/POST/PUT/DELETE  /api/type-conges/{id?}
GET/POST/PUT/DELETE  /api/jours-feries/{id?}
GET/POST/PUT/DELETE  /api/regles-acquisition-conges/{id?}
GET/POST/PUT/DELETE  /api/conge-soldes/{id?}
GET/POST/PUT/DELETE  /api/demandes-conges/{id?}
POST                 /api/demandes-conges/{id}/valider-n1
POST                 /api/demandes-conges/{id}/rejeter-n1
POST                 /api/demandes-conges/{id}/valider-rh
POST                 /api/demandes-conges/{id}/rejeter-rh
GET                  /api/statistiques-conges/*  [auth:sanctum]
```

**TODOs connus (à implémenter) :**
- Génération de fiche PDF pour congés
- Génération d'attestation de congé
- Vérification des rôles RH
- Notifications dans le workflow

---

### Module 11 — Sécurité sociale

**Tables :** `organisme_securite_sociales`, `affiliation_securite_sociales`

**Routes :**
```
GET/POST/PUT/DELETE  /api/organismes-securite-sociale/{id?}
GET/POST/PUT/DELETE  /api/affiliations-securite-sociale/{id?}
```

---

### Module 12 — Sanctions

**Tables :** `sanctions`, `type_sanctions`

**Routes :**
```
GET/POST/PUT/DELETE  /api/sanctions/{id?}
GET                  /api/sanctions/agent/{agentId}
POST                 /api/sanctions/{id}/valider
POST                 /api/sanctions/{id}/rejeter
```

---

### Module 13 — Représentants externes

**Table :** `representant_externes`

**Routes :**
```
GET/POST/PUT/DELETE  /api/representants-externes/{id?}
```

---

### Module 14 — Référentiels

**Tables :** `diplomes`, `fonctions`, `type_contrats`

**Routes :**
```
GET/POST/PUT/DELETE  /api/diplomes/{id?}
GET/POST/PUT/DELETE  /api/fonctions/{id?}
GET/POST/PUT/DELETE  /api/types-contrats/{id?}
```

---

### Module 15 — Notifications système

**Table :** `notifications` (table polymorphique Laravel)

**Routes :**
```
GET   /api/notifications          [auth:sanctum]
GET   /api/notifications/non-lu   [auth:sanctum]
POST  /api/notifications/{id}/lu  [auth:sanctum]
```

---

## 6. Conventions de code

### 6.1 Nommage

| Élément | Convention | Exemple |
|---|---|---|
| Controllers | PascalCase + `Controller` | `AgentController` |
| Services | PascalCase + `Service` | `AgentService` |
| Repositories | PascalCase + `Repository` | `AgentRepository` |
| Interfaces | PascalCase + `Interface` | `AgentInterface` |
| Models | PascalCase singulier | `Agent`, `Contratagent` |
| Migrations | snake_case daté | `2024_01_01_000000_create_agents_table` |
| Tables DB | snake_case pluriel | `agents`, `contratagents` |
| Routes | kebab-case | `/api/type-conges`, `/api/demandes-conges` |
| Form Requests | dossier = domaine, fichier = action | `Agent/CreateRequest.php` |

### 6.2 Réponses JSON

Toujours retourner des réponses JSON cohérentes :

```php
// Succès liste
return response()->json(['data' => $collection], 200);

// Succès entité
return response()->json(['data' => $entity, 'message' => 'Opération réussie'], 200);

// Création
return response()->json(['data' => $entity, 'message' => 'Créé avec succès'], 201);

// Erreur métier
return response()->json(['message' => 'Raison de l\'erreur'], 422);

// Non trouvé
return response()->json(['message' => 'Ressource non trouvée'], 404);

// Non autorisé
return response()->json(['message' => 'Non autorisé'], 403);
```

### 6.3 Form Requests

Chaque action (create, update, filter) a son propre FormRequest dans un sous-dossier :

```
app/Http/Requests/
├── Agent/
│   ├── CreateRequest.php
│   ├── UpdateRequest.php
│   └── FilterRequest.php
├── Contrat/
│   ├── CreateRequest.php
│   └── UpdateRequest.php
...
```

### 6.4 API Resources

Utiliser les API Resources pour transformer les réponses Eloquent :

```php
// app/Http/Resources/AgentResource.php
class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nom'        => $this->nom,
            'prenom'     => $this->prenom,
            'matricule'  => $this->matricule,
            // ...relations chargées conditionnellement
            'contrat'    => $this->whenLoaded('contratActif', fn() => new ContratagentResource($this->contratActif)),
        ];
    }
}
```

### 6.5 Annotations Swagger

Chaque contrôleur API doit avoir des annotations OpenAPI (`@OA`) :

```php
/**
 * @OA\Get(
 *     path="/api/agents",
 *     operationId="getAgents",
 *     tags={"Agents"},
 *     summary="Liste des agents",
 *     security={{"sanctum":{}}},
 *     ...
 * )
 */
```

### 6.6 Observers

Utiliser les Observers pour la logique déclarée automatiquement (ex : génération de sigle) :

```php
// app/Observers/SigleObservers.php — attaché à Administration, Direction, Service, Bureau
public function creating(Model $model): void
{
    // génération automatique du sigle à partir du nom
}
```

---

## 7. Base de données

### 7.1 Configuration `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_rh_api
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### 7.2 Ordre de migration (respecter les dépendances FK)

1. Tables système : `agents`, `users`, Spatie permissions, `cache`, `jobs`, `notifications`
2. Structure org. : `localites`, `administrations`, `directions`, `services`, `bureaus`
3. Référentiels : `diplomes`, `fonctions`, `type_contrats`
4. Agent details : `informationspersonnelles`, `informationsprofessionnelles`, `contacturgences`
5. Contrats/carrière : `contratagents`, `affectations`, `nominations`
6. Documents : `documents`
7. Recrutements : `recrutements`
8. Salaires : `classegrillesalariales`, `parametregrilles`, `salaires`, `salaires_agents`
9. Évaluations : `session_evaluations`, `evaluations`, `question_evaluations`, `note_evaluations`, `reclamations`, `connaissances_complementaires`, `avis_hierarchiques`, `note_syntheses`, commissions
10. Congés : `type_conges`, `jour_feries`, `regle_acquisition_conges`, `conge_soldes`, `demande_conges`, `justificatifs`, `validation_conges`, `notification_conges`, `document_conges`
11. Autres : `sanctions`, `type_sanctions`, `organisme_securite_sociales`, `affiliation_securite_sociales`, `representant_externes`

### 7.3 Ordre des seeders

```php
// DatabaseSeeder.php — ordre à respecter
$this->call([
    RoleSeeder::class,               // 1. Rôles et permissions
    LocaliteSeeder::class,           // 2. Structure
    AdministrationSeeder::class,
    DirectionSeeder::class,
    ServiceSeeder::class,
    BureauSeeder::class,
    DiplomeSeeder::class,            // 3. Référentiels
    FonctionSeeder::class,
    TypeContratsSeeder::class,
    ClassegrillesalarialeSeeder::class,
    ParametregrillesalarialeSeeder::class,
    TypeCongeSeeder::class,
    JourFerieSeeder::class,
    RegleAcquisitionCongeSeeder::class,
    OrganismeSecuriteSocialeSeeder::class,
    TypeSanctionSeeder::class,
    AgentSeeder::class,              // 4. Données agents
    UserSeeder::class,
    InformationspersonnelleSeeder::class,
    InformationsprofessionnelleSeeder::class,
    ContacturgenceSeeder::class,
    RecrutementSeeder::class,
    ContratagentSeeder::class,       // Déclenche affectation+nomination auto
    AffectationSeeder::class,
    NominationSeeder::class,
    DocumentSeeder::class,
    SalaireSeeder::class,
    SalaireAgentSeeder::class,
    CongeSoldeSeeder::class,
    SessionEvaluationSeeder::class,  // 5. Évaluations
    QuestionEvaluationSeeder::class,
    EvaluationSeeder::class,
]);
```

---

## 8. Logique métier automatique (règles critiques)

Ces automatismes sont déclenchés dans les Services, pas dans les Controllers.

### 8.1 À la création d'un contrat

```php
// Dans ContratagentService::create()
// 1. Créer le contrat
$contrat = $this->contratagentRepository->create($data);

// 2. Affectation automatique à la DRHL
$this->affectationService->affecterAutomatiquement($agent, $directionDRHL);

// 3. Nomination automatique selon type contrat
$fonction = match($contrat->type) {
    'CDI', 'CDD'        => Fonction::where('nom', 'Agent')->first(),
    'Stage'             => Fonction::where('nom', 'Stagiaire')->first(),
    'Consultant'        => null,
};
if ($fonction) {
    $this->nominationService->nommerAutomatiquement($agent, $fonction);
}

// 4. Salaire automatique (CDI/CDD uniquement)
if (in_array($contrat->type, ['CDI', 'CDD'])) {
    $this->salaireService->creerSalaireInitial($agent, $contrat);
}

// 5. Notification
$this->notificationService->notifier($agent, 'contrat_cree', $contrat);
```

### 8.2 À l'ouverture d'une session d'évaluation

```php
// Dans SessionEvaluationService::ouvrir()
// Générer automatiquement une fiche d'évaluation pour chaque agent
// attribué à un supérieur hiérarchique dans cette session
foreach ($session->agents as $agent) {
    $this->evaluationService->genererFiche($agent, $session);
}
```

### 8.3 Calcul de la note d'évaluation

```
Compétences  : note /10 (coefficient configurable)
Assiduité    : note /3  (coefficient configurable)
Relations    : note /7  (coefficient configurable)
─────────────────────────────
Total        : note /20 (calculé automatiquement)
```

### 8.4 Calcul des jours de congé

```php
// Exclure les week-ends (samedi, dimanche) ET les jours fériés
$joursOuvrables = CarbonPeriod::create($debut, $fin)
    ->filter(fn($date) => !$date->isWeekend())
    ->filter(fn($date) => !JourFerie::estFerie($date))
    ->count();
```

### 8.5 Job planifié

```php
// routes/console.php
Schedule::job(new ContractEnFinDateJob(15)) // alerte 15 jours avant échéance
    ->weekdays()
    ->at('08:00')
    ->withoutOverlapping();
```

---

## 9. Fonctionnalités partiellement implémentées (TODOs)

Ces éléments doivent être complétés dans une prochaine itération :

### Notifications manquantes

| Événement | Fichier | Ligne approx. |
|---|---|---|
| Réclamation d'évaluation → notifier supérieur | `EvaluationService.php` | 279 |
| Signature d'évaluation → notifier supérieur | `EvaluationService.php` | 314 |
| Validation d'évaluation → notifier agent | `SuperieurHierarchiqueService.php` | 186 |
| Validation N+1 congé → notifier agent | `DemandeCongeService.php` | 1196 |
| Rejet N+1 congé → notifier agent | `DemandeCongeService.php` | 1205 |
| Validation RH congé → notifier agent | `DemandeCongeService.php` | 1214 |
| Rejet RH congé → notifier agent | `DemandeCongeService.php` | 1223 |

### PDF manquants

| Document | Fichier | Ligne approx. |
|---|---|---|
| Fiche de congé PDF | `DemandeCongeService.php` | 374 |
| PDF congé (général) | `DemandeCongeService.php` | 890 |
| Attestation de congé | `DemandeCongeService.php` | 900 |

### Autres TODOs

- Suppression de session d'évaluation (commentée dans `SessionEvaluationController.php` ligne 432)
- Documentation Swagger complète (annotations partielles)
- Tests automatisés Pest (~20 % de couverture actuelle)
- Vérification des rôles RH dans `DemandeCongeService.php` ligne 1132

---

## 10. Configuration bootstrap

### `bootstrap/app.php`

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        console: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role'       => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## 11. Organisation des routes (`routes/api.php`)

Le fichier routes est organisé par sections commentées :

```php
// ============================================================================
// ROUTES D'AUTHENTIFICATION
// ============================================================================

// ============================================================================
// ROUTES DES NOTIFICATIONS
// ============================================================================

// ============================================================================
// GESTION DES UTILISATEURS ET PERMISSIONS
// ============================================================================

// ============================================================================
// STRUCTURE ORGANISATIONNELLE
// ============================================================================

// ... etc.
```

---

## 12. Ordre d'implémentation recommandé pour une IA

Pour reconstruire ce projet de façon cohérente, implémenter dans cet ordre :

1. **Configuration** : `.env`, `config/sanctum.php`, `config/permission.php`, `bootstrap/app.php`
2. **Migrations** : dans l'ordre des dépendances FK (voir section 7.2)
3. **Modèles** : avec toutes leurs relations Eloquent
4. **Interfaces** : contrats pour chaque domaine
5. **Repositories** : implémentations des interfaces
6. **`AppServiceProvider`** : tous les bindings IoC
7. **Services** : logique métier (en commençant par les services sans dépendances)
8. **Form Requests** : validation des entrées
9. **API Resources** : transformation des sorties
10. **Controllers** : orchestration (minime)
11. **Routes** : dans `routes/api.php`, organisées par module
12. **Middleware** : `CheckPermission`, `CheckRole`
13. **Observers** : `SigleObservers`, `DiplomeObserver`
14. **Jobs** : `ContractEnFinDateJob`
15. **Seeders** : dans l'ordre des dépendances (voir section 7.3)
16. **Notifications** : système de notifications Laravel
17. **PDF** : templates Blade + DomPDF
18. **Tests** : Pest

---

## 13. Chiffres de référence du projet (état actuel)

| Élément | Quantité |
|---|---|
| Modèles Eloquent | 55 |
| Contrôleurs API | 51 |
| Services métier | 55 |
| Repositories | 45 |
| Interfaces (contrats) | 49 |
| Migrations | 86 |
| Seeders | 51 |
| Routes API | ~200+ |
| Form Requests | ~45 dossiers |
| API Resources | ~60 |

---

## 14. Points d'attention pour la cohérence

1. **Ne jamais injecter un Repository directement dans un Controller** — toujours passer par le Service.
2. **Ne jamais injecter un Repository concret dans un Service** — toujours utiliser l'Interface.
3. **Les automatismes métier** (affectation auto, nomination auto, etc.) vivent dans les Services, jamais dans les Controllers.
4. **Toutes les réponses sont en JSON** — aucune vue HTML sauf pour les PDFs et la documentation Swagger.
5. **Pas de Policies Laravel** — toute l'autorisation passe par les middlewares `permission:` et `role:` de Spatie.
6. **Les Observers** sont attachés dans `AppServiceProvider::boot()`, pas dans les modèles.
7. **Les routes sont regroupées par préfixe** avec `Route::prefix()->group()` pour la lisibilité.
8. **Les timestamps** (`created_at`, `updated_at`) sont activés sur tous les modèles.
9. **Soft deletes** : à vérifier modèle par modèle selon la sensibilité des données.
10. **Pagination** : utiliser `->paginate()` pour toutes les listes, jamais `->get()` directement sur de grandes tables.

---

*Dernière mise à jour : Mai 2026*
