# Référentiels — Évaluation et notation

Document de reproduction du module d’évaluation des agents. Il décrit les **règles métier**, les **barèmes**, les **statuts** et le **workflow** tels qu’implémentés dans Gestion RH API, pour les transposer à l’identique dans un autre projet.

**Contexte :** Administration publique (ARFT / DGMRF / ANIF)  
**Périmètre :** sessions, fiches d’évaluation, notation /20, avis hiérarchiques, signature agent, validation RH, commissions  
**Source :** code applicatif (`Evaluation`, `QuestionEvaluation`, `NoteCalculationService`, `EvaluationStatutService`, `SessionEvaluationService`)

**Architecture cible :** Controller → Service → Repository → Model (Laravel). Routes sous `/api/avancements/`, protégées par authentification.

---

## Légende

| Symbole | Signification |
|---------|---------------|
| ✅ | Règle obligatoire à reproduire |
| ⚙️ | Règle calculée / dérivée (pas de table dédiée) |
| 📎 | Table / enum à créer |

---

## 1. Objectif du module ✅

Évaluer périodiquement les agents (hors Directeur Général) sur **20 points**, via le supérieur hiérarchique immédiat (N+1), puis faire circuler la fiche dans la chaîne hiérarchique, la DRHL et les commissions d’avancement.

**Chaîne complète :**

1. Création d’une **session**
2. **Attribution automatique** des agents éligibles à leur N+1
3. Génération des **fiches** (une par agent)
4. **Notation** (3 blocs → total /20)
5. **Avis + signature** du N+1
6. **Signature** de l’agent (prise de connaissance)
7. **Avis hiérarchiques** séquentiels
8. **Validation RH** (DRHL)
9. **Commission préparatoire** (harmonisation des notes)
10. **Note de synthèse**
11. **Commission d’avancement** (décision)

---

## 2. Modèle de données 📎

### 2.1 Tables à créer

| Table | Rôle |
|-------|------|
| `session_evaluations` | Campagne / période d’évaluation |
| `question_evaluations` | Référentiel des critères et barèmes |
| `evaluations` | Fiche d’un agent dans une session |
| `note_evaluations` | Note par question (unicité `evaluation_id` + `question_id`) |
| `avis_hierarchiques` | Avis Chef de service / Directeur / DG |
| `reclamations` | Contestation de l’agent |
| `connaissances_complementaires` | Besoins de formation liés à la fiche |

Tables satellites (même module, hors notation stricte) : `commissions_preparatoires`, `commission_preparatoire_notes`, `commission_preparatoire_commentaires`, `commissions_avancements`, `note_syntheses`.

### 2.2 Champs essentiels — session

| Champ | Type / valeurs | Règle |
|-------|----------------|-------|
| `debut_session` | date, obligatoire | Date de campagne |
| `fin_session` | date, nullable | Renseignée à la clôture |
| `statut` | `ouverte` \| `cloturee` \| `annulee` | Nouvelle session = `ouverte` |
| `type_annee` | `paire` \| `impaire` | Filtre d’éligibilité |
| `semestre` | `1` \| `2` | Filtre d’éligibilité |
| `description` | texte, max 500 | Optionnel |
| `created_by` / `closed_by` / `closed_at` | traçabilité | Automatique |

**Contrainte :** une seule session `ouverte` à la fois. Refuser toute nouvelle session tant qu’une session ouverte existe.

`fin_session` ne peut pas précéder `debut_session`.

### 2.3 Champs essentiels — évaluation

| Champ | Règle |
|-------|--------|
| `agent_id` | Agent évalué |
| `session_id` | Session parente |
| `superieur_hierarchique_id` | N+1 notateur |
| `date_evaluation` | Date de création / notation |
| `jours_absence_non_justifiee` | Entier ≥ 0, défaut 0 |
| `sanctions` | Texte libre, max 1000 (constat du N+1) |
| `note_globale` | Somme des notes, 2 décimales, /20 |
| `avis_superieur` | Texte, min 10 / max 2000 à la validation N+1 |
| `signe_par_evaluateur` | Booléen, signature N+1 |
| `signe_par_evalue` | Booléen, signature agent |
| `statut` | Voir § 6 |
| Champs RH | `validee_par_rh`, `date_validation_rh`, `validateur_rh_id`, `commentaire_rh`, `conforme_rh` |
| Commission | `commission_note`, `commission_decision` |

**Unicité métier :** un agent n’apparaît qu’une fois par session.

---

## 3. Référentiel des questions (grille /20) ✅

**Table :** `question_evaluations`  
**Seeder de référence :** `QuestionEvaluationSeeder`

Trois types de critères **fixes** (`type_critere`) :

| Code | Libellé | Total barème |
|------|---------|--------------|
| `competence_pro` | Compétences professionnelles | **10** |
| `assiduite` | Assiduité | **3** |
| `relation_sociale` | Relations sociales | **7** |
| | **Total** | **20** |

Chaque question a : `libelle`, `bareme_max` (0–20, décimal), `ordre`, `actif` (booléen).

Seules les questions `actif = true` sont notées et comptent dans la **complétude**.

### 3.1 Compétences professionnelles (/10)

| # | Libellé | Barème max |
|---|---------|------------|
| 1 | Connaissance technique | 1 |
| 2 | Capacité à anticiper et programmer le travail | 1 |
| 3 | Autonomie et sens de responsabilité | 1 |
| 4 | Capacité à déléguer | 0,5 |
| 5 | Qualité rédactionnelle | 1 |
| 6 | Prise d’initiatives | 1 |
| 7 | Fiabilité et qualité d’exécution des tâches | 1 |
| 8 | Respect des délais et sens de l’organisation | 1 |
| 9 | Rigueur et respect des procédures | 1 |
| 10 | Capacité à partager l’information | 0,5 |
| 11 | Curiosité professionnelle | 0,5 |
| 12 | Capacité à identifier et hiérarchiser les priorités | 0,5 |

### 3.2 Assiduité (/3)

| # | Libellé | Barème max |
|---|---------|------------|
| 13 | Ponctualité | 1 |
| 14 | Disponibilité | 1 |
| 15 | Serviabilité | 1 |

### 3.3 Relations sociales (/7)

| # | Libellé | Barème max |
|---|---------|------------|
| 16 | Capacité à animer et motiver l’équipe | 1 |
| 17 | Adaptabilité | 0,5 |
| 18 | Communication | 0,5 |
| 19 | Rapport avec la hiérarchie | 1 |
| 20 | Rapport avec les collègues | 1 |
| 21 | Qualité de l’accueil | 0,5 |
| 22 | Faculté d’écoute et de réponse | 0,5 |
| 23 | Capacité à travailler en équipe | 0,5 |
| 24 | Respect du code vestimentaire | 1,5 |

**Règle de reproduction :** recopier ces 24 lignes telles quelles. Le CRUD questions permet d’ajouter / désactiver / réordonner, mais le barème total visé reste **20**.

---

## 4. Règles de notation ✅

### 4.1 Saisie

- Notateur = `superieur_hierarchique_id` de la fiche.
- Payload : tableau `notes[]` avec `question_id`, `note_obtenue`, `commentaire` optionnel.
- Au moins **une** note à chaque enregistrement (saisie progressive autorisée).
- `note_obtenue` : nombre ≥ 0, **≤ `bareme_max` de la question** (tolérance float 0,0001).
- Commentaire : max **500** caractères.
- Champs annexes optionnels : `jours_absence_non_justifiee`, `sanctions`.
- Upsert sur (`evaluation_id`, `question_id`) : on peut corriger une note tant que le statut le permet.

### 4.2 Calcul de la note globale ⚙️

```
note_globale = somme(note_obtenue) des notes non nulles
```

Pas de pondération supplémentaire. Les barèmes des questions **sont** la pondération.

Statistiques par bloc : somme, moyenne, max possible, pourcentage = `somme / somme(bareme_max)`.

### 4.3 Complétude

La notation est **complète** lorsque **chaque question active** a une `note_obtenue` non nulle.

Sans complétude 100 % :

- le statut reste `en_cours` (après la première note) ;
- la validation N+1 est **refusée**.

### 4.4 Mentions / appréciations ⚙️

**Recommandé : une seule grille, sur /20** (celle des rapports et de la session). C’est la note stockée, le seuil d’avancement (≥ 12) et le langage RH.

| Mention | Note /20 | Équivalent % | Usage métier |
|---------|----------|--------------|--------------|
| Excellent | ≥ 16 | ≥ 80 % | Très bon dossier |
| Très bien | 14 – 15,99 | 70 – 79 % | |
| Bien | 12 – 13,99 | 60 – 69 % | Seuil d’avancement (≥ 12) |
| Moyen | 10 – 11,99 | 50 – 59 % | |
| Insuffisant | < 10 | < 50 % | Agent en difficulté |

Ne pas utiliser en parallèle l’ancienne grille « scolaire » du code (`getAppreciation` : Excellent à 90 % = 18/20, « Assez bien » / « Passable »). Elle contredit les stats (16/20 y est Excellent, pas Très bien). Dans le projet cible : **supprimer cette seconde grille**.

---

---

## 5. Éligibilité des agents à une session ✅

Un agent est éligible **si et seulement si** toutes les conditions suivantes sont vraies.

| # | Règle | Détail |
|---|--------|--------|
| 1 | Pas Directeur Général | Nomination active dont la fonction a l’intitulé « Directeur Général » |
| 2 | Date de recrutement connue | `agent.recrutement.date_recrutement` obligatoire |
| 3 | Ancienneté ≥ **2 ans** | `année(debut_session) − année(recrutement) ≥ 2` |
| 4 | Parité d’année | Année de recrutement paire ↔ session `type_annee = paire` (idem impair) |
| 5 | Semestre | Mois 1–6 → semestre `1` ; mois 7–12 → semestre `2`. Doit égaler `session.semestre` |
| 6 | N+1 identifiable | Affectation active + nomination de chef (voir § 8). Sinon : agent listé « sans supérieur », **pas de fiche créée** |

Conséquence : une cohorte n’est évaluée **qu’une année sur deux**, au semestre correspondant à son mois d’embauche.

À la création de session : génération automatique des fiches pour tous les éligibles ayant un N+1.

---

## 6. Statuts de la fiche d’évaluation ✅

| Code | Libellé | Description |
|------|---------|-------------|
| `en_attente` | En attente de notation | Fiche créée, N+1 n’a pas commencé |
| `en_cours` | Notation en cours | Au moins une note, grille incomplète |
| `notee` | Notée | 100 % des questions actives notées |
| `validee` | Validée par le supérieur | N+1 a émis son avis et signé |
| `signee` | Signée par l’agent | Prise de connaissance |
| `en_validation_rh` | En validation RH | Tous avis signés, dossier transmis à la DRHL |
| `finalisee` | Finalisée | RH conforme — transmissible aux commissions |
| `rejetee` | Rejetée | Non-conformité RH ou réclamation / anomalie |
| `annulee` | Annulée | Hors processus d’avancement |

**Couleurs UI :** `en_attente` warning · `en_cours` / `en_validation_rh` info · `notee` primary · `validee` / `signee` / `finalisee` success · `rejetee` danger · `annulee` secondary.

### 6.1 Transitions (machine à états)

```
en_attente → en_cours → notee → validee → signee → en_validation_rh → finalisee
                 ↘           ↘        ↘        ↘
                    rejetee / annulee (selon étape)
```

| De | Vers | Déclencheur | Auto |
|----|------|-------------|------|
| `en_attente` | `en_cours` | Première note saisie | Oui |
| `en_cours` | `notee` | Complétude 100 % | Oui |
| `en_cours` **ou** `notee` | `validee` | N+1 : avis + signature évaluateur | Non (action) |
| `validee` | `signee` | Agent signe **et** `signe_par_evaluateur = true` | Non (action) |
| `signee` | `en_validation_rh` | `signe_par_evalue` **et** tous avis hiérarchiques signés | Non (action) |
| `en_validation_rh` | `finalisee` | DRHL : conforme | Non (action) |
| `en_validation_rh` | `rejetee` | DRHL : non conforme + motif | Non (action) |

Validation N+1 **refusée** si notation incomplète.

Signature agent **refusée** si le N+1 n’a pas signé.

Transmission RH **refusée** si un avis hiérarchique requis n’est pas signé.

---

## 7. Validation N+1 et signatures ✅

### 7.1 Avis du supérieur notateur

- Champ `avis_superieur` : obligatoire, **10 à 2000** caractères.
- `approuve` : booléen optionnel.
- `observations` : max 1000.
- Pose `signe_par_evaluateur = true` et passe en `validee`.

### 7.2 Signature de l’agent

- Confirme la prise de connaissance (pas un accord sur la note).
- Pose `signe_par_evalue = true`, statut `signee`.
- L’agent peut ensuite déposer une **réclamation**.

### 7.3 Réclamation 📎

| Champ | Valeurs |
|-------|---------|
| `motif` | Texte libre |
| `statut` | `en_attente` \| `acceptee` \| `rejetee` |
| `traite_par` | Traçabilité du traitement |

---

## 8. Attribution N+1 (hiérarchie) ✅

Le notateur est le **supérieur immédiat** déduit de l’**affectation active** et des **nominations actives**. L’agent n’est jamais son propre évaluateur.

| Affectation de l’évalué | N+1 recherché | Fallback |
|-------------------------|---------------|----------|
| Bureau | Chef de bureau | Chef de service → Directeur |
| Service | Chef de service | Directeur |
| Direction | Directeur de la direction | — |

Le chef est l’agent nominé (`is_active`) sur la structure. Si le nominé est l’agent lui-même, on ignore et on remonte.

**Réattribution :** possible tant que la session est ouverte (changement de N+1 sur une fiche).

---

## 9. Avis hiérarchiques (après le N+1) ✅

Complément de l’avis du notateur. **Un avis par niveau et par évaluation.** Un avis n’est modifiable / supprimable **que non signé**. La signature est définitive et horodatée.

### 9.1 Niveaux et ordre

| Code | Libellé | Ordre standard |
|------|---------|----------------|
| `chef_bureau` | Chef de Bureau | 1 |
| `chef_service` | Chef de Service | 2 |
| `directeur` | Directeur | 3 |
| `directeur_general` | Directeur Général | 4 |

**Séquentialité :** le niveau N ne peut déposer un avis que si le niveau N−1 est **signé**. Le premier niveau est toujours autorisé.

### 9.2 Variante « service rattaché à la DG »

Si le service de l’évalué a `direction_id = 1` (DG) :

```
Chef de bureau (1) → Chef de service (2) → Directeur Général (3)
```

Le niveau `directeur` est **sauté**.

Sinon chaîne complète à 4 niveaux.

### 9.3 Contenu d’un avis

| Champ | Règle |
|-------|--------|
| `avis` | Texte |
| `approuve` | true = favorable, false = défavorable |
| `signe` / `date_signature` | Signature électronique |
| `ordre` | 1…4 selon le contexte |
| `observations` | Optionnel |

---

## 10. Validation RH (DRHL) ✅

Contrôle **administratif** (conformité du dossier), pas une re-notation.

Prérequis : statut `signee`, signature agent, **tous les avis requis signés**.

| Action | Effet |
|--------|--------|
| Valider | `conforme_rh = true`, `validee_par_rh = true`, statut `finalisee` |
| Rejeter | `conforme_rh = false`, statut `rejetee`, `motif` / `commentaire_rh` |

---

## 11. Connaissances complémentaires ✅

Demandes de formation liées à la fiche (agent).

| Champ | Valeurs |
|-------|---------|
| `type_formation` | `formation` \| `certification` \| `competence` \| `experience` |
| `nom_formation` | Obligatoire, max 255 |
| `niveau_souhaite` | `debutant` \| `intermediaire` \| `avance` \| `expert` |
| `statut_demande` | `en_attente` → `approuvee` \| `refusee` → `en_cours` → `terminee` |

Workflow RH : approuver / refuser / mettre en cours / terminer (dates, organisme, note de formation, certificat).

---

## 12. Session : clôture et statistiques ✅

**Clôture autorisée seulement si :**

1. toutes les fiches ont une `note_globale` non nulle ;
2. commission préparatoire **et** commission d’avancement existent et sont `cloturee`.

À la clôture : `statut = cloturee`, `fin_session = now()`, `closed_by` / `closed_at`.

Statuts session : `ouverte` | `cloturee` | `annulee`.

---

## 13. Commissions (règles minimales à conserver) ✅

**Commission préparatoire :** harmonise les notes N+1 (`commission_note` peut différer de `note_globale`). Priorité de traitement renforcée si `|commission_note − note_globale| > 5`.

**Commission d’avancement :** décision finale (dans ce projet : `Favorable` / `Defavorable` sur la fiche ; documentation métier : avancement accordé / différé / maintien).

Seuil indicatif documenté ailleurs : **note ≥ 12** → avancement accordé (à confirmer dans le projet cible).

---

## 14. Endpoints minimaux à reproduire

Préfixe : `/api/avancements/`

| Acteur | Méthode | Action |
|--------|---------|--------|
| RH | CRUD `sessions` | Créer (attribution auto), clôturer, annuler, réattribuer, stats |
| N+1 | `GET .../evaluations/superieur/mes-evaluations` | Liste |
| N+1 | `POST .../evaluations/superieur/{id}/noter-agent` | Notation |
| N+1 | `POST .../evaluations/superieur/{id}/avis-et-validation` | Avis + signature |
| Agent | `GET .../evaluations/agent/mes-evaluations` | Liste |
| Agent | `POST .../evaluations/agent/{id}/valider-et-signer` | Signature |
| Agent | `POST .../evaluations/agent/{id}/reclamation` | Réclamation |
| Hiérarchie | `POST .../evaluations/{id}/avis-hierarchique` | Avis + signer |
| DRHL | `POST .../evaluations/rh/{id}/valider` / `rejeter` | Conformité |
| Admin | CRUD `questions-evaluation` | Référentiel critères |

---

## 15. Services à recréer (logique centralisée)

| Service | Responsabilité |
|---------|----------------|
| `NoteCalculationService` | Upsert notes, barèmes, `note_globale`, complétude, stats par critère |
| `EvaluationStatutService` | **Seule** porte d’entrée des transitions de statut |
| `SessionEvaluationService` | Une session ouverte, éligibilité, attribution N+1, clôture |
| `AvisHierarchiqueService` | Séquentialité + variante DG |
| `ValidationRHService` | Conformité DRHL |

Ne pas recalculer la note globale dans le contrôleur : toujours via `NoteCalculationService`.

---

## 16. Checklist de reproduction

- [ ] Tables § 2 + unicité note (`evaluation_id`, `question_id`)
- [ ] Seeder des **24 questions** et totaux 10 / 3 / 7
- [ ] `note_globale` = somme des notes
- [ ] Complétude = toutes questions **actives**
- [ ] Une seule session `ouverte`
- [ ] Éligibilité : hors DG, ≥ 2 ans, parité année, semestre d’embauche, N+1
- [ ] Machine à états § 6.1 sans saut d’étape
- [ ] Avis N+1 min 10 caractères + signature évaluateur avant signature agent
- [ ] Avis hiérarchiques séquentiels + exception service rattaché DG
- [ ] Validation RH après tous avis signés
- [ ] Mentions : **grille unique /20** (§ 4.4) — pas de second barème en %
- [ ] Auth sur toutes les routes `/avancements`

---

## 17. Fichiers sources dans ce dépôt

| Sujet | Fichier |
|-------|---------|
| Grille questions | `database/seeders/QuestionEvaluationSeeder.php` |
| Types de critères | `app/Models/QuestionEvaluation.php` |
| Statuts fiche | `app/Models/Evaluation.php` |
| Calcul notes | `app/Services/NoteCalculationService.php` |
| Transitions | `app/Services/EvaluationStatutService.php` |
| Éligibilité / N+1 | `app/Services/SessionEvaluationService.php` |
| Avis | `app/Models/AvisHierarchique.php`, `docs/avis-hierarchiques.md` |
| Validation RH | `docs/validation-rh.md` |
| Routes | `routes/api.php` (groupe `avancements`) |

Voir aussi : [REFERENTIELS-RH.md](./REFERENTIELS-RH.md) (grades, fonctions, absences liées à `jours_absence_non_justifiee`).  
**Phasage dans ce projet :** [`plan-module-evaluation-notation.md`](./plan-module-evaluation-notation.md).  
**Lots A–C livrés (PDF, mutation art. 62, tableau D5).** Art. 73–75 encore ouvert : [`plan-evaluation-complements.md`](./plan-evaluation-complements.md).  
**Convention collective ARTF (ch. 4–5) :** [`convention-artf-avancement.md`](./convention-artf-avancement.md) — **prime** sur les règles d’éligibilité / commissions de ce document.

---

*Dernière mise à jour : septembre 2026 — aligné sur le code du dépôt Gestion RH API.*
