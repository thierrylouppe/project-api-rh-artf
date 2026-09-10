# Plan — Module Évaluation, notation et avancement

> Branche : `feature/evaluation-notation-avancement`  
> Date : 2026-09-09  
> Document **vivant** : cocher les cases à chaque livraison.  
> Métier logiciel (grille /20, statuts) : [`REFERENTIELS-EVALUATION-NOTATION.md`](./REFERENTIELS-EVALUATION-NOTATION.md)  
> **Droit ARTF (prioritaire)** : [`convention-artf-avancement.md`](./convention-artf-avancement.md) — CCN ch. 4–5, art. 60–75  
> Contrat FE : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md) (§ à créer à la 1re livraison)  
> Permissions déjà seedées : `consulter-evaluations`, `creer-evaluations`, `valider-evaluations`

**Objectif :** livrer le **tableau d’avancement ARTF** (notation → préparatoire → commission), par lots, sans casser le FE.  
La CCN **prime** sur le référentiel d’un autre dépôt (éligibilité, exemptions, commissions, +2 échelons).

---

## 1. Cadre projet (non négociable)

Chaîne : Route → FormRequest → Controller → **Service** → **Interface** → Repository → Model.

- Controller : Services uniquement.
- Service : Interfaces uniquement. Métier (éligibilité, N+1, notes, statuts) **ici**.
- Repository : Eloquent uniquement.
- Bindings `Interface → Repository` dans `AppServiceProvider::register()`.
- Auth : `auth:sanctum` + `permission:` (pas de Policies Laravel).
- Préfixe API : **`/api/avancements`**.
- Extension, pas rupture : pas de rename de routes existantes.
- Notifications : canal `database` via `NotificationService` (comme congés). Mail / SMS hors scope.

**Adaptations par rapport au dépôt source du référentiel :**

| Sujet | Ici |
|-------|-----|
| Date d’ancienneté | `agents.date_prise_service` (pas `recrutement.date_recrutement`) |
| N+1 | Affectation **active** + nomination active (même logique que les congés) |
| Mentions | **Une seule grille /20** (§ 4.4 du référentiel). Pas de second barème « scolaire » |
| Avancement paie | Art. 60 = **échelon dans la même classe** → `avancerEchelon` en **phase 4** (après commission, art. 70). ⚠️ La méthode existante avance de **+1** et plafonne à `echelon_fin` : prévoir un `avancerEchelons(int $nombre)` pour art. 71/72 (+2) |
| Notateurs | Déjà modélisés : `nominations.poste` ∈ `Directeur Général`, `Directeur Central`, `Directeur Départemental`, `Chef de Service`, `Chef de Bureau`. **Ne pas créer** un nouveau référentiel de niveaux |
| Service rattaché DG | Détecter par la **direction du service** (fonction `Chef de service rattaché` déjà seedée), jamais `direction_id = 1` en dur |
| Éligibilité | Art. 62–65 (24 mois, en activité, affecté, exemptions) **avant** les filtres parité/semestre du référentiel logiciel |
| DG évalué ? | **Non** — décision RH du 2026-09-10. Le DG **note** et **préside** les commissions, il n’a pas de fiche |

---

## 2. Vue d’ensemble des phases

Ne pas commencer la phase N+1 tant que la phase N n’est pas **testée + notée FE**.

```
Phase 1  Grille + session + fiches + notation /20
    → Phase 2  Signatures + réclamation (art. 63–65) + RH
        → Phase 3  Chaîne des notateurs (art. 64)
            → Phase 4  Préparatoire + commission + note d’avancement (art. 66–70)
                → Phase 5  Art. 71–72, cloche, PDF
```

| Phase | Livrable FE | Statut |
|-------|-------------|--------|
| 1 | Session, mes fiches N+1, saisie notes, total /20 | ⬜ |
| 2 | Avis + signatures notateurs **et** agent (art. 63), **réclamation** (art. 65), validation DRHL | ⬜ |
| 3 | Chaîne art. 64 : bureau → service → dir. dép. / central → DG | ⬜ |
| 4 | Préparatoire + synthèse (art. 66–68), commission + note d’avancement (art. 69–70), `avancerEchelon` | ⬜ |
| 5 | Avancement auto stage 9 mois (art. 71), exceptionnel +2 max (art. 72), cloche, PDF | ⬜ |

**Hors module V1 (ch. 5 CCN) :** reclassement de classe, hors classe, reconversion (art. 73–75).  
Hors scope aussi : concours, reporting dashboard, mail.

---

## 3. Permissions (menus)

Déjà dans `PermissionSeeder` / `RoleSeeder`. **Ne pas inventer de nouvelles permissions** tant que le FE n’en a pas besoin.

| Permission | Usage prévu |
|------------|-------------|
| `consulter-evaluations` | Listes, détail, stats, questions (lecture) |
| `creer-evaluations` | Créer / clôturer / annuler une session, CRUD questions, réattribuer N+1 |
| `valider-evaluations` | Avis hiérarchiques, validation RH, commissions (selon le **rôle** en plus : N+1, `rh`, `directeur-general`) |

Comme les congés : la permission **ouvre la route** ; le métier (N+1 réel, rôle `rh`, etc.) décide qui signe. Sinon **403**.

Rôles seeder actuel : `rh` a `consulter` seulement ; hiérarchie (`directeur`, chefs, DG) a `consulter` + `valider` ; `admin` tout. À **réviser en phase 1** : le `rh` doit pouvoir `creer-evaluations` (sessions). Noter l’écart dans la note FE.

---

## 4. Phase 1 — Socle notation (premier lot FE)

**Pourquoi en premier :** sans session + grille + notes, rien d’autre n’a de sens.

### 4.1 Données

- [ ] Tables : `session_evaluations`, `question_evaluations`, `evaluations`, `note_evaluations` (unicité `evaluation_id` + `question_id`)
- [ ] Enums : statut session (`ouverte` \| `cloturee` \| `annulee`), statut fiche (§ 6 référentiel — stocker dès maintenant, transitions limitées en P1)
- [ ] Seeder **24 questions** (10 + 3 + 7 = 20), `QuestionEvaluationSeeder`
- [ ] Une seule session `ouverte` à la fois

### 4.2 Métier

- [ ] Éligibilité **CCN** : en **activité** (`statut = actif`), **affecté** à un poste (affectation active), **pas** stage / détachement / position exceptionnelle (art. 62, 65)
- [ ] **Hors DG** (décision RH 2026-09-10) : exclure l’agent porteur d’une nomination active `poste = Directeur Général`
- [ ] Rythme : **tous les 24 mois** (art. 62). Les filtres parité année / semestre du référentiel = **mécanique de cohorte** optionnelle, à valider (D8) : ils doivent servir le 24 mois, pas le remplacer.
- [ ] `date_prise_service` (ou date de dernière notation) pour dater le cycle
- [ ] Mutation en cours d’année (art. 62) : notateur = N+1 du poste où l’agent a **servi le plus longtemps** sur la période (pas seulement l’affectation active du jour)
- [ ] N+1 identifiable ; liste « sans supérieur » si affecté mais chef introuvable
- [ ] Création de session → génération auto des fiches (éligibles **avec** N+1)
- [ ] Liste « sans supérieur » (éligibles sans fiche) — lecture RH
- [ ] Notation : upsert notes, `note_obtenue` ≤ `bareme_max`, au moins une note
- [ ] `NoteCalculationService` : `note_globale` = somme, complétude = toutes questions `actif`
- [ ] Statuts auto : `en_attente` → `en_cours` → `notee` (`EvaluationStatutService` **seule** porte)
- [ ] Mentions dérivées /20 (Excellent ≥ 16, …) — champ calculé, pas une 2ᵉ grille
- [ ] Réattribution N+1 tant que session ouverte

### 4.3 API minimale

Préfixe `/api/avancements`.

| Acteur | Endpoint (indicatif) |
|--------|----------------------|
| RH | CRUD `sessions`, clôturer **interdit** tant que P4 (ou 422 « commissions manquantes ») |
| RH | CRUD `questions-evaluation` |
| RH | `GET sessions/{id}/sans-superieur` |
| N+1 | `GET evaluations/superieur/mes-evaluations` |
| N+1 | `POST evaluations/superieur/{id}/noter-agent` |
| Agent | `GET evaluations/agent/mes-evaluations` (lecture seule en P1) |

### 4.4 Services

`SessionEvaluationService` · `NoteCalculationService` · `EvaluationStatutService`  
N+1 : extraire / réutiliser un `SuperieurHierarchiqueService` (affectation active + nomination). L’agent n’est jamais son propre évaluateur.

### 4.5 Tests + FE

- [ ] Feature : unicité session ouverte, éligibilité, génération fiches, barème, complétude, mentions
- [ ] Note FE : § évaluations (statuts, payload notes, `note_globale`)
- [ ] Ajuster seeder rôles : `rh` + `creer-evaluations` si confirmé

**Hors P1 :** avis, signatures, RH, commissions, PDF.

---

## 5. Phase 2 — Circuit N+1, agent, DRHL

**Prérequis :** fiche `notee` possible.

- [x] Avis N+1 : `avis_superieur` 10–2000, signature évaluateur → statut `signee_evaluateur` (notation complète obligatoire) — endpoint `avis-et-signer`
- [x] Signature agent **obligatoire** (art. 63) : prise de connaissance → `signee_evalue` (refusée si N+1 non signé)
- [x] **Réclamation** (art. 65) : endpoint `reclamer`, table `reclamations`, `ReclamationService::traiter()`
- [x] Validation RH : endpoint `envoyer-rh` depuis `signee_evalue`. En P3 on ajoutera les prérequis avis hiérarchiques.
- [x] RH conforme → `finalisee` ; non conforme + motif → `rejetee`
- [x] Endpoints : `avis-et-signer`, `reclamer`, `envoyer-rh`, `valider-rh`, `/reclamations/{id}/traiter`
- [x] Tests Feature workflow complet P1+P2 — **20 tests, 111 assertions ✅**
- [x] Note FE : `prochaine_etape` sur chaque fiche + tableau boutons/rôles dans §7b

---

## 6. Phase 3 — Avis hiérarchiques

- [ ] Table `avis_hierarchiques` — un avis par niveau et par évaluation
- [ ] Niveaux CCN art. 64 : `chef_bureau` → `chef_service` → `directeur_departemental` / `directeur_central` → `directeur_general`  
  Mapper sur nos structures (Bureau, Service, Direction). Distinguer directeur **départemental** vs **central** (D4).
- [ ] Séquentialité : N seulement si N−1 signé ; signature définitive
- [ ] Variante « service rattaché à la DG » : sauter `directeur` — **règle projet à figer** (nom / flag sur `Direction`, pas `id = 1`)
- [ ] Transmission RH (P2) : exiger tous les avis **requis** signés
- [ ] Tests : ordre, skip DG, avis non signé non modifiable après signature
- [ ] Note FE : qui peut poster quel niveau (`user.agent_id` vs nomination)

---

## 7. Phase 4 — Commissions, clôture, avancement

- [ ] Tables : `commissions_preparatoires` (+ commentaires / débat), `note_syntheses` (art. 67), `commissions_avancements`
- [ ] Préparatoire **présidée DG** (art. 68) : cohérence appréciations/notes, motivations des notateurs, **note de synthèse** — pas un simple recalcul « écart > 5 » (règle logiciel, à garder en alerte UI seulement)
- [ ] Commission d’avancement présidée DG (art. 69) : **fixe la note à l’avancement** (art. 70) — champ distinct de `note_globale` N+1
- [ ] Décision d’avancement (échelon, **même classe**) : 0, 1, … selon la session ; **pas** de reclassement de classe ici
- [ ] Seuil /20 du référentiel (≥ 12) : **indicatif logiciel**, à confirmer — la CCN dit que la note d’avancement est **déterminée en commission** (art. 70)
- [ ] Clôture session si notation close **et** les deux commissions `cloturee`
- [ ] Lien paie : après décision de la commission, `avancerEchelon` (bouton RH, D6) — idempotent
- [ ] Tests + note FE commissions

---

## 8. Phase 5 — Satellites

À faire **après** qu’un cycle P1–P4 tourne avec le FE.

| Sous-lot | Contenu | Priorité |
|----------|---------|----------|
| 5.1 Art. 71 | +2 échelons après stage ≥ 9 mois (certificat / attestation) — **hors** cycle 24 mois | Haute métier, après P4 |
| 5.2 Art. 72 | Avancement exceptionnel, proposition DG, **plafond +2 échelons** | Haute métier, après P4 |
| 5.3 Connaissances complémentaires | Demandes de formation liées à la fiche | Basse |
| 5.4 Notifications | `domaine: evaluation` (session créée, à noter, à signer, RH, commission) | Haute dès P2 si le FE a la cloche |
| 5.5 PDF | Fiche d’évaluation, note de synthèse | Moyenne |
| 5.6 Stats session | Compteurs par statut, moyenne /20, répartition mentions | Haute pour écran RH |

---

## 9. Ordre de création dans le code (chaque agrégat)

```
Migration → Model → Enum → Interface → Repository → binding
→ Service → FormRequests → Resource → Controller → routes (section Avancements dans api.php)
→ Seeder → tests Feature → journal note-fe
```

Ne pas mettre le calcul de `note_globale` dans le Controller.

---

## 10. Décisions à trancher (avant ou pendant la phase)

| # | Sujet | Phase | Proposition |
|---|--------|-------|-------------|
| D1 | `rh` a-t-il `creer-evaluations` ? | 1 | **Oui** (sessions = métier DRHL) |
| D2 | Date du cycle 24 mois | 1 | `date_prise_service` puis date de **dernière notation** |
| D3 | Transmission RH sans tous les notateurs art. 64 | 2 vs 3 | P2 : N+1 + agent ; P3 : chaîne complète |
| D4 | Directeur départemental vs central | 3 | ✅ Utiliser `nominations.poste` (déjà distingué) |
| D5 | Qui inscrit au tableau d’avancement | 4 | Tous les notés `finalisee` vs filtre RH |
| D6 | Passage d’échelon | 4 | **Bouton RH** après art. 70 (pas d’auto sur la note N+1) |
| D7 | Statuts agent « détachement » / « position exceptionnelle » | 1 | À créer : `statut` agent ne connaît que `actif`, `inactif`, `stagiaire`, `archive`, `BROUILLON` |
| D8 | Parité année + semestre du référentiel | 1 | **Optionnel** ; le légal est 24 mois + affecté |
| D9 | Le DG est-il noté ? | 1 | ✅ **Non** (RH, 2026-09-10) — il note et préside |
| D10 | Bonification de +2 échelons (art. 71/72) | 5 | Étendre `SalaireAgentService` : `avancerEchelons(int)` idempotent, plafonné `echelon_fin` |

---

## 11. Journal

| Date | Phase | Fait |
|------|-------|------|
| 2026-09-10 | — | D9 tranché : **DG non évalué**. D4 résolu via `nominations.poste`. D10 ouvert (+2 échelons) |
| 2026-09-10 | — | Alignement CCN ARTF art. 60–75 ([`convention-artf-avancement.md`](./convention-artf-avancement.md)) |
| 2026-09-09 | — | Création de ce plan + branche `feature/evaluation-notation-avancement` |
