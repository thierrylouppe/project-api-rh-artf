# Prochaines fonctionnalités — suivi d’implémentation

> Document **vivant** : cocher au fur et à mesure.  
> Dernière mise à jour : **2026-09-15** (modules DRHL ajoutés — cloisonnement reporté)  
> Architecture obligatoire : [`architecture.md`](./architecture.md)  
> Plan long : [`plan_complet.md`](./plan_complet.md)  
> Contrat FE actuel : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md)  
> Cible métier : [`organigramme-drhl.md`](./organigramme-drhl.md)

**Objectif :** livrer les modules métier manquants (vie de l’agent + DRHL), **sans cloisonner** par service / bureau. Le cloisonnement vient **après**.

**État :** le cœur API (entrée → carrière → paie → congés → notation → avancement → reclassement) est **livré**. Prochain module neuf : **D.2 Discipline**.

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
| D.2 | Discipline | Types, sanctions, valider / rejeter, historique | Personnel + conformité | ⬜ **prochain** |
| D.3 | Affaires sociales | Organismes, affiliations, ayants droit ; prestations ensuite | Affaires sociales | ⬜ |
| D.4 | Formation | Catalogue, plan annuel, inscriptions, certifications + `convertir-agent` | Formation | ⬜ |
| D.5 | Paie (éléments + lots) | Primes / retenues, lot mensuel, bulletin enrichi | Solde | ⬜ |
| D.6 | Reporting | Dashboard effectifs, répartitions, exports | Étude et planification | ⬜ |

Détail D.1 : [`plan-module-evaluation-notation.md`](./plan-module-evaluation-notation.md) + [`plan-evaluation-complements.md`](./plan-evaluation-complements.md).

Compléments intégration (pas un nouveau métier) : permissions fines `/integration/*`, PDF actes. `convertir-agent` est rattaché à **D.4**.

Hors ces vagues : **logistique**, recrutement amont, GED versionnée, notes administratives génériques, planning calendrier, mail / SMS — voir § Compléments reportés.

---

### D.2 — Discipline ⬜ **prochain**

Préfixe : `/api/discipline` (nouveau). Permissions à seeder : `consulter-discipline` / `gerer-discipline` (soft launch sur les **nouvelles** routes).

| # | Tâche | Statut |
|---|--------|--------|
| D.2.1 | Référentiel `types-sanctions` (gravité) | ⬜ |
| D.2.2 | Dossier sanction : créer, instruire, valider / rejeter, historique agent | ⬜ |
| D.2.3 | Avertissements | ⬜ |
| D.2.4 | Notifications + tests Feature + note FE | ⬜ |

**Hors D.2 :** procédure contentieuse longue (recours, conseil de discipline) — itération suivante si le métier le demande.

---

### D.3 — Affaires sociales ⬜

Préfixe : `/api/affaires-sociales`. Permissions : `consulter-affaires-sociales` / `gerer-affaires-sociales`.  
Réutilise `situation-familiale` (trop pauvre aujourd’hui) et le n° CNSS déjà exigé à l’intégration (pièce, pas encore objet métier).

| # | Tâche | Contenu | Statut |
|---|--------|---------|--------|
| D.3.1 | Organismes | CRUD CNSS / mutuelle / complémentaire | ⬜ |
| D.3.2 | Affiliations | N° , dates, statut ; alerte agent sans affiliation | ⬜ |
| D.3.3 | Ayants droit | Conjoint / enfants nominatifs + pièces (remplace le seul `nb_enfants`) | ⬜ |
| D.3.4 | Prestations | Demandes (décès, aides, allocations) → instruire → décider | ⬜ après D.5 |
| D.3.5 | Santé / AT-MP / dossier retraite | Visites, accidents, pension | ⬜ plus tard |

D.3.1–D.3.3 = **P1** (ex-« sécurité sociale »). D.3.4 calcule des montants : dépend des **éléments de paie** (D.5) pour atterrir sur le bulletin.

---

### D.4 — Formation ⬜

Préfixe : `/api/formations`. Permissions : `consulter-formations` / `gerer-formations`.  
Les stages d’**accueil** (`/integration/stages`) restent où ils sont ; ce module = formation **continue** des agents.

| # | Tâche | Contenu | Statut |
|---|--------|---------|--------|
| D.4.1 | Catalogue | Interne / externe, durée, organisme, coût | ⬜ |
| D.4.2 | Plan annuel | Brouillon → validé → exécuté | ⬜ |
| D.4.3 | Inscriptions | Inscrire, présence, clôturer | ⬜ |
| D.4.4 | Certifications | Pièce + lien diplôme / reclassement art. 73 déjà livré | ⬜ |
| D.4.5 | `POST /integration/stages/{id}/convertir-agent` | Stage L3 (accueil → agent) | ⬜ |

---

### D.5 — Paie (éléments + lots) ⬜

**Extension** de `/salaires-agents` : ne pas casser grille, salaire indiciaire, bulletin simplifié.

Préfixe nouveau : `/api/paie`. Permissions existantes `consulter-salaires` / `gerer-salaires` (ou `gerer-paie` en plus, soft launch).

| # | Tâche | Contenu | Statut |
|---|--------|---------|--------|
| D.5.1 | Référentiel d’éléments | Primes, indemnités, retenues (récurrent / ponctuel) | ⬜ |
| D.5.2 | Affectation agent | Période, montant | ⬜ |
| D.5.3 | Lot mensuel | Générer → contrôler → valider → clôturer | ⬜ |
| D.5.4 | Bulletin enrichi | Base + éléments + net (nouveau endpoint ou champ optionnel) | ⬜ |
| D.5.5 | Export masse salariale | CSV / PDF | ⬜ (peut aller dans D.6) |

---

### D.6 — Reporting ⬜

Préfixe : `/api/reporting` — permission **déjà** seedée : `consulter-reporting`.

| # | Tâche | Contenu | Statut |
|---|--------|---------|--------|
| D.6.1 | Dashboard | Effectifs, pyramide, répartitions direction / grade / sexe | ⬜ |
| D.6.2 | Stats congés / évaluations | Agrégats (les stats **session** existent déjà) | ⬜ |
| D.6.3 | Exports | PDF / Excel effectifs, congés, notation | ⬜ |
| D.6.4 | Alertes conformité | Agents sans N+1, dossiers incomplets, sans affiliation (D.3) | ⬜ |

GPEEC avancé (prévision retraite, vacances de postes au-delà de `nominations/postes-vacants`) : après D.6.1.

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
                                              D.2 Discipline  ← ici
                                                         ↓
                                              D.3 Affaires sociales (P1 : organismes, affiliations, ayants droit)
                                                         ↓
                                              D.4 Formation
                                                         ↓
                                              D.5 Paie éléments + lots
                                                         ↓
                                              D.6 Reporting
                                                         ↓
                                              F Cloisonnement par bureau   ← après, pas maintenant
```

1. **D.2 Discipline** — premier module neuf.  
2. **D.3.1–D.3.3** Affaires sociales (P1).  
3. **D.4** Formation (+ `convertir-agent`).  
4. **D.5** Paie, puis **D.3.4** prestations / allocations.  
5. **D.6** Reporting.  
6. **Vague F** cloisonnement — seulement une fois le FE branché sur ces modules.

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
| 2026-09-15 | Plan | Modules D.3–D.6 + Vague F cloisonnement. Décision : **pas de cloison** tant que les modules ne sont pas livrés. Prochain : **D.2**. |
