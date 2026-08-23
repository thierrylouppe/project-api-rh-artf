# Plan — Module Nomination (carrière)

> Branche : `feature/nomination`  
> Date : 2026-08-23  
> Objectif : porter la nomination au même niveau que l’affectation, sans casser le frontend.

Références : [`plan-module-carriere.md`](./plan-module-carriere.md) (lot 3) · [`note-fe-routes-carriere.md`](./note-fe-routes-carriere.md) · [`architecture.md`](./architecture.md) · [`integration.md`](./integration.md)

Chaîne technique : Route → FormRequest → Controller → Service → Interface → Repository.

---

## 1. Règles métier (non négociables)

1. La nomination est un **acte de carrière** (`/api/carriere/nominations`). Elle n’est pas une étape de dossier.
2. `INTEGRE` **ne dépend pas** d’une nomination. `marquerNomme` ne doit plus être appelé depuis l’activation.
3. Une **structure** n’a qu’un seul responsable actif.
4. Un **agent** n’a qu’une seule nomination active. Toute nouvelle activation clôture l’ancienne (agent **et** structure).
5. Le couple **poste ↔ type de structure** est cohérent (ex. Chef de Bureau → Bureau uniquement).
6. Activation uniquement depuis le statut `approuvee` (circuit terminé).
7. Alias `/api/integration/nominations` conservés jusqu’à bascule FE coordonnée.
8. Pas de nomination automatique (`nommerAutomatiquement`) sur cette branche.

---

## 2. État actuel (point de départ)

Routes déjà montées sous `/carriere` et alias `/integration` :

- `GET/POST /nominations`, `GET /nominations/{id}`
- `POST /nominations/{id}/activer|cloturer|rejeter`
- `GET /agents/{id}/nominations`

Déjà en place : création `en_attente` + init circuit, clôture des actives **par structure** à l’activation.

Écarts bloquants :

| Sujet | Constat |
|--------|---------|
| Circuit | `ValidationWorkflowController` ne met pas la nomination en `approuvee` / `rejetee` |
| Transitions | Statuts en chaînes, pas de garde 422 |
| Dossier | `activer` peut encore appeler `marquerNomme` |
| Structure | `structurable_id` non vérifié (pas de `ValideStructurable`) |
| Agent | L’ancienne nomination **de l’agent** n’est pas clôturée s’il change de poste |
| Tests | Aucun `NominationTest` |
| OpenAPI | Tags Intégration uniquement |

---

## 3. Phases

```
Phase 1 (cette branche)  →  Phase 2 (métier carrière)  →  Phase 3 (plus tard)
        │
        └─ review FE : les alias integration/* marchent encore
```

---

### Phase 1 — Parité affectation (lot 3 + corrections bloquantes)

**But :** même robustesse que `feature/affectation`. Pas de nouvelles routes métier.

#### 1.1 Machine à états

- [x] Enum `StatutNomination` : `en_attente`, `approuvee`, `active`, `cloturee`, `rejetee`
- [x] Méthodes `label()`, `transitionsAutorisees()`, `peutTransitionnerVers()`
- [x] Cast sur le modèle ; Resource : `statut` + `statut_label`
- [x] 422 si transition interdite (activer / clôturer / rejeter / approuver)

Alignement attendu :

```
en_attente → approuvee | rejetee
approuvee  → active | rejetee
active     → cloturee
cloturee   → (terminal)
rejetee    → (terminal)
```

#### 1.2 Circuit de validation

- [x] `NominationService::approuver` / `rejeter` (comme `AffectationService`)
- [x] Dernière étape du circuit → `approuvee` via `ValidationWorkflowController`
- [x] Rejet d’une étape du circuit → `rejetee` (commentaire historisé)
- [x] `POST …/activer` uniquement depuis `approuvee`

#### 1.3 Règles d’activation

- [x] Clôturer la nomination **active de la structure**
- [x] Clôturer la nomination **active de l’agent** (mutation de poste)
- [x] `date_debut` renseignée si absente ; `date_fin` de l’ancienne = jour d’activation

#### 1.4 Découplage dossier

- [x] Controller : injecter uniquement `NominationService`
- [x] `activer` n’appelle plus `marquerNomme`
- [x] `dossier_integration_id` accepté (nullable), **ignoré**
- [x] Checklist étape 15 : ne plus marquer `fait` sur n’importe quelle nomination (préférer nomination **active**, ou laisser le lien optionnel)

#### 1.5 Validation HTTP

- [x] `CreateRequest` : trait `ValideStructurable` + cohérence poste / `structurable_type`
- [x] FormRequests : `ActiverRequest`, `CloturerRequest` (`date_fin` avant ou égal à aujourd’hui), `RejeterRequest` (commentaire)
- [x] Messages métier en français

Cohérence poste / structure proposée :

| Poste | Structure |
|--------|-----------|
| Directeur Général, Directeur Central, Directeur Départemental | `Direction` |
| Chef de Service | `Service` |
| Chef de Bureau | `Bureau` |

#### 1.6 Historique

- [x] `nomination_creee` à la création
- [x] Historique + transaction sur activer / clôturer / rejeter / approuver
- [x] Commentaire de rejet enregistré

#### 1.7 Contrat API / OpenAPI

- [x] Tags `Carrière — Nominations` + alias `Intégration — Nominations` *deprecated*
- [x] Resource : `structure` (morph)
- [x] `showRelations` : `agent`, `structure`, `validations.validateur`
- [x] `getByAgent` : eager load + ordre `date_debut` desc
- [x] Schéma Swagger : `statut` = `en_attente` (pas `EN_ATTENTE`)

#### 1.8 Tests Feature `tests/Feature/NominationTest.php`

- [x] Création via `/carriere` et alias `/integration`
- [x] Structure inexistante → 422
- [x] Poste incohérent avec la structure → 422
- [x] Circuit → `approuvee` au dernier niveau
- [x] Activer depuis `en_attente` → 422
- [x] Activer clôture l’ancienne (structure **et** agent)
- [x] Activer **ne change pas** le statut dossier
- [x] Clôturer / rejeter + transitions 422

#### 1.9 Docs phase 1

- [x] `note-fe-routes-carriere.md` : préciser activation sans `NOMME`, `dossier_integration_id` ignoré
- [x] `plan-module-carriere.md` : lot 3 pointé vers ce fichier

**Done quand :** tests Feature verts ; activation sans effet sur le dossier ; circuit et transitions alignés sur l’affectation.

**Hors phase 1 :** seeder dédié, notifications, `permission:` bloquant, PDF d’acte, nouvelles routes, `nommerAutomatiquement`.

---

### Phase 2 — Métier carrière (lecture & actes)

**But :** endpoints et pièces utiles au FE carrière. Après merge de la phase 1.

#### 2.1 Lectures métier

- [x] `GET /carriere/nominations/postes-vacants` — structures sans nomination `active`
- [x] `GET /carriere/nominations/chefs/{id}/agents-sous-autorite` — agents dont le supérieur vient de cette nomination (déjà utilisé côté affectation)
- [x] `GET /carriere/agents/{id}/nominations/historique` — actives exclues ou filtre `statut` (ne pas casser `GET …/nominations` existant)

#### 2.2 Mise à jour avant activation

- [x] `PUT /carriere/nominations/{id}` — uniquement si `en_attente` (poste, structure, dates, `type_acte`)

#### 2.3 Acte administratif

- [x] Génération PDF selon `type_acte` (`arrete` / `decision` / `note_service`)
- [x] Aligner le vocabulaire avec `TypeActeAdministratif` (éviter deux enums divergents)
- [x] Route de téléchargement (même schéma que la note de service affectation, si besoin)

#### 2.4 Contrôle d’intégrité données

- [x] Index `agent_id + statut` pour `getActive` / `nominationActive`
- [x] Garde applicative (ou contrainte) : une seule `active` par agent et par structure
- [x] `ClotureStageService` : clôturer via `NominationService` (date_fin + historique), plus de `update` Eloquent direct

#### 2.5 Tests & docs phase 2

- [x] Tests postes vacants, sous-autorité, PUT 422 si déjà `active`
- [x] Note FE + OpenAPI
- [ ] Maquette `doc/maquettes/maquette-nomination-fe.canvas.tsx` (optionnel)

**Done quand :** le FE peut lister les postes à pourvoir, voir la ligne hiérarchique, et télécharger l’acte.

---

### Phase 3 — Plus tard (hors cette branche)

Ne pas coder maintenant. Décisions à prendre avec le FE / le plan global.

| Sujet | Pourquoi plus tard |
|--------|-------------------|
| Notifications (création, validation, activation) | Même report que l’affectation |
| Middleware `permission:` sur routes déjà consommées | Soft launch, contrainte `plan_complet.md` |
| Suppression des alias `/integration/nominations` | Après bascule FE coordonnée |
| `nommerAutomatiquement` + seeder contrat | Ancien design recrutement ; incompatible avec le flux actuel |
| `GET /carriere/agents/{id}` synthèse | Lot 5 du plan carrière (agrégation) |
| Lien `fonction_id` physique | `poste` string suffit en V1 |
| Renommage namespaces PHP / tables | Hors V1 (`plan-module-carriere.md`) |

---

## 4. Architecture cible (phase 1)

```
Route /carriere/nominations (+ alias /integration/nominations)
  → FormRequest
  → NominationController          // JSON uniquement, injecte NominationService
  → NominationService             // transitions, circuit, clôtures, historique
  → NominationInterface
  → NominationRepository          // Eloquent

ValidationWorkflowController
  → NominationService::approuver / rejeter   // dernier niveau / rejet d’étape
```

Pas de second circuit. Moteur partagé : `POST /integration/validations/{id}/…`.

---

## 5. Compatibilité frontend

- Zéro rupture d’URI : mêmes verbes sous `/carriere` et `/integration`.
- Body `dossier_integration_id` toujours accepté, ignoré.
- Statuts : valeurs **minuscules** (`en_attente`, pas `EN_ATTENTE`).
- Champ `statut_label` ajouté (non cassant).
- `structure` ajouté au détail si relation chargée.

---

## 6. Premier commit de code

**Phase 1 uniquement**, sur `feature/nomination`.  
Phases 2 et 3 : commits / PRs séparés.
