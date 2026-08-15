# Guide de test d’acceptation V2

> **Périmètre :** socle → intégration → grille → salaires agents (fin Phase 3)  
> **Date :** 2026-08-08  
> **Base URL :** `http://localhost:8000/api`  
> **Outil :** Insomnia / Postman (+ smoke FE si disponible)

**Headers** (sauf login et tests « sans token ») :
```
Accept: application/json
Authorization: Bearer {token}
Content-Type: application/json
```

**Guides détaillés (référence) :**
- [`guide-test-integration.md`](./guide-test-integration.md) — recrutement / contractuel
- [`guide-test-integration-stage.md`](./guide-test-integration-stage.md) — stage
- [`guide-test-salaires-agents.md`](./guide-test-salaires-agents.md) — grille & salaires

**Ordre recommandé :**
```text
A (smoke) → B (grille) → D (contractuel+salaires) → C (permanent) → E (stage) → F (FE) → G (erreurs)
```

**Bloquant avant Phase suivante :** A + B + D + F.

---

## Variables à noter pendant les tests

| Variable | Exemple | Source |
|---|---|---|
| `{token}` | `1\|abc…` | A1 |
| `{agent_id_c}` | `42` | Parcours C |
| `{dossier_id_c}` | `7` | Parcours C |
| `{agent_id_d}` | `50` | Parcours D |
| `{dossier_id_d}` | `12` | Parcours D |
| `{salaire_agent_id}` | `1` | D3 |
| `{agent_id_e}` | `43` | Parcours E |
| `{dossier_id_e}` | `8` | Parcours E |
| `{convention_id}` | `1` | Parcours E |

**IDs seed utiles (après `db:seed`) :**

| Entité | ID typique | Sigle / nom |
|---|---|---|
| Type intégration Recrutement externe | `1` | — |
| Type intégration Contractuel | `6` | `necessite_contrat: true` |
| Type intégration Stage pro | `7` | — |
| Type contrat CDI | `1` | `CDI` |
| Type contrat CDD | `2` | `CDD` |
| Type contrat Stage | `3` | `STG` |
| Diplôme Master | `23` | Classe VIII |
| Catégorie Classe I | `1` | Personnel de service |
| Grade Personnel de service | `1` | coeff. 45 |
| Fonction (ex.) | `3` | selon seeder |

> Si les IDs diffèrent, les résoudre via `GET /types-integrations`, `GET /types-contrats`, `GET /diplomes`.

---

## Préparation environnement

| # | Action | Critère OK | ☐ |
|---|---|---|---|
| P0 | Base propre : `php artisan migrate:fresh --seed` | Seeders sans erreur | ☐ |
| P1 | Si env déjà seedé : `php artisan db:seed --class=PermissionSeeder` puis `RoleSeeder` | Permissions `consulter-salaires` / `gerer-salaires` sur admin & rh | ☐ |
| P2 | Serveur API démarré | `GET /health` → `{ "status": "ok" }` | ☐ |
| P3 | Compte | `admin@arft.cg` / `Admin@2026` | ☐ |

---

## Bloc A — Smoke socle (~30 min)

### A1 — Login

```
POST /login
```
```json
{
  "email": "admin@arft.cg",
  "password": "Admin@2026"
}
```

> Comptes seed : `admin@arft.cg` / `Admin@2026` · `rh@arft.cg` / `Rh@2026`  
> (ne pas utiliser `admin@artf.cg` / `password` — absents du seeder).

| Critère | ☐ |
|---|---|
| Status **200** | ☐ |
| Corps contient `token` (et infos user) | ☐ |
| Copier `{token}` dans le header Authorization | ☐ |

### A2 — Profil

```
GET /user
```

| Critère | ☐ |
|---|---|
| Status **200** | ☐ |
| Email / rôle admin cohérents | ☐ |

### A3 — Structure organisationnelle

```
GET /localites
GET /administrations
GET /directions
GET /services
GET /bureaux
```

| Critère | ☐ |
|---|---|
| Chaque liste non vide (`data` rempli) | ☐ |

### A4 — Référentiels RH

```
GET /diplomes
GET /grades
GET /categories
GET /echelons
GET /fonctions
```

| Critère | ☐ |
|---|---|
| Listes non vides | ☐ |
| Au moins 10 catégories / 10 grades / 12 échelons | ☐ |

### A5 — Types métier

```
GET /types-integrations
GET /types-contrats
GET /types-documents
```

| Critère | ☐ |
|---|---|
| Recrutement externe, Contractuel, Stage présents | ☐ |
| CDI, CDD, STG présents | ☐ |
| `necessite_contrat` correct (false / true / true) | ☐ |

### A6 — Auth obligatoire intégration

```
GET /integration/agents
```
*(sans header Authorization)*

| Critère | ☐ |
|---|---|
| Status **401** | ☐ |

### A7 — Auth obligatoire salaires

```
GET /salaires
POST /salaires/generation
```
*(sans Authorization ; body génération optionnel)*

| Critère | ☐ |
|---|---|
| Status **401** sur les deux | ☐ |

**Bilan A :** __ / 7 — Bloquant si A1, A6 ou A7 KO.

---

## Bloc B — Grille salariale (~20 min)

> Permission : `consulter-salaires` (lecture) / `gerer-salaires` (génération). Admin possède les deux.

### B1 — Paramètres grille

```
GET /grille-parametres/current
```

| Critère | ☐ |
|---|---|
| Status **200** | ☐ |
| `valeur_point_indice` = 300 (défaut seed) | ☐ |
| `echelon_depart` = 1, `echelon_fin` = 12 | ☐ |

### B2 — Génération

```
POST /salaires/generation
```
```json
{
  "valeur_point_indice": 300
}
```

| Critère | ☐ |
|---|---|
| Status **200** | ☐ |
| `success: true` | ☐ |
| Clés présentes : `message`, `data`, `total`, `valeur_point_indice`, `echelon_depart`, `echelon_fin` | ☐ |
| `total` = **120** | ☐ |

### B3 — Contrôle formule Classe I / éch. 1

```
GET /salaires
```

Chercher la ligne : coefficient classe **45**, `echelon` **1**.

| Critère | ☐ |
|---|---|
| Status **200**, `total` = 120 | ☐ |
| `indice` = **490** | ☐ |
| `salaire` = **147000** | ☐ |

### B4 — Permission génération (optionnel si compte rh/agent dispo)

Connexion avec un user **sans** `gerer-salaires` (ex. rôle `agent`) :

```
POST /salaires/generation
```

| Critère | ☐ |
|---|---|
| Status **403** | ☐ |

### B5 — Permission lecture

User avec `consulter-salaires` (rh / directeur / chef-service) :

```
GET /salaires
```

| Critère | ☐ |
|---|---|
| Status **200** | ☐ |

**Bilan B :** __ / 5 — Bloquant si B2 ou B3 KO.

---

## Bloc D — Contractuel + salaires agents (~60–75 min) — **critique Phase 3**

Exécuter **après B2** (grille générée). Détail des transitions RH : voir guide intégration (étapes 4→11).

### D1 — Créer agent Contractuel (Classe I pour contrôle 147 000)

```
POST /integration/agents
```
```json
{
  "nom": "KAYA",
  "prenom": "Alice",
  "date_naissance": "1992-06-20",
  "lieu_naissance": "Pointe-Noire",
  "nationalite": "Congolaise",
  "genre": "F",
  "telephone": "+242 06 111 22 33",
  "email_personnel": "a.kaya.accept@test.cg",
  "type_integration_id": 6,
  "categorie_id": 1,
  "grade_id": 1,
  "echelon_id": 1,
  "fonction_id": 3
}
```

| Critère | ☐ |
|---|---|
| Status **201** | ☐ |
| `agent.id` et `dossier.id` notés (`{agent_id_d}`, `{dossier_id_d}`) | ☐ |
| `dossier.statut` = `BROUILLON` | ☐ |
| Agent : `categorie_id` / `grade_id` / `echelon_id` renseignés | ☐ |

### D2 — Contrat CDI → salaire auto

```
POST /integration/contrats
```
```json
{
  "agent_id": "{agent_id_d}",
  "dossier_integration_id": "{dossier_id_d}",
  "type_contrat_id": 1,
  "fonction_id": 3,
  "date_debut": "2026-08-01",
  "remuneration": 147000
}
```

| Critère | ☐ |
|---|---|
| Status **201**, contrat `statut: actif` | ☐ |
| Un `salaires_agents` actif a été créé en coulisse | ☐ |

### D3 — Salaire actuel

```
GET /integration/agents/{agent_id_d}/salaires/actuel
```

| Critère | ☐ |
|---|---|
| Status **200** | ☐ |
| `statut` = `actif` | ☐ |
| `echelon` = 1 | ☐ |
| `montant_base` = **147000** (Classe I) | ☐ |
| `type_changement` = `initial` | ☐ |
| Noter `{salaire_agent_id}` | ☐ |

### D4 — Circuit jusqu’à intégration (résumé)

Enchaîner (body vide sauf documents) :

```
POST /integration/dossiers/{dossier_id_d}/soumettre
POST /integration/dossiers/{dossier_id_d}/passer-en-etude-rh
POST /integration/dossiers/{dossier_id_d}/documents          (multipart)
POST /integration/documents/{doc_id}/valider
POST /integration/dossiers/{dossier_id_d}/marquer-complet
POST /integration/dossiers/{dossier_id_d}/valider-rh
POST /integration/validations/{id}/approuver                 (répéter niveaux)
POST /integration/dossiers/{dossier_id_d}/generer-acte       (si requis par le flux)
POST /integration/dossiers/{dossier_id_d}/marquer-contrat-signe
POST /integration/dossiers/{dossier_id_d}/integrer
```

| Critère | ☐ |
|---|---|
| Statut final `INTEGRE` | ☐ |
| Réponse contient `taches_post_integration` | ☐ |
| Chaque tâche a les clés : `etape`, `label`, `endpoint`, `statut`, `obligatoire` | ☐ |

### D5 — Tâche « Salaire initial »

```
GET /integration/dossiers/{dossier_id_d}/taches-post-integration
```

| Critère | ☐ |
|---|---|
| Une tâche mentionne le salaire initial / `salaires/actuel` | ☐ |
| Son `statut` = `fait` | ☐ |

### D6 — Avancer d’échelon

```
POST /integration/agents/{agent_id_d}/salaires/avancer-echelon
```
```json
{
  "motif": "Acceptation V2 — avancement test"
}
```

| Critère | ☐ |
|---|---|
| Status **200** | ☐ |
| Nouveau salaire : `echelon` = 2, `statut` = `actif` | ☐ |
| `type_changement` = `avancement_echelon` | ☐ |
| `motif` renseigné | ☐ |
| `montant_base` = **160500** (Classe I éch. 2) | ☐ |

### D7 — Historique

```
GET /integration/agents/{agent_id_d}/salaires/historique
```

| Critère | ☐ |
|---|---|
| Status **200**, au moins **2** lignes | ☐ |
| Ordre chronologique (éch. 1 puis éch. 2) | ☐ |
| Première ligne : `type_changement` = `initial`, variations `null` | ☐ |
| Deuxième ligne : `echelon_precedent` = 1, `variation_echelon` = 1 | ☐ |
| `montant_precedent` = 147000, `variation_montant` = 13500 | ☐ |

### D8 — Bulletin PDF (actif)

```
GET /integration/agents/{agent_id_d}/salaires/bulletin
```

| Critère | ☐ |
|---|---|
| Status **200** | ☐ |
| `Content-Type` contient `application/pdf` | ☐ |
| PDF ouvrable (nom agent / montants visibles) | ☐ |

### D9 — Bulletin PDF par id

```
GET /salaires-agents/{salaire_agent_id}/bulletin
```

| Critère | ☐ |
|---|---|
| Status **200**, PDF | ☐ |

### D10 — Liste & clôture

```
GET /integration/agents/{agent_id_d}/salaires
GET /salaires-agents?agent_id={agent_id_d}
POST /salaires-agents/{id_actif}/cloturer
```
```json
{
  "date_fin": "2026-08-08",
  "motif": "Acceptation V2 — clôture test"
}
```

| Critère | ☐ |
|---|---|
| Liste contient actif + clôturé(s) | ☐ |
| Après clôture : `statut` = `cloture`, `date_fin` renseignée | ☐ |

### D11 — Stage / non CDI-CDD → pas de salaire

Créer un agent stage (ou réutiliser E) avec `type_contrat_id: 3` **sans** s’attendre à un salaire :

```
GET /integration/agents/{agent_id_e}/salaires/actuel
```

| Critère | ☐ |
|---|---|
| **404** (aucun salaire actif) — ou agent stage sans ligne `salaires_agents` | ☐ |

### D12 — Plafond échelon

Sur un agent déjà à `echelon_fin` (12), ou après 11 avancements de test :

```
POST /integration/agents/{agent_id}/salaires/avancer-echelon
```

| Critère | ☐ |
|---|---|
| Status **422** | ☐ |
| Message indiquant dernier échelon atteint | ☐ |

> D12 peut être reporté en fin de campagne si chronophage.

**Bilan D :** __ / 12 — Bloquant si D2, D3, D6, D7 ou D8 KO.

---

## Bloc C — Recrutement externe / permanent (~45–60 min)

> Pas de contrat. Pas de salaire auto. Détail complet : [`guide-test-integration.md`](./guide-test-integration.md).

### C1 — Créer agent + dossier

```
POST /integration/agents
```
```json
{
  "nom": "LOUPPE",
  "prenom": "Thierry",
  "date_naissance": "1990-03-15",
  "lieu_naissance": "Brazzaville",
  "nationalite": "Congolaise",
  "genre": "M",
  "telephone": "+242 06 123 45 67",
  "email_personnel": "t.louppe.accept@test.cg",
  "type_integration_id": 1,
  "diplome_id": 23,
  "fonction_id": 3
}
```

| Critère | ☐ |
|---|---|
| Status **201** | ☐ |
| Noter `{agent_id_c}`, `{dossier_id_c}` | ☐ |
| `categorie_id` / `grade_id` / `echelon_id` auto via diplôme | ☐ |

### C2 — Circuit RH jusqu’à VALIDE_DG / acte

Enchaîner soumettre → étude RH → documents → complet → valider RH → circuit → (générer acte).

| Critère | ☐ |
|---|---|
| Transitions acceptées (pas de 422 illégal) | ☐ |
| Statut progresse jusqu’à validation DG / acte | ☐ |

### C3 — Intégrer

```
POST /integration/dossiers/{dossier_id_c}/integrer
```

| Critère | ☐ |
|---|---|
| Status **200**, statut `INTEGRE` | ☐ |
| Compte utilisateur créé (si prévu) | ☐ |
| `taches_post_integration` présent avec clés FE | ☐ |

### C4 — Post-intégration minimale

```
POST /integration/dossiers/{dossier_id_c}/assigner-matricule
POST /carriere/affectations
GET /integration/dossiers/{dossier_id_c}/taches-post-integration
```

| Critère | ☐ |
|---|---|
| Matricule renseigné | ☐ |
| Affectation créée | ☐ |
| Tâches concernées en `fait` | ☐ |

### C5 — Historique dossier

```
GET /integration/dossiers/{dossier_id_c}/historique
```

| Critère | ☐ |
|---|---|
| Status **200**, liste non vide | ☐ |

### C6 — Pas de salaire auto

```
GET /integration/agents/{agent_id_c}/salaires/actuel
```

| Critère | ☐ |
|---|---|
| Status **404** | ☐ |

**Bilan C :** __ / 6 — Fortement recommandé (1 parcours permanent vert).

---

## Bloc E — Stage (~45 min)

> Détail : [`guide-test-integration-stage.md`](./guide-test-integration-stage.md).

### E1 — Stagiaire + convention STG

```
POST /integration/agents
```
```json
{
  "nom": "MOBILA",
  "prenom": "Jean",
  "date_naissance": "2001-05-12",
  "lieu_naissance": "Brazzaville",
  "nationalite": "Congolaise",
  "genre": "M",
  "telephone": "+242 06 555 12 34",
  "email_personnel": "j.mobila.accept@test.cg",
  "type_integration_id": 7,
  "fonction_id": 8,
  "notes": "Acceptation V2 — stage"
}
```

```
POST /integration/contrats
```
```json
{
  "agent_id": "{agent_id_e}",
  "dossier_integration_id": "{dossier_id_e}",
  "type_contrat_id": 3,
  "fonction_id": 8,
  "date_debut": "2026-07-01",
  "date_fin": "2026-12-31",
  "remuneration": 150000
}
```

| Critère | ☐ |
|---|---|
| Agent + dossier créés | ☐ |
| Contrat STG créé | ☐ |
| **Aucun** salaire agent (`GET .../salaires/actuel` → 404) | ☐ |

### E2 — Circuit + intégration

Même enchaînement RH que C/D, puis :

```
POST /integration/dossiers/{dossier_id_e}/integrer
```

| Critère | ☐ |
|---|---|
| Statut `INTEGRE` | ☐ |
| Convention de stage créée / consultable | ☐ |

### E3 — Lister / prolonger

```
GET /integration/stages
PATCH /integration/stages/{convention_id}/prolonger
```
```json
{
  "date_fin": "2027-03-31"
}
```

| Critère | ☐ |
|---|---|
| Liste non vide / filtre OK | ☐ |
| Date de fin mise à jour | ☐ |

### E4 — Clôturer + attestation PDF

```
POST /integration/stages/{convention_id}/cloturer
GET /integration/stages/{convention_id}/attestation
```

| Critère | ☐ |
|---|---|
| Stage terminé | ☐ |
| Attestation PDF (`application/pdf`) | ☐ |

**Bilan E :** __ / 4 — Fortement recommandé (1 parcours stage vert).

---

## Bloc F — Non-régression contrat API / FE (~20 min)

| ID | Contrôle | Critère | ☐ |
|---|---|---|---|
| F1 | Login / logout / user (FE ou API) | Parcours auth inchangé | ☐ |
| F2 | Écrans grille FE : `GET /salaires` + génération | Fonctionnent **avec** Bearer ; pas de rupture de clés `success` / `message` / `data` | ☐ |
| F3 | Champs ajoutés génération | `total`, `valeur_point_indice`, `echelon_depart`, `echelon_fin` **en plus**, sans retirer l’existant | ☐ |
| F4 | `taches_post_integration` | Clés `etape`, `label`, `endpoint`, `statut`, `obligatoire` toujours présentes | ☐ |
| F5 | Intégration FE (permanent ou contractuel) | Happy path écran sans erreur 500 / contrat cassé | ☐ |

**Bilan F :** __ / 5 — Bloquant si F2 ou F4 KO.

---

## Bloc G — Cas d’erreur (~20 min)

| ID | Action | Attendu | ☐ |
|---|---|---|---|
| G1 | Transition dossier illégale (ex. `valider-dg` depuis `BROUILLON`) | **422** + message clair | ☐ |
| G2 | Créer salaire agent **sans** grille (`migrate:fresh` sans B2, ou DB `salaires` vide) | **422** « Générez d’abord la grille… » | ☐ |
| G3 | Agent sans `categorie_id`/`grade_id` → `POST /salaires-agents` | **422** classe introuvable | ☐ |
| G4 | `POST /salaires-agents/{id}/cloturer` sur salaire déjà clôturé | **422** | ☐ |
| G5 | User authentifié sans `consulter-salaires` → `GET /salaires` | **403** | ☐ |

**Bilan G :** __ / 5 — Qualité ; ne bloque pas seul si A–D–F verts.

---

## Synthèse campagne

| Bloc | Titre | Score | Statut |
|---|---|---|---|
| A | Smoke socle | **7 / 7** (exécuté 2026-08-08) | ☑ OK · ☐ KO |
| B | Grille | **5 / 5** (exécuté 2026-08-08) | ☑ OK · ☐ KO |
| D | Contractuel + salaires | **12 / 12** (exécuté 2026-08-08) | ☑ OK · ☐ KO |
| C | Permanent | **6 / 6** (exécuté 2026-08-08) | ☑ OK · ☐ KO |
| E | Stage | **4 / 4** (exécuté 2026-08-08) | ☑ OK · ☐ KO |
| F | Non-régression FE | __ / 5 | ☐ OK · ☐ KO · ☐ N/A (manuel FE) |
| G | Erreurs | **5 / 5** (exécuté 2026-08-08) | ☑ OK · ☐ KO |

### Go / No-Go Phase suivante

| Condition | ☐ |
|---|---|
| A1–A7 verts | ☑ |
| B2–B3 verts (147 000) | ☑ |
| D2, D3, D6, D7, D8 verts | ☑ |
| Au moins **C** ou **E** vert de bout en bout | ☑ (C et E) |
| F2 + F4 verts | ☐ à valider côté FE |
| Bugs connus listés ci-dessous | ☑ |

**Décision :** ☑ **GO API** Phase 2/4/5 (sous réserve smoke FE bloc F) · ☐ **NO-GO**

---

## Bugs / écarts constatés

| # | Bloc / ID | Sévérité (Bloquant / Majeur / Mineur) | Description | Contournement |
|---|---|---|---|---|
| 1 | Guides A1 | Mineur | Identifiants docs `admin@artf.cg` / `password` incorrects | Utiliser `admin@arft.cg` / `Admin@2026` (corrigé dans guides) |
| 2 | D2 | Bloquant → **corrigé** | `ContratController::store` appelait `marquerContratSigne` dès la création (BROUILLON → CONTRAT_SIGNE invalide) → HTTP 422 malgré contrat créé | Retiré l’auto-appel ; signature via `POST .../marquer-contrat-signe` uniquement |
| 3 | C4 | Bloquant → **corrigé** | `assignerMatricule` forçait transition vers `MATRICULE_CREE` même si dossier déjà `INTEGRE` (mode post-intégration) | Si statut `INTEGRE` / `MATRICULE_CREE` : assigne le matricule sans transition |
| 4 | E2 | Mineur | Après `integrer` stage, `agent.statut` parfois encore `actif` dans la réponse (attendu `stagiaire`) | Vérifier en base / recharger agent ; clôture stage passe bien à `inactif` |

---

## Journal d’exécution

| Champ | Valeur |
|---|---|
| Date | 2026-08-08 |
| Environnement | local (`127.0.0.1:8000`) |
| Exécutant | Auto (API) |
| Commit / branche API | worktree local Phase 3 + correctifs acceptation |
| Commit / branche FE | — (bloc F non exécuté) |
| Durée totale | ~ session acceptation A–E + G |
| Commentaire | GO API ; F à valider manuellement côté frontend |
