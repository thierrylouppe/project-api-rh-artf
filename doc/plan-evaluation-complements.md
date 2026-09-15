# Plan — Compléments évaluation / avancement

> Branche : `feature/evaluation-complements` (depuis `develop`)  
> Date : **2026-09-14** · **Close : 2026-09-15**  
> Document **vivant** : cocher à chaque livraison.  
> Socle déjà livré (P1–P5) : [`plan-module-evaluation-notation.md`](./plan-module-evaluation-notation.md)  
> Droit ARTF : [`convention-artf-avancement.md`](./convention-artf-avancement.md) — art. 62, 67, 73–75  
> Contrat FE : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md)

**Objectif :** fermer les **trous du tableau d’avancement V1** (PDF, notateur art. 62, inscription D5), puis poser le **ch. 5 CCN** (reclassement / hors classe / reconversion) **hors** `/avancements`.

**État :** lots **A–D livrés**. Ne pas relancer P1–P5 ni A–D. Hors lot : catalogue formations, concours, PDF acte de reclassement. Test optionnel A.6 (chef de structure sans `superieur_hierarchique_id`).

---

## 1. Cadre (inchangé)

Chaîne : Route → FormRequest → Controller → **Service** → **Interface** → Repository → Model.

- Controller : Services uniquement. Service : Interfaces uniquement.
- Préfixe notation : **`/api/avancements`**.
- Préfixe reclassement (lot D) : **`/api/carriere`** — ce n’est **pas** un échelon dans la même classe (art. 60).
- PDF : même schéma que les congés (`Barryvdh\DomPDF`, vues `resources/views/pdf/`).
- Permissions existantes : `consulter-evaluations`, `creer-evaluations`, `valider-evaluations`. Lot D : **nouvelle** permission seulement si le FE en a besoin (`gerer-reclassements` proposé).
- Notifications : canal `database` via `NotificationService`. Mail / SMS hors scope.

---

## 2. État actuel (constat code, 2026-09-15 — lots A–D livrés)

| Sujet | Code aujourd’hui | Écart CCN / plan |
|--------|------------------|------------------|
| PDF fiche + synthèse | `EvaluationPdfService` + vues `pdf/fiche-evaluation` et `pdf/note-synthese-preparatoire` | Livré lot C |
| Note de synthèse | Champ `evaluations.note_synthese` + PDF commission après clôture | Livré lot C |
| Notateur | Poste où l’agent a **servi le plus longtemps** sur `[debut − 24 mois, debut)` | Livré lot A |
| Tableau d’avancement (D5) | `inscrit_tableau` ; `GET …/tableau` ; 422 si `decider` hors tableau | Livré lot B |
| Art. 73–75 | `/api/carriere/reclassements` + `changerClasse` | Livré lot D |

`avancerEchelon` / `avancerEchelons` restent **même classe**. Ils ne servent **pas** le ch. 5.

---

## 3. Lots et ordre

```
Lot A  Art. 62 — notateur = affectation dominante
    → Lot B  D5 — inscription au tableau d’avancement
        → Lot C  5.5 — PDF fiche + note de synthèse
            → Lot D  Art. 73–75 — reclassement / hors classe / reconversion
```

| Lot | Livrable FE | Priorité | Statut |
|-----|-------------|----------|--------|
| **A** | Fiche générée avec le bon N+1 si mutation dans la période | Haute métier (légal) | ✅ |
| **B** | Liste « tableau » filtrable ; inscrire / retirer avant commission d’avancement | Haute métier (D5) | ✅ |
| **C** | Télécharger fiche PDF + PDF synthèse préparatoire | Moyenne (plan 5.5) | ✅ |
| **D** | Demandes de reclassement / hors classe / reconversion + effet paie **classe** | Hors notation V1 | ✅ |

Ne pas relancer A–D. Suite hors évaluation : [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md) **D.2 Discipline**.

---

## Lot A — Mutation en cours d’année (art. 62)

**Règle :** *« Le salarié muté ou affecté en cours d’année est noté au poste où il a servi le plus longtemps. »*

Aujourd’hui le N+1 = `affectationRepository->getActive($agentId)->superieur_hierarchique_id`.

### A.1 Période de calcul

Période de service = `[session.debut_session − 24 mois, session.debut_session)`  
(cycle art. 62, pas seulement l’année civile).

Si `date_prise_service` est plus récente que le début de période → tronquer au `date_prise_service`.

### A.2 Affectations prises en compte

- Statuts `active` **et** `cloturee` (une mutation clôture l’ancienne).
- Chevauchement avec la période : `date_affectation` … `date_fin` (si `date_fin` null → `min(aujourd’hui, fin de période)`).
- Durée en **jours calendaires** d’intersection.

**Égalité de durée :** affectation la plus **récente** (`date_affectation` max, puis `id` max).

### A.3 Résolution du notateur

1. Affectation **dominante** (plus longue sur la période).
2. `superieur_id` = `superieur_hierarchique_id` de cette affectation.
3. Si null → `resoudreSuperiorParStructure(structurable_type, structurable_id)` (déjà sur `AffectationInterface`).
4. Si toujours null → pas de fiche, agent dans `sans-superieur` (comportement actuel).

L’agent n’est **jamais** son propre évaluateur (règle existante à conserver).

### A.4 Traçabilité (non-breaking)

Ajouter sur `evaluations` (nullable) :

| Champ | Rôle |
|-------|------|
| `affectation_notation_id` | Affectation retenue pour la notation |

Resource : exposer `affectation_notation: { id, date_affectation, date_fin, structure }` **si chargé**.  
Le FE affiche « Noté au poste X » à partir des dates (pas de `duree_jours` stocké). Pas un 2ᵉ N+1 à saisir.

### A.5 Où coder

- Extraire la résolution dans `SuperieurHierarchiqueService` (déjà le point unique N+1 des congés) :
  - `affectationDominante(int $agentId, Carbon $debut, Carbon $fin): ?Affectation`
  - `resoudreNotateurPourPeriode(...)`
- `SessionEvaluationService::genererFiches` appelle ce service (plus `getActive` seul).
- `AffectationInterface` : méthode lecture `getPourAgentSurPeriode(int $agentId, $debut, $fin): Collection` — **pas** de métier dans le repository.

Réattribution RH (`POST …/evaluations/{id}/superieur`) **reste possible** tant que la session est ouverte (déjà livré).

### A.6 Tests Feature

- [x] Une seule affectation active → même N+1 qu’aujourd’hui.
- [x] Mutation : 8 mois poste A + 4 mois poste B → notateur de A.
- [x] Égalité 6 / 6 → notateur du poste le plus récent.
- [ ] Affectation sans `superieur_hierarchique_id` mais chef de structure → N+1 résolu.
- [x] Aucun chef → `sans-superieur`, pas de fiche.
- [x] Agent = son propre chef → pas de fiche / liste `sans-superieur`.

### A.7 Note FE

Une ligne : génération de session, N+1 = poste dominant sur 24 mois, champ optionnel `affectation_notation`.

**Done quand :** tests verts ; fiches existantes **non** recalculées (seulement nouvelles générations). Pas de backfill obligatoire.

---

## Lot B — D5 : qui entre au tableau d’avancement

**Décision à figer (proposition, à confirmer RH) :**

| Étape | Population |
|--------|------------|
| Notation + avis + RH | Tous les éligibles (inchangé) |
| Commission **préparatoire** (art. 66–68) | Toutes les fiches `finalisee` (cohérence des notes) |
| **Tableau d’avancement** (commission art. 69–70) | Uniquement les fiches `finalisee` **inscrites** |

Inscription **par défaut : oui** à la validation RH (`finalisee` → `inscrit_tableau = true`).  
La RH peut **retirer** avant l’ouverture de la commission d’avancement (agent noté mais pas proposé à l’avancement).

Alternative rejetée : « tous les `finalisee` sans filtre » — c’est le comportement actuel ; ça mélange notation et tableau.

### B.1 Données

Sur `evaluations` :

| Champ | Type | Défaut |
|-------|------|--------|
| `inscrit_tableau` | boolean | `false` ; passé à `true` dans `valider-rh` si `conforme` |

Pas de nouvelle table. Index `(session_id, inscrit_tableau)`.

### B.2 API

| Méthode | URI | Permission | Règle |
|---------|-----|------------|--------|
| `GET` | `/avancements/sessions/{id}/tableau` | `consulter-evaluations` | Fiches `finalisee` + `inscrit_tableau=true` |
| `POST` | `/avancements/evaluations/{id}/inscrire-tableau` | `valider-evaluations` | 422 si pas `finalisee` ; 422 si commission d’avancement **clôturée** |
| `POST` | `/avancements/evaluations/{id}/retirer-tableau` | `valider-evaluations` | Idem ; 422 si décision `commission_decision` déjà posée |

`CommissionAvancementService::decider` : 422 si `inscrit_tableau = false`.  
Ouvrir la commission d’avancement : possible même si 0 inscrit (la RH décide) — documenter.

Resource : `inscrit_tableau` (bool). `prochaine_etape` : après `finalisee`, si RH → `inscrire_tableau` ou `commission_preparatoire` selon que la préparatoire est ouverte.

### B.3 Tests Feature

- [x] `valider-rh` conforme → `inscrit_tableau=true`.
- [x] Retirer puis `decider` → 422.
- [x] `GET …/tableau` n’inclut pas les non inscrits.
- [x] Inscrire après clôture commission d’avancement → 422.

### B.4 Note FE

Tableau dédié + 2 boutons RH. Ne pas casser la liste actuelle des fiches (filtre query optionnel `inscrit_tableau=1`).

**Done quand :** D5 tranché dans ce fichier (✅ ci-dessus) + tests + journal FE.

---

## Lot C — 5.5 PDF fiche d’évaluation + note de synthèse

Même contrat que les congés : `GET` stream PDF, `permission:consulter-evaluations`, 403 si hors périmètre (agent = sa fiche ; N+1 = ses fiches ; `rh` / DG = session).

### C.1 Fiche individuelle (art. 63)

| Méthode | URI | Disponible dès |
|---------|-----|----------------|
| `GET` | `/avancements/evaluations/{id}/fiche-pdf` | Statut ≥ `signee_evalue` (note portée à connaissance). **422** avant. |

Contenu (vue `pdf/fiche-evaluation.blade.php`) :

- Identité agent, session, N+1, affectation de notation (lot A si présent)
- Grille 24 questions (note / barème) + `note_globale` + mention
- Avis N+1, signatures (dates), avis hiérarchiques
- Réclamation si existante
- `commission_note`, `note_synthese`, `note_avancement`, décision commission si renseignés

Nom fichier : `fiche-evaluation-{session}-{matricule}.pdf`.

### C.2 Note de synthèse préparatoire (art. 67)

La CCN demande **une** note de synthèse adressée à la commission d’avancement (points débattus), pas seulement le champ par fiche.

| Méthode | URI | Disponible dès |
|---------|-----|----------------|
| `GET` | `/avancements/commissions-preparatoires/{id}/synthese-pdf` | Commission préparatoire **clôturée**. **422** si encore `en_cours`. |

Contenu (vue `pdf/note-synthese-preparatoire.blade.php`) :

- Session, dates, président (DG), `observations` de la commission
- Tableau des fiches `finalisee` : agent, note N+1, `commission_note`, écart, `note_synthese` par fiche
- Alertes écart > 5 (déjà en API `GET …/alertes`)

Option (même lot, si le FE le demande) : `GET /avancements/evaluations/{id}/synthese-pdf` = extrait d’une seule fiche (réutilise le champ `note_synthese`).

### C.3 Couches

- `EvaluationPdfService` (nouveau) — **pas** de PDF dans le Controller.
- Le service charge via `EvaluationInterface` / `CommissionPreparatoireInterface`.
- Controllers existants : 2 actions minces (comme `DemandeCongeController::fichePdf`).

Pas de stockage disque V1 (stream, comme les congés). GED plus tard.

### C.4 Tests Feature

- [x] Fiche `notee` sans signature agent → 422.
- [x] Fiche `signee_evalue` → 200, `Content-Type: application/pdf`.
- [x] Agent A ne télécharge pas la fiche de B → 403.
- [x] Synthèse avant clôture préparatoire → 422.
- [x] Synthèse après clôture → 200 PDF.

### C.5 Note FE

Deux URLs, quand les boutons apparaissent, 422 métier.

**Done quand :** 2 PDF + tests + journal FE §5.5.

---

## Lot D — Art. 73–75 (ch. 5 CCN)

**Hors module notation.** Changement de **classe** / d’emploi, pas d’échelon (art. 60).  
Préfixe : **`/api/carriere/reclassements`**.  
Ne pas brancher sur `CommissionAvancementService::decider`.

### D.1 Trois procédures

| Art. | Type | Conditions (à valider en Service) | Effet paie |
|------|------|-----------------------------------|------------|
| **73** | `reclassement_formation` | Formation autorisée + diplôme **au dossier** (`diplome_id` + `informations_professionnelles`) rattaché à une classe grille | Classe **supérieure** ; échelon = `echelon_depart` (**D11**) |
| **74a** | `reclassement_exceptionnel` | Âge ≥ 50 **et** ancienneté ≥ 15 ans **et** 3 ans dans la même classe | Classe supérieure (pas Hors classe) |
| **74b** | `hors_classe` | Ancienneté ≥ 25 ans **et** grade Inspecteur principal **et** 8ᵉ échelon | Classe grille **Classe X / Hors Classe** (coeff. 170, déjà seedée) — **422** si pas de ligne de salaire |
| **75** | `reconversion` | Motif ∈ `baisse_activite` \| `reorganisation` \| `maladie` (certificat si maladie) | Autre `fonction` ; classe optionnelle |

### D.2 Décisions figées (2026-09-15)

| # | Sujet | Décision |
|---|--------|----------|
| D11 | Échelon après 73/74a/74b | `parametregrilles.echelon_depart` (1) — comme l’intégration. Pas de conservation du n° d’échelon. |
| D12 | Hors classe (74b) | Vraie classe de grille « Hors Classe » (catégorie Classe X, grade Hors Classe). **Pas** de flag `agents.hors_classe`. |
| D13 | Circuit | RH crée (`soumis`). **73** : RH `approuver`. **74/75** : DG `approuver`. RH seule `appliquer` (paie). |
| D14 | Permission | **Pas** de `gerer-reclassements`. Écrire / appliquer : `gerer-salaires`. Lire / approuver : `consulter-salaires`. DG reçoit `consulter-salaires` (seeder). **Pas** `valider-evaluations`. |

### D.3 Données

Table `reclassements` :

- `agent_id`, `type` (enum ci-dessus), `statut` (`soumis` \| `approuve` \| `rejete` \| `applique` \| `annule`)
- `classe_cible_id` (nullable pour reconversion pure emploi)
- `diplome_id`, `motif`, `piece` (path justificatif, même pattern GED agent)
- `created_by`, `valide_par`, `applique_at`
- Dates / âge / ancienneté **calculés au submit**, stockés pour audit (`age_ans`, `anciennete_ans`, `annees_dans_classe`)

`TypeChangementSalaireAgent` : ajouter `RECLASSEMENT`, `HORS_CLASSE`, `RECONVERSION`.

`SalaireAgentService::changerClasse(int $agentId, int $classeCibleId, int $echelon, TypeChangementSalaireAgent $type, ?string $motif)` — **nouvelle** méthode. Ne pas réutiliser `avancerEchelons`.

### D.4 API minimale

| Méthode | URI | Permission | Description |
|---------|-----|------------|-------------|
| `GET` | `/carriere/reclassements` | `consulter-salaires` | Liste (filtres `type`, `statut`, `agent_id`) |
| `POST` | `/carriere/reclassements` | `gerer-salaires` | Créer (`soumis`) |
| `GET` | `/carriere/reclassements/{id}` | `consulter-salaires` | Détail + `eligibilite` |
| `POST` | `…/{id}/approuver` · `…/{id}/rejeter` | `consulter-salaires` | 73 = RH ; 74/75 = DG (**403** sinon) |
| `POST` | `…/{id}/appliquer` | `gerer-salaires` | Idempotent : paie + `categorie_id` / `grade_id` / `fonction_id` |
| `GET` | `/carriere/agents/{id}/reclassements` | `consulter-salaires` | Historique |

422 explicites si conditions art. 73–74 non réunies (ne pas laisser le FE « tenter »).

### D.5 Tests Feature

- [x] 73 sans diplôme → 422.
- [x] 74a âge 49 → 422 ; 50 + 15 ans + 3 ans classe → 201.
- [x] 74b inspecteur principal + échelon 8 + 25 ans → OK ; sinon 422.
- [x] 75 maladie sans justificatif → 422.
- [x] `appliquer` deux fois → 2ᵉ appel no-op / message idempotent.
- [x] `appliquer` ne change **pas** seulement l’échelon dans la même classe.

### D.6 Note FE

Nouveau § carrière. Hors écrans `/avancements`. Lien depuis la fiche agent (pas depuis la commission).

**Done :** 4 types + `changerClasse` + tests `ReclassementTest` + journal FE. Hors lot : catalogue formations, concours, PDF acte.

---

## 4. Architecture cible

```
Lot A–C  /api/avancements
  SessionEvaluationService  → SuperieurHierarchiqueService (période)
  EvaluationStatutService   → inscrit_tableau à la validation RH
  CommissionAvancementService → refuse decider si non inscrit
  EvaluationPdfService      → DomPDF

Lot D    /api/carriere/reclassements
  ReclassementController → ReclassementService → ReclassementInterface
  ReclassementService    → SalaireAgentService::changerClasse
                         → AgentInterface (catégorie / grade)
```

---

## 5. Compatibilité frontend

- Zéro rename d’URI existante.
- Champs nouveaux **optionnels** : `affectation_notation`, `inscrit_tableau`.
- `GET …/tableau` et PDF = **nouveaux** endpoints.
- Lot D = **nouveau** préfixe.

---

## 6. Journal

| Date | Lot | Fait |
|------|-----|------|
| 2026-09-15 | — | Lots A–D **close**. Suite projet : D.2 Discipline. |
| 2026-09-15 | D | API `/carriere/reclassements` (art. 73–75). D11–D14 figés. `changerClasse`. Tests `ReclassementTest`. |
| 2026-09-14 | A–C | API livrée : notateur art. 62, `inscrit_tableau`, PDF fiche + synthèse. Tests `EvaluationComplementsTest`. |
| 2026-09-14 | — | Branche `feature/evaluation-complements` + création de ce plan. D5 proposé : inscription par défaut à `finalisee`, filtre RH avant commission d’avancement. |
