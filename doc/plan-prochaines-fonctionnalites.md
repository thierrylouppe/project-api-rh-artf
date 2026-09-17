# Prochaines fonctionnalités — suivi d’implémentation

> Document **vivant** : cocher au fur et à mesure.  
> Dernière mise à jour : **2026-09-17** (D.3.5 Santé / AT-MP livré)  
> Architecture obligatoire : [`architecture.md`](./architecture.md)  
> Plan long : [`plan_complet.md`](./plan_complet.md)  
> Contrat FE actuel : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md)  
> Cible métier : [`organigramme-drhl.md`](./organigramme-drhl.md)  
> Droit ARTF (barèmes, délais, éligibilité) : [`convention-collective-artf.md`](./convention-collective-artf.md)  
> Reporting D.6 : [`plan-module-reporting.md`](./plan-module-reporting.md)  
> Prestations D.3.4 : [`plan-module-prestations.md`](./plan-module-prestations.md)  
> Santé D.3.5 : [`plan-module-sante.md`](./plan-module-sante.md)

**Objectif :** livrer les modules métier manquants (vie de l’agent + DRHL), **sans cloisonner** par service / bureau. Le cloisonnement vient **après**.

**État :** le cœur API (entrée → carrière → paie → congés → notation → avancement → reclassement) est **livré**. **D.2 Discipline, D.3 P1 + D.3.4 Prestations + D.3.5 Santé / AT-MP, D.4 Formation, D.5 Paie, D.6 Reporting livrés.** Vague E lots **A–E** livrés.  
Prochain : **Vague F** cloisonnement — cloisonnement **plus tard**.

---

## Déjà livré (ne pas relancer)

| Module | Préfixe | Notes |
|--------|---------|--------|
| Auth, users, rôles | `/login`, `/users`, `/roles` | Permissions Spatie |
| Structure org. | `/localites` … `/bureaux` | |
| Référentiels | `/diplomes`, `/types-conges`, `/types-absences`, … | CRUD listes seulement |
| Intégration | `/integration/…` | Dossier, circuit, acte métier, stage, matériel, PDS |
| Personnel | `/personnel/…` | Annuaires intégrés / stagiaires + fiche vie courante |
| Carrière | `/carriere/…` | Affectations, nominations, lots, contrats, synthèse, **reclassements art. 73–75** |
| Salaires | `/salaires`, `/salaires-agents`, grille | Bulletin PDF, `avancerEchelon` / `avancerEchelons`, `changerClasse` |
| Notifications | `/notifications` | Inbox + événements métier (canal `database`) |
| Congés & absences | `/conges`, `/absences` | Demandes, soldes, circuit par type, PDF |
| Dossier agent | `/personnel/agents/{id}` | Infos, contacts, GED légère, archivage |
| Évaluations & avancements | `/avancements/…` | P1–P5 + lots A–C (art. 62, tableau, PDF) |
| Discipline | `/discipline/…` | Types CCN, rapport N+1, instruire RH, prononcer DG, pièces, PDF, avertissements, historique |
| Paie | `/paie` | Éléments, affectations, lots, bulletin enrichi, export |
| Reporting | `/reporting` | Dashboard, stats, alertes, exports PDF/CSV |

**Hors scope immédiat :** PDF actes d’intégration (Phase 1.B), recrutement amont (concours), GED versionnée, mail / SMS, **Service Logistique**, cloisonnement par bureau.

---

## Principe — fonctionnalités d’abord, cloisonnement ensuite

Décision **2026-09-15** : implémenter **tous** les modules ci-dessous comme des préfixes API **globaux**, consommés par le rôle `rh` (comme aujourd’hui).

| Maintenant | Plus tard (Vague F) |
|---|---|
| Un rôle métier `rh` | Menus / permissions par bureau (Personnel, Solde, Formation, Affaires sociales, Étude) |
| Aucun filtre « ma structure seulement » | Périmètre Direction → Service → Bureau sur les listes |
| Seeders org. inchangés | Éventuellement seeder `B.A.S.` + descriptions de missions |

Les tableaux « Bureau cible » ci-dessous sont de la **doc métier**, pas une contrainte d’implémentation. Ne pas ajouter de `permission:` ou de filtre d’affectation par bureau tant que la Vague F n’est pas ouverte.

---

## Règles à chaque livraison

```
Interface → Repository → binding AppServiceProvider → Service → FormRequests → Resource → Controller → routes (section module)
```

- Controller : Services uniquement. Service : Interfaces uniquement. Métier dans le Service.
- **Extension, pas rupture** : nouveaux préfixes / champs optionnels. Pas de rename de route existante.
- `permission:` bloquant sur une route **déjà** ouverte : uniquement avec seeder + FE dans la même livraison.
- Après livraison : 1 ligne dans le journal (§ ci-dessous) + statut dans `note-fe-etat-implementations.md`.

---

## Vague A — Notifications ✅

| # | Tâche | Statut |
|---|--------|--------|
| A.1 | `NotificationService` (canal `database` ; mail plus tard) | ✅ |
| A.2 | Routes `GET /notifications`, `GET /notifications/non-lues`, `POST /{id}/lu`, `POST /tout-lire` | ✅ |
| A.3 | Brancher intégration : validation / rejet / compte / prise de service | ✅ |
| A.4 | Brancher carrière : affectation unitaire (lots déjà notifiés) | ✅ |
| A.5 | `ConventionStageEnFinDateJob` → vraie notification (plus de TODO log) | ✅ |
| A.6 | Tests Feature smoke + note FE (retirer « ne pas brancher la cloche ») | ✅ |

**Hors Vague A :** SMS, mail.

---

## Vague B — Dossier agent vie courante ✅

| # | Tâche | Statut |
|---|--------|--------|
| B.1 | Infos perso / pro / contacts / situation familiale (si incomplet) | ✅ |
| B.2 | Documents agent hors dossier d’intégration (GED légère) | ✅ |
| B.3 | Soft delete / archivage agent (règles métier) | ✅ |

Lecture carrière (`GET /carriere/agents/{id}`, historiques) : **déjà livré**.

---

## Vague C — Congés & absences ✅

Référentiels `TypeConge` / `TypeAbsence` déjà en place.

| # | Sous-module | Contenu | Statut |
|---|-------------|---------|--------|
| C.1 | Paramétrage | Jours fériés, règles d’acquisition, soldes | ✅ |
| C.2 | Demandes | CRUD + calcul jours ouvrables (week-ends + fériés) | ✅ |
| C.3 | Workflow | Agent → N+1 → RH (valider / rejeter) — circuit **par type** (N+1 / RH / DG) | ✅ |
| C.4 | PDF | Fiche + attestation | ✅ |
| C.5 | Notifications | Via Vague A | ✅ |
| C.6 | Tests + guide de test | Tests Feature + note FE §2c | ✅ |

Préfixe : `/api/conges/…` et `/api/absences`.

---

## Vague D — Carrière / évaluations puis compléments

| Vague | Module | Contenu | Bureau cible (doc) | Statut |
|-------|--------|---------|--------------------|--------|
| D.1 | Évaluations & avancements | P1–P5 + lots A–D | Personnel | ✅ |
| D.2 | Discipline | Types CCN, N+1→RH→DG, pièces, PDF, historique | Personnel + conformité | ✅ |
| D.3 | Affaires sociales | Organismes, affiliations, ayants droit, prestations, santé / AT-MP | Affaires sociales | ✅ **P1 + D.3.4 + D.3.5** |
| D.4 | Formation | Catalogue, plan annuel, inscriptions, certifications + `convertir-agent` | Formation | ✅ |
| **E** | Conformité CCN 3–5 | Essai, contrat 30 j, pièces/CNSS, positions 76–80, hors grille DG | Personnel + Solde | ⬜ |
| D.5 | Paie (éléments + lots) | Primes / retenues, lot mensuel, bulletin enrichi, export | Solde | ✅ |
| D.6 | Reporting | Dashboard effectifs, répartitions, exports | Étude et planification | ✅ |

Détail D.1 : [`plan-module-evaluation-notation.md`](./plan-module-evaluation-notation.md) + [`plan-evaluation-complements.md`](./plan-evaluation-complements.md).

Compléments intégration (pas un nouveau métier) : permissions fines `/integration/*`, PDF actes. `convertir-agent` est rattaché à **D.4**.

Hors ces vagues : **logistique**, recrutement amont, GED versionnée, notes administratives génériques, planning calendrier, mail / SMS — voir § Compléments reportés.

---

### D.2 — Discipline ✅

Préfixe : `/api/discipline`. Permissions : `consulter-discipline` / `gerer-discipline` / `proposer-discipline` / `prononcer-discipline`. Circuit CCN art. 90–91 : N+1 propose → RH instruit → DG prononce.

| # | Tâche | Statut |
|---|--------|--------|
| D.2.1 | Référentiel `types-sanctions` (4 codes CCN art. 90) | ✅ |
| D.2.2 | Dossier sanction : rapport, instruire, prononcer / classer, historique | ✅ |
| D.2.3 | Avertissements | ✅ |
| D.2.4 | Notifications + tests Feature + note FE | ✅ |
| D.2.5 | Pièces jointes, mise à pied 1–8 j, PDF rapport / décision | ✅ |
| D.2.6 | Conservation 5 ans, récidive (antécédents), suspension / archivage auto | ✅ |

**Hors D.2 :** conseil de discipline, recours, indemnité de licenciement calculée, abandon de poste.

---

### D.3 — Affaires sociales ✅ P1 + D.3.4 + D.3.5

Préfixe : `/api/affaires-sociales`. Permissions : `consulter-affaires-sociales` / `gerer-affaires-sociales` / **`decider-prestations`** (DG).  
Réutilise `situation-familiale` (`nb_enfants` recalculé dès qu’il existe des enfants nominatifs) et le n° CNSS de l’agent (versé dans l’affiliation CNSS).

| # | Tâche | Contenu | Statut |
|---|--------|---------|--------|
| D.3.1 | Organismes | CRUD CNSS / mutuelle / complémentaire | ✅ |
| D.3.2 | Affiliations | N° , dates, statut ; alerte agent sans affiliation | ✅ |
| D.3.3 | Ayants droit | Conjoint / enfants nominatifs + pièces (remplace le seul `nb_enfants`) | ✅ |
| D.3.4 | Prestations | Demandes (décès, retraite) → instruire → décider DG → pose paie | ✅ |
| D.3.5 | Santé / AT-MP / dossier retraite | Visites, prises en charge, arrêts 132–135 ; dossier retraite hors V1 | ✅ |

D.3.1–D.3.3 = **P1 livré**. D.3.4 **livré** ([`plan-module-prestations.md`](./plan-module-prestations.md)). D.3.5 **livré** ([`plan-module-sante.md`](./plan-module-sante.md)).

---

### D.4 — Formation ✅

Préfixe : `/api/formations`. Permissions : `consulter-formations` / `gerer-formations`.  
Les stages d’**accueil** (`/integration/stages`) restent où ils sont ; ce module = formation **continue** des agents.

| # | Tâche | Contenu | Statut |
|---|--------|---------|--------|
| D.4.1 | Catalogue | Interne / externe, durée, organisme, coût | ✅ |
| D.4.2 | Plan annuel | Brouillon → validé → exécuté | ✅ |
| D.4.3 | Inscriptions | Inscrire, présence, clôturer | ✅ |
| D.4.4 | Certifications | Pièce + lien diplôme / reclassement art. 73 déjà livré | ✅ |
| D.4.5 | `POST /integration/stages/{id}/convertir-agent` | Stage L3 (accueil → agent) | ✅ |

---

### Vague E — Conformité CCN (modules 3, 4, 5) ⬜

**Avant D.5.** Détail, lots A–E et règles métier : [`plan-conformite-ccn-modules-3-4-5.md`](./plan-conformite-ccn-modules-3-4-5.md).

Ne pas recoder la grille (annexe 2) ni les reclassements art. 73–75. Primes art. 56 / indemnités art. 57 restent **D.5**.

| # | Lot | Contenu | Statut |
|---|-----|---------|--------|
| E.A | Essai + contrat 30 j | Art. 49–52 — durées 1/2/3 mois, renouvellement ×1, `necessite_contrat` | ✅ |
| E.B | Pièces + CNSS | Art. 46–47 | ✅ |
| E.C | Positions | Art. 76–80 — plus de PUT statut nu | ✅ |
| E.D | Hors grille + accès | Art. 55 DG/DC/DD ; bonifs d’échelon annexe 1 | ✅ |
| E.E | Compléments | Art. 48, 50, 81–82 | ✅ |

---

### D.5 — Paie (éléments + lots) ✅

Découpage : [`plan-module-paie.md`](./plan-module-paie.md) (branche `feature/paie-d5`).

**Extension** de `/salaires-agents` : ne pas casser grille, salaire indiciaire, bulletin simplifié.

Préfixe nouveau : `/api/paie`. Permissions existantes `consulter-salaires` / `gerer-salaires` (pas de `gerer-paie` en V1).

| # | Tâche | Contenu | Statut |
|---|--------|---------|--------|
| D.5.1 | Référentiel d’éléments | Primes, indemnités, retenues (récurrent / ponctuel) | ✅ |
| D.5.2 | Affectation agent | Période, montant | ✅ |
| D.5.3 | Lot mensuel | Générer → contrôler → valider → clôturer | ✅ |
| D.5.4 | Bulletin enrichi | Base + éléments + net (nouveau endpoint ou champ optionnel) | ✅ |
| D.5.5 | Export masse salariale | CSV / PDF | ✅ |

---

### D.6 — Reporting ✅

Préfixe : `/api/reporting` — permission `consulter-reporting` (`rh`, `admin`, **`directeur-general`**). Plan : [`plan-module-reporting.md`](./plan-module-reporting.md).

| # | Tâche | Contenu | Statut |
|---|--------|---------|--------|
| D.6.1 | Dashboard | Effectifs présent, pyramide, répartitions, entrées/sorties, masse salariale | ✅ |
| D.6.2 | Stats congés / évaluations | Agrégats année + session courante (stats session inchangées) | ✅ |
| D.6.3 | Exports | PDF / CSV effectifs, congés, notation | ✅ |
| D.6.4 | Alertes conformité | Sans N+1, dossier incomplet, CNSS, contrats 30/60 j, postes vacants | ✅ |

GPEEC avancé (prévision retraite, vacances de postes au-delà de `nominations/postes-vacants`) : **hors V1**.

---

## Vague F — Cloisonnement (plus tard, ne pas ouvrir maintenant)

Quand les modules D.2–D.6 sont consommés par le FE :

1. Menus par permission **métier** déjà en place ; ajouter éventuellement un rattachement utilisateur → bureau.
2. Filtrer les listes « uniquement ma structure » (Direction / Service / Bureau).
3. Option : un agent DRHL n’a que les permissions de **son** bureau (Personnel vs Solde vs Formation vs Affaires sociales).
4. Seeder `B.A.S.` + champ `description` des missions si le métier le valide.

Jusque-là : **pas** de middleware ni de scope Eloquent par bureau.

---

## Compléments reportés (hors D.2–D.6)

| Sujet | Note |
|---|---|
| PDF actes d’intégration | Phase 1.B |
| Permissions fines `/integration/*` | Coordonner FE |
| Planning calendrier absences / congés | Module 6.4 |
| Notes administratives génériques | Hors notes de service d’affectation |
| GED versionnée | Module 12 |
| Recrutement amont (concours) | Phase 8 |
| Service Logistique | Hors RH |
| Mail / SMS | Hors MVP |

---

## Ordre recommandé

```
A Notifications  →  B Dossier agent  →  C Congés  →  D.1 Évaluations
                                                         ↓
                                              D.2 Discipline  ✅
                                                         ↓
                                              D.3 Affaires sociales (P1)  ✅
                                                         ↓
                                              D.4 Formation  ✅
                                                         ↓
                                              Vague E Conformité CCN 3–5  ← ici
                                                         ↓
                                              D.5 Paie éléments + lots
                                                         ↓
                                              D.6 Reporting
                                                         ↓
                                              F Cloisonnement par bureau   ← après, pas maintenant
```

1. ~~**D.2 Discipline**~~ **livré**.  
2. ~~**D.3.1–D.3.3** Affaires sociales (P1)~~ **livré**.  
3. ~~**D.4** Formation (+ `convertir-agent`)~~ **livré**.  
4. ~~**Vague E** conformité CCN~~ **livré**.  
5. ~~**D.5** Paie~~ **livré**.  
6. ~~**D.6** Reporting~~ **livré**.  
7. ~~**D.3.4** Prestations~~ **livré**. ~~**D.3.5** Santé / AT-MP~~ **livré**. **Vague F** cloisonnement — seulement une fois le FE branché.

---

## Journal

| Date | Vague | Fait |
|------|-------|------|
| 2026-09-01 | — | Création de ce suivi |
| 2026-09-01 | A | Inbox API, branchements intégration/carrière, job stage |
| 2026-09-01 | B | Fiche personnel, GED agent, archivage |
| 2026-09-01 | C | Congés & absences (demandes, soldes, workflow, PDF) |
| 2026-09-10 | D.1 | Évaluations P1–P5 (sessions → commissions → art. 71–72, stats, notifs) |
| 2026-09-14 | D.1 | Lots A–C (art. 62, tableau D5, PDF fiche + synthèse) |
| 2026-09-15 | D.1 | Lot D art. 73–75 (`/carriere/reclassements`). Vague D.1 **close**. |
| 2026-09-15 | D.2 | Discipline : `/discipline` (types, dossiers, instruire / valider / rejeter, avertissements, historique). Permissions `consulter-discipline` / `gerer-discipline`. |
| 2026-09-15 | D.2 | Alignement CCN art. 90–91 : 4 types, N+1→RH→DG, pièces, mise à pied 1–8 j, PDF. Permissions `proposer-discipline` / `prononcer-discipline`. |
| 2026-09-15 | D.2 | Vague B : conservation 5 ans, antécédents/récidive, mise à pied → `suspendu`, licenciement → archivage. |
| 2026-09-15 | Plan | Modules D.3–D.6 + Vague F cloisonnement. Décision : **pas de cloison** tant que les modules ne sont pas livrés. Prochain : **D.3**. |
| 2026-09-15 | D.3 | Affaires sociales P1 : `/affaires-sociales` (organismes, affiliations, ayants droit CCN art. 59, pièces, alerte CNSS, dossier social). Permissions `consulter-affaires-sociales` / `gerer-affaires-sociales`. |
| 2026-09-15 | D.4 | Formation continue : `/formations` (catalogue, plan annuel, inscriptions CCN art. 92–104, certifications) + `POST /integration/stages/{id}/convertir-agent`. |
| 2026-09-16 | E | Plan conformité CCN modules 3–5 (essai, contrat 30 j, pièces/CNSS, positions 76–80, hors grille). **Avant D.5.** |
| 2026-09-16 | D.5 | Découpage technique paie : [`plan-module-paie.md`](./plan-module-paie.md). Code non commencé. |
| 2026-09-16 | D.5.1 | Référentiel `/api/paie/elements` + seeder CCN art. 54–59. |
| 2026-09-16 | D.5.2 | Affectations `/api/paie/affectations` (éligibilité, chevauchement). |
| 2026-09-16 | D.5.3 | Lots `/api/paie/lots` : génération CCN (base, ancienneté, fin d’année, affectations), contrôle, validation, clôture. |
| 2026-09-16 | D.5.4 | Bulletin PDF enrichi `GET /api/paie/lots/{id}/lignes/{ligneId}/bulletin`. Bulletin indiciaire inchangé. |
| 2026-09-16 | D.5.5 | Export masse `GET /api/paie/lots/{id}/export?format=csv\|pdf` (lot validé / clôturé). |
| 2026-09-16 | D.5 | Compléments : génération bulk, autos paramétrés (AF, SFT, vestimentaire, transport, CNSS), `PUT` commentaire, `actions` / filtres. |
| 2026-09-16 | E.A | Lot A livré : essai art. 49, délai 30 j art. 52, `necessite_contrat` recrutement externe, jobs d’alerte. |
| 2026-09-16 | E.B | Lot B livré : pièces art. 46 (ACE, mariage, déjà salarié), CNSS art. 47 à `integrer`. |
| 2026-09-17 | D.6 | Reporting `/api/reporting` : dashboard, stats congés/évaluations, alertes, exports PDF/CSV. DG : `consulter-reporting`. |
| 2026-09-17 | D.3.4 | Prestations `/api/affaires-sociales/prestations` : circuit DG, barèmes CCN art. 119–121, pose paie, PDF. Permission `decider-prestations`. |
| 2026-09-17 | D.3.5 | Santé / AT-MP : structures, visites, prises en charge, arrêts art. 122–135, pose paie, PDF. Même `decider-prestations`. |
