# Plan — Module Carrière & séparation des routes

> Branche : `feature/affectation`  
> Date : 2026-08-15  
> Objectif : figer le découpage **avant** le code, sans casser le frontend.

Références : [`workflow-integration-par-type.md`](./workflow-integration-par-type.md) · [`plan_complet.md`](./plan_complet.md) · [`architecture.md`](./architecture.md)

---

## 1. Règles métier (non négociables)

1. **Intégration** = identité administrative d’entrée  
   Type, dossier, pièces, validations, acte, contrat d’entrée, matricule, compte à l’entrée, convention de stage.
2. **Carrière** = situation administrative vivante  
   Affectation, nomination, contrats (vie du contrat), salaires agent, historiques.  
   Réutilisable toute la vie de l’agent (mutation, nouvelle nomination, etc.).
3. `INTEGRE` **ne dépend pas** d’une affectation ni d’une nomination.
4. La première affectation est un **raccourci depuis l’intégration**, pas une étape de dossier.
5. La nomination **n’apparaît pas** dans le wizard / checklist d’intégration.
6. Pas d’affectation automatique à la DRHL (ancien design recrutement, incompatible avec le flux actuel).

Chaîne technique inchangée : Route → FormRequest → Controller → Service → Interface → Repository.

---

## 2. Qui gère quoi

### `/api/integration` — entrée

| Domaine | Routes (rester) |
|---------|-----------------|
| Agents (création liée au dossier) | `apiResource agents`, `PATCH …/matricule` |
| Dossiers + transitions | soumettre → valider → `integrer`, historique, tâches |
| Documents du dossier | CRUD / valider |
| Circuit dossier | `dossiers/{id}/circuit` |
| Actes du dossier | générer / signer |
| Compte à l’entrée | `POST comptes/provisionner` |
| Accueil | remises matériel, prises de service |
| Stages (création / suivi lié au dossier) | `stages`, prolonger, clôturer, attestation |

### `/api/carriere` — vie de l’agent

| Domaine | Routes (cible) |
|---------|----------------|
| Affectations | CRUD lecture + store, groupée, activer, rejeter, terminer, notes de service, par agent |
| Nominations | idem (activer, clôturer, rejeter, par agent) |
| Contrats | index, store, show, résilier, par agent |
| Salaires agent | actuel, historique, bulletin, avancer-échelon, par agent |
| Synthèse (plus tard) | `GET /carriere/agents/{id}` — contrat / affectation / nomination / salaire actifs |

### Hors des deux préfixes (déjà en place)

- `/personnel` — annuaires agents / stagiaires
- `/salaires-agents`, grille — barème et paie transverse
- Validations `POST /integration/validations/{id}/…` — **moteur partagé**, on ne le duplique pas en V1
- Structure org. + référentiels + auth

### Ne va **pas** dans carrière (V1)

- Fiche agent CRUD (reste `integration/agents` pour le FE)
- Remise matériel, prise de service
- Notifications
- Avancement / évaluations (phases 5–6 du plan global)
- Renommage physique des controllers / namespaces PHP

---

## 3. Compatibilité frontend (obligatoire)

Le FE consomme déjà `/api/integration/affectations`, `nominations`, `contrats`, `agents/{id}/salaires`.

**Stratégie : nouvelles routes + alias, zéro rupture.**

1. Enregistrer les routes sous `prefix('carriere')`.
2. **Dupliquer** les mêmes verbes/URIs sous `prefix('integration')` (mêmes controllers).
3. OpenAPI : tags `Carrière — …` sur les nouvelles routes ; les anciennes restent documentées comme dépréciées.
4. Retrait des alias **uniquement** après bascule FE (livraison coordonnée, hors de cette branche si besoin).

Interdit en V1 : supprimer ou renommer une route `integration/*` existante.

---

## 4. Découplage dossier ↔ carrière

Aujourd’hui, activer une affectation / nomination peut passer le dossier à `AFFECTE` / `NOMME` si `dossier_integration_id` est fourni.

| Action | Décision |
|--------|----------|
| `marquerAffecte()` / `marquerNomme()` | Ne plus les appeler depuis les controllers carrière |
| Champ `dossier_integration_id` sur `ActiverRequest` | Le garder (nullable) pour ne pas casser le FE, **l’ignorer** côté métier |
| Statuts `AFFECTE`, `NOMME` | Les laisser dans l’enum (chemin A legacy). Ne plus les viser depuis le code neuf |
| Checklist `tachesPostIntegration` | Retirer étape 15 (nomination). Étape 14 : soit retirée, soit transformée en **lien** non bloquant (voir lot 2) |

Lien utile (sans verrouiller le dossier) :

```json
{
  "etape": 14,
  "label": "Affecter l'agent (module carrière)",
  "endpoint": "POST /carriere/affectations",
  "statut": "non_fait",
  "obligatoire": false
}
```

Si on retire 14 entièrement : le FE qui attend la clé `etape: 14` peut casser. **Préférer le lien optionnel** plutôt que la suppression sèche.

---

## 5. Lots de code (ordre)

### Lot 0 — Socle routes (petit, isolé)

- Dans `routes/api.php`, groupe `prefix('carriere')->middleware('auth:sanctum')`.
- Y monter : affectations, nominations, contrats, salaires-par-agent.
- Laisser les routes identiques sous `integration` (alias).
- Aucun changement métier.

**Done quand :** `GET/POST /api/carriere/affectations` répond comme `/api/integration/affectations`.

### Lot 1 — Affectation = module carrière (cette branche)

Cœur de `feature/affectation`. Pas de nouvelle feature métier (pas d’auto-DRHL).

1. **Controller mince**  
   Sortir de `AffectationController` : upload note de service, ZIP, `Storage`, `ZipArchive`.  
   Aller dans `AffectationService` (ex. `stockerNoteService`, `genererNotesServiceZip`).
2. **Découpler le dossier**  
   `activer()` n’appelle plus `marquerAffecte`.  
   `dossier_integration_id` accepté, ignoré.
3. **Durcir la validation**  
   `structurable_id` doit exister dans la table du `structurable_type` (Rule custom ou `Rule::exists` conditionnel). Idem `GroupeeRequest`.
4. **Permissions** : **ne pas** poser de `permission:` bloquant sur les routes déjà consommées (contrainte `plan_complet.md`). Soft launch plus tard.
5. **Tests Feature** `tests/Feature/AffectationTest.php`  
   - création unitaire + supérieur auto  
   - groupée  
   - circuit → `approuvee` auto au dernier niveau  
   - activer clôture l’ancienne active  
   - activer **ne change pas** le statut dossier  
   - terminer / rejeter + transitions 422  
   - `structurable_id` invalide → 422
6. **Docs**  
   `guide-test-integration.md` (tâche 14) : endpoints `/carriere/…` + alias.  
   `workflow-integration-par-type.md` : affectation / nomination hors statuts dossier.

**Hors lot 1 :** seeder dédié, `listResource`, notifications, renvoyer-pour-correction, déplacement des classes PHP.

### Lot 2 — Checklist intégration — ✅

- Étape 14 : `obligatoire: false`, `POST /carriere/affectations`, label clarifié (clé `etape` conservée).
- Étape 15 : conservée, `POST /carriere/nominations`, toujours optionnelle.
- Docs + note FE : [`note-fe-routes-carriere.md`](./note-fe-routes-carriere.md).

### Lot 3 — Nominations & contrats (même schéma, autre branche possible)

- Alias `/carriere/nominations` + `/carriere/contrats` (si pas déjà faits au lot 0).
- `NominationController::activer` n’appelle plus `marquerNomme`.
- Contrats : pas de changement de règles ; seul le préfixe bouge.
- Tests nomination : activer ne touche pas le dossier.

### Lot 4 — Salaires agent sous `/carriere` (alias)

- Déplacer les 5 routes `integration/agents/{agent}/salaires*` vers `carriere/agents/{agent}/salaires*`.
- Garder les alias `integration`.
- `/salaires-agents` et la grille **restent** à la racine.

### Lot 5 — Synthèse carrière (phase 4.3 du plan global)

- `GET /carriere/agents/{id}` : `contratActif`, `affectationActive`, `nominationActive`, `salaireActuel`.
- Pas de logique métier nouvelle : agrégation de lectures existantes.
- Permissions lecture à définir avec le FE.

---

## 6. Architecture cible (lot 1)

```
Route /carriere/affectations (+ alias /integration/affectations)
  → FormRequest
  → AffectationController          // JSON uniquement, injecte AffectationService
  → AffectationService             // transitions, groupe, PDF, ZIP, upload, supérieur
  → AffectationInterface
  → AffectationRepository          // Eloquent + résolution supérieur
```

`ValidationWorkflowController` continue d’appeler `AffectationService::approuver/rejeter` quand le validable est une `Affectation`. Pas de second circuit.

---

## 7. Hors scope (ne pas coder maintenant)

- `affecterAutomatiquement(DRHL)`
- Notifications
- Middleware `permission:` sur routes existantes
- Suppression des alias `integration/*`
- Migration des URI FE
- Renommer `CompteIntegrationController` / tables
- Soft delete agent
- Module congés / évaluations

---

## 8. Ordre de merge suggéré

```
Lot 0  →  Lot 1 (cette branche)  →  Lot 2  →  Lots 3–4  →  Lot 5
              │
              └─ review FE : les alias integration/* marchent encore
```

Après lot 1, le module affectation est **déjà** un module carrière, même si le FE appelle encore `/integration/affectations`.

---

## 9. Critères de fin — lot 1 (pour commencer à coder)

- [x] `POST /api/carriere/affectations` et alias `integration` identiques
- [x] Controller sans filesystem / ZIP / `DossierIntegrationService`
- [x] Activation sans changement de statut dossier
- [x] Structure inexistante → 422
- [x] Tests Feature verts
- [x] Guides mis à jour (carrière + alias)

---

## 10. Premier commit de code

**Lot 0 + lot 1 uniquement**, sur `feature/affectation`.  
Lots 2–5 : commits / PRs séparés pour limiter l’impact FE.
