# Prochaines fonctionnalités — suivi d’implémentation

> Document **vivant** : cocher au fur et à mesure.  
> Dernière mise à jour : **2026-09-01** (Vague A livrée sur `feature/notifications`)  
> Architecture obligatoire : [`architecture.md`](./architecture.md)  
> Plan long : [`plan_complet.md`](./plan_complet.md)  
> Contrat FE actuel : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md)

**Objectif :** livrer le cœur « vie de l’agent » après l’entrée, sans casser le frontend déjà branché.

---

## Déjà livré (ne pas relancer)

| Module | Préfixe | Notes |
|--------|---------|--------|
| Auth, users, rôles | `/login`, `/users`, `/roles` | Permissions Spatie |
| Structure org. | `/localites` … `/bureaux` | |
| Référentiels | `/diplomes`, `/types-conges`, `/types-absences`, … | CRUD listes seulement |
| Intégration | `/integration/…` | Dossier, circuit, acte métier, stage, matériel, PDS |
| Personnel | `/personnel/…` | Annuaires intégrés / stagiaires |
| Carrière | `/carriere/…` | Affectations, nominations, lots, contrats, synthèse |
| Salaires | `/salaires`, `/salaires-agents`, grille | Bulletin PDF, `avancerEchelon` |

**Hors scope immédiat :** PDF actes d’intégration (Phase 1.B), recrutement amont (concours), formation, reporting.

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

## Vague A — Notifications (en cours / à finir)

**Pourquoi maintenant :** écriture DB déjà partielle (nomination, lots). Pas d’inbox API. Job stage encore en TODO log.

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

## Vague B — Dossier agent vie courante

**Pourquoi :** la fiche hors wizard et les docs hors intégration manquent.

| # | Tâche | Statut |
|---|--------|--------|
| B.1 | Infos perso / pro / contacts / situation familiale (si incomplet) | ⬜ |
| B.2 | Documents agent hors dossier d’intégration (GED légère) | ⬜ |
| B.3 | Soft delete / archivage agent (règles métier) | ⬜ |

Lecture carrière (`GET /carriere/agents/{id}`, historiques) : **déjà livré**.

---

## Vague C — Congés & absences (premier gros module neuf)

Référentiels `TypeConge` / `TypeAbsence` déjà en place.

| # | Sous-module | Contenu | Statut |
|---|-------------|---------|--------|
| C.1 | Paramétrage | Jours fériés, règles d’acquisition, soldes | ⬜ |
| C.2 | Demandes | CRUD + calcul jours ouvrables (week-ends + fériés) | ⬜ |
| C.3 | Workflow | Agent → N+1 → RH (valider / rejeter) | ⬜ |
| C.4 | PDF | Fiche + attestation | ⬜ |
| C.5 | Notifications | Via Vague A | ⬜ |
| C.6 | Tests + guide de test | | ⬜ |

Préfixe recommandé : `/api/conges/…` (et `/api/absences` si séparé).  
Permissions nouvelles seulement (ne pas durcir les routes existantes).

---

## Vague D — ensuite (ne pas commencer avant C)

| Vague | Module | Contenu | Statut |
|-------|--------|---------|--------|
| D.1 | Évaluations & avancements | Sessions → notation /20 → avis → commissions → lien `avancerEchelon` | ⬜ |
| D.2 | Discipline | Types, sanctions, valider / rejeter, historique | ⬜ |
| D.3 | Sécurité sociale | Organismes + affiliations | ⬜ |
| D.4 | Reporting / GED / formation | Backlog | ⬜ |

Compléments intégration (pas un nouveau métier) : permissions fines `/integration/*`, stage L2/L3 (`convertir-agent`), PDF actes.

---

## Ordre recommandé

```
A Notifications  →  C Congés  →  D.1 Évaluations
        ↘ B Dossier agent (en parallèle si le FE en a besoin)
```

1. Finir **A** (inbox + job stage).  
2. Enchaîner **C** (vie quotidienne RH).  
3. **B** en parallèle seulement si un écran fiche / GED agent est bloqué.  
4. **D.1** après les congés (module le plus lourd).

---

## Journal

| Date | Vague | Fait |
|------|-------|------|
| 2026-09-01 | — | Création de ce suivi |
| 2026-09-01 | A | Inbox API, branchements intégration/carrière, job stage |
