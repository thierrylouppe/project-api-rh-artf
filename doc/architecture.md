# Architecture — Gestion RH API

> Document de référence obligatoire pour tout développement sur ce projet.  
> Complète [`instruction_projet.md`](./instruction_projet.md) (section 3) en précisant **ce qui est autorisé, interdit, et où placer chaque responsabilité**.

---

## 1. Principe fondamental

Chaque requête HTTP suit **une seule chaîne de responsabilités**, sans raccourci :

```
HTTP Request
    │
    ▼
Route (routes/api.php)
    │
    ▼
FormRequest (validation des entrées)
    │
    ▼
Controller (orchestration HTTP uniquement)
    │
    ▼
Service (logique métier)
    │
    ▼
Repository via Interface (accès données Eloquent)
    │
    ▼
Model (Eloquent)
    │
    ▼
MySQL
```

**Règle d'or :** une couche ne connaît que la couche **immédiatement en dessous** (et jamais au-dessus).

---

## 2. Les trois règles non négociables

| # | Règle | Violation typique |
|---|--------|-------------------|
| 1 | Un **Controller** n'injecte **jamais** un Repository | `AgentController` avec `AgentRepository` |
| 2 | Un **Service** n'injecte **jamais** un Repository concret | `AgentService` avec `AgentRepository` au lieu de `AgentInterface` |
| 3 | La **logique métier** (automatismes, règles, orchestration) vit dans les **Services**, jamais dans les Controllers | affectation auto dans `store()` du controller |

---

## 3. Responsabilités par couche

### 3.1 Route (`routes/api.php`)

| Autorisé | Interdit |
|----------|----------|
| Définir URI, verbes HTTP, middleware (`auth:sanctum`, `permission:`, `role:`) | Logique métier, requêtes Eloquent |
| Regrouper par module avec `Route::prefix()->group()` | Validation inline |
| Commentaires de section par module | Appels directs à des Services |

### 3.2 FormRequest (`app/Http/Requests/{Domaine}/`)

| Autorisé | Interdit |
|----------|----------|
| Règles de validation (`rules()`, `messages()`) | Accès base de données |
| Autorisation simple (`authorize()`) si liée à la requête | Logique métier complexe |
| Un fichier par action : `CreateRequest`, `UpdateRequest`, `FilterRequest` | Appels à des Services |

**Convention :** un sous-dossier par domaine métier (`Agent/`, `Contrat/`, `DemandeConge/`).

### 3.3 Controller (`app/Http/Controllers/API/`)

| Autorisé | Interdit |
|----------|----------|
| Injecter **uniquement** des Services via le constructeur | Injecter un Repository |
| Recevoir un FormRequest typé | `Model::create()`, `Model::find()`, requêtes Eloquent |
| Appeler une méthode du Service | Règles métier, `match` sur types de contrat, notifications |
| Retourner du JSON (`response()->json`) ou une Resource | Transactions DB directes |
| Annotations Swagger `@OA` | Bindings IoC manuels |

**Le controller est mince :** typiquement 3 à 8 lignes par action.

```php
// ✅ CORRECT
public function store(CreateRequest $request): JsonResponse
{
    $agent = $this->agentService->createAgent($request->validated());

    return response()->json([
        'data'    => new AgentResource($agent),
        'message' => 'Agent créé avec succès',
    ], 201);
}

// ❌ INTERDIT
public function store(CreateRequest $request): JsonResponse
{
    $agent = Agent::create($request->validated());
    $this->affectationRepository->affecterAutomatiquement($agent);
    return response()->json(['data' => $agent], 201);
}
```

### 3.4 Service (`app/Services/`)

| Autorisé | Interdit |
|----------|----------|
| Logique métier, règles, orchestration | Requêtes Eloquent directes (`Agent::where(...)`) |
| Injecter des **Interfaces** de Repository | Injecter des Repositories concrets |
| Injecter d'autres Services | Retourner des réponses HTTP |
| Déclencher notifications, jobs, événements | Validation des entrées HTTP (déjà faite par FormRequest) |
| Transactions (`DB::transaction`) si plusieurs écritures | Accès direct aux Models (sauf cas exceptionnel documenté) |

**Les automatismes métier vivent ici**, par exemple à la création d'un contrat :

```php
// ContratagentService::create() — exemple de responsabilité Service
$contrat = $this->contratagentRepository->create($data);
$this->affectationService->affecterAutomatiquement($agent, $directionDRHL);
$this->nominationService->nommerAutomatiquement($agent, $fonction);
$this->salaireService->creerSalaireInitial($agent, $contrat);
$this->notificationService->notifier($agent, 'contrat_cree', $contrat);
```

### 3.5 Interface (`app/Interfaces/`)

| Autorisé | Interdit |
|----------|----------|
| Contrat des méthodes du Repository | Implémentation, logique |
| Signatures alignées sur les besoins du Service | Dépendances vers Models dans la logique |

**Convention de nommage :** `{Domaine}Interface` (ex. `AgentInterface`).

### 3.6 Repository (`app/Repositories/`)

| Autorisé | Interdit |
|----------|----------|
| Implémenter l'Interface correspondante | Logique métier (notifications, règles RH) |
| Requêtes Eloquent, filtres, pagination | Appels à d'autres Services |
| `findOrFail`, `create`, `update`, `delete` | Réponses JSON |
| `when()` pour filtres optionnels | Transactions métier multi-domaines |

**Convention de nommage :** `{Domaine}Repository` implémente `{Domaine}Interface`.

### 3.7 Model (`app/Models/`)

| Autorisé | Interdit |
|----------|----------|
| Relations Eloquent, `$fillable`, casts, scopes simples | Logique métier orchestrée |
| Accesseurs/mutateurs de formatage | Appels à des Services |
| Enregistrement d'Observers dans `AppServiceProvider::boot()` | Policies Laravel (non utilisées sur ce projet) |

### 3.8 API Resource (`app/Http/Resources/`)

| Autorisé | Interdit |
|----------|----------|
| Transformation de la sortie JSON (`toArray`) | Logique métier, écriture en base |
| `whenLoaded()` pour relations conditionnelles | Requêtes supplémentaires non nécessaires |

---

## 4. Injection de dépendances (IoC)

### 4.1 Bindings centralisés

Tous les bindings Interface → Repository sont dans **`app/Providers/AppServiceProvider.php`** :

```php
$this->app->bind(AgentInterface::class, AgentRepository::class);
```

**Interdit :** binding dans un Controller, un Service ou un fichier de routes.

### 4.2 Matrice d'injection

| Classe | Injecte |
|--------|---------|
| `*Controller` | `*Service` |
| `*Service` | `*Interface` (+ autres `*Service` si besoin) |
| `*Repository` | Models Eloquent uniquement |

```php
// Controller
public function __construct(private AgentService $agentService) {}

// Service
public function __construct(private AgentInterface $agentRepository) {}

// Repository — pas d'injection d'Interface d'un autre domaine sauf nécessité documentée
```

---

## 5. Ajouter une nouvelle fonctionnalité (checklist)

Pour un domaine `{Domaine}` (ex. `Sanction`, `DemandeConge`) :

- [ ] **1.** `app/Interfaces/{Domaine}Interface.php`
- [ ] **2.** `app/Repositories/{Domaine}Repository.php` (implémente l'interface)
- [ ] **3.** Binding dans `AppServiceProvider::register()`
- [ ] **4.** `app/Services/{Domaine}Service.php` (injecte l'interface)
- [ ] **5.** `app/Http/Requests/{Domaine}/CreateRequest.php` (+ Update, Filter si besoin)
- [ ] **6.** `app/Http/Resources/{Domaine}Resource.php` (si réponse structurée)
- [ ] **7.** `app/Http/Controllers/API/{Domaine}Controller.php` (injecte le Service)
- [ ] **8.** Routes dans `routes/api.php` (section commentée du module)
- [ ] **9.** Annotations Swagger sur le controller

**Ordre de création recommandé :** Interface → Repository → Binding → Service → Requests → Resource → Controller → Routes.

---

## 6. Ce qui ne va pas dans les Controllers

Les éléments suivants doivent **toujours** être délégués à un Service (ou Job / Observer selon le cas) :

| Responsabilité | Où la placer |
|----------------|--------------|
| Création contrat + affectation + nomination + salaire | `ContratagentService` |
| Calcul note d'évaluation (/20) | `EvaluationService` |
| Calcul jours de congé (hors week-end et fériés) | `DemandeCongeService` |
| Validation workflow N+1 / RH | Service du domaine concerné |
| Envoi de notifications | `NotificationService` ou Notifications Laravel via Service |
| Génération PDF | Service + vue Blade (DomPDF) |
| Génération de sigle org. | `SigleObservers` (Observer) |
| Alerte contrats en fin de date | `ContractEnFinDateJob` (Job planifié) |

---

## 7. Autorisation et réponses HTTP

- **Pas de Policies Laravel** — utiliser les middlewares `permission:` et `role:` (Spatie).
- **Réponses JSON** uniformes depuis le Controller :

```php
// Liste
return response()->json(['data' => $collection], 200);

// Création
return response()->json(['data' => $entity, 'message' => 'Créé avec succès'], 201);

// Erreur métier (levée ou retournée par le Service)
return response()->json(['message' => 'Raison'], 422);
```

- Les middlewares `CheckPermission` et `CheckRole` retournent du JSON (pas de redirection HTML).

---

## 8. Anti-patterns — à rejeter en revue de code

```php
// ❌ Repository dans le Controller
public function __construct(private AgentRepository $repo) {}

// ❌ Eloquent dans le Controller
$agents = Agent::where('statut', 'actif')->get();

// ❌ Repository concret dans le Service
public function __construct(private AgentRepository $repo) {}

// ❌ Logique métier dans le Repository
public function create(array $data) {
    $agent = Agent::create($data);
    Notification::send(...); // INTERDIT
    return $agent;
}

// ❌ Validation métier complexe dans FormRequest qui appelle la DB
public function rules() {
    return ['matricule' => Rule::unique(Agent::class)]; // OK
    // mais pas de workflow complet de recrutement ici
}

// ❌ Binding dispersé
// dans RandomServiceProvider ou dans le controller
app()->bind(...);
```

---

## 9. Structure des dossiers (rappel)

```
app/
├── Http/
│   ├── Controllers/API/     # Orchestration HTTP
│   ├── Middleware/          # permission, role
│   ├── Requests/{Domaine}/   # Validation
│   └── Resources/           # Transformation JSON sortie
├── Interfaces/              # Contrats Repository
├── Repositories/            # Accès Eloquent
├── Services/                # Logique métier
├── Models/                  # Eloquent + relations
├── Observers/               # Effets de bord automatiques (sigle, etc.)
├── Jobs/                    # Tâches asynchrones / planifiées
├── Notifications/
└── Providers/
    └── AppServiceProvider.php  # Bindings IoC + Observers
```

---

## 10. Revue rapide avant merge

Avant toute PR ou commit de feature :

1. Aucun `use App\Repositories\*` dans un fichier sous `Controllers/`
2. Aucun `use App\Models\*` avec requête active dans un Controller (sauf imports pour type-hint rare)
3. Chaque nouveau Repository a son Interface + binding dans `AppServiceProvider`
4. Chaque action controller a son FormRequest
5. La logique métier nouvelle est testable via le Service (pas via le Controller)
6. Les routes du module sont dans la bonne section commentée de `routes/api.php`

---

## 11. Références

| Document | Contenu |
|----------|---------|
| [`instruction_projet.md`](./instruction_projet.md) | Spec complète, modules, TODOs, seeders |
| Ce fichier | Règles d'architecture et checklist d'implémentation |
| `.cursor/rules/architecture-layers.mdc` | Règle Cursor pour l'agent IA |

---

*Dernière mise à jour : Mai 2026*
