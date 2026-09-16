# Suivi de projet — Gestion RH API

> Dernière mise à jour : 2026-09-16  
> Références : [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md) · [`plan_complet.md`](./plan_complet.md) · [`plan-module-paie.md`](./plan-module-paie.md) · [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md) · [`roadmap.md`](./roadmap.md) · [`structuration_par_module.md`](./structuration_par_module.md) · [`SPEC-GRILLE-SALARIALE.md`](./SPEC-GRILLE-SALARIALE.md)

**Légende :** ⬜ À réaliser · 🔄 En cours · ✅ Réalisé · ⏸ Non réalisé (reporté)

**Base de données :** MySQL `bd_api_rh_artf` (utf8mb4_unicode_ci) sur `127.0.0.1:3306`

**État :** cœur « vie de l’agent » API **livré**. **D.2 Discipline, D.3 P1 Affaires sociales, D.4 Formation, D.5 Paie livrés.** Vague E lots **A–E** livrés. Prochain : **D.6 Reporting**. Modules DRHL suivants **globaux** (pas de cloison bureau).

Plan E : [`plan-conformite-ccn-modules-3-4-5.md`](./plan-conformite-ccn-modules-3-4-5.md).

---

## Phase 0 — Socle technique

| # | Tâche | Statut | Résumé |
|---|-------|--------|--------|
| 0.1 | Dépendances Sanctum, Spatie, DomPDF | ✅ | Packages installés et publiés |
| 0.1 | Dépendances Swagger (l5-swagger) | ✅ | `darkaonline/l5-swagger` — UI `/api/documentation` |
| 0.1 | Dépendances Pest | ⏸ | Incompatible Laravel 13 / PHPUnit 12 — PHPUnit conservé |
| 0.1 | Config `.env` (queue, cache, session) | ✅ | Queue, cache et session configurés en `database` |
| 0.1 | Config `sanctum.php` et `permission.php` | ✅ | Guard `api`, expiration token 480 min, cache Spatie actif |
| 0.2 | Middlewares `CheckPermission` / `CheckRole` | ✅ | Réponses JSON 403 sur permission ou rôle manquant |
| 0.2 | Alias middlewares + routes API dans `bootstrap/app.php` | ✅ | Alias `permission`/`role` et `routes/api.php` enregistrés |
| 0.3 | `BaseInterface`, `BaseRepository`, `BaseService`, `BaseController` | ✅ | CRUD générique prêt pour héritage par module |
| 0.4 | Traits `HasAutoSigle`, `HasFilterScope` | ✅ | Sigle auto depuis le nom et scope de filtrage générique |
| 0.5 | `OpenApiDefinition` Swagger | ✅ | Annotations `@OA\Info` et schéma bearer Sanctum |
| 0.6 | `AppServiceProvider` (bindings + observers) | ✅ | Tableau `$repositoryBindings` — interfaces liées |

**Phase 0 : terminée** (hors Pest, reporté)

---

## Module 1 — Paramétrage & Référentiels

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 1.1 Structures organisationnelles | ✅ | Hiérarchie Localite → Administration → Direction → Service → Bureau |
| 1.2 Référentiels RH | ✅ | Diplômes, grades, catégories (Classe I–X dont Hors Classe), échelons, fonctions, types… |
| 1.3 Administration système | ✅ | Auth Sanctum, users/rôles/permissions, audit logs, paramètres app |

---

## Module 2 — Gestion du Personnel / Dossier Agent

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 2.1 Fiche agent | ✅ | `/personnel/agents/{id}` + fiche wizard `/integration/agents/{id}` |
| 2.2 Documents d'entrée | ✅ | Documents dossier d’intégration + GED agent légère (`/personnel/agents/{id}/documents`) |
| 2.3 Compte utilisateur lié | ✅ | Provisionnement à l’entrée (`/integration/comptes`) |

---

## Module 3 — Entrée dans l'administration

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 3.1 Recrutement externe | ⏸ | Concours / candidatures — Phase 8, hors chemin critique |
| 3.2 Autres modes d'intégration | ✅ | Types d’intégration + circuit par type (`/integration/…`) |
| 3.3 Workflow d'intégration | ✅ | Dossier, transitions, acte métier, stage, matériel, PDS. **Hors V1 :** PDF actes (1.B). `convertir-agent` **livré** (D.4.5). |

---

## Module 4 — Contrats & Situation Administrative

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 4.1 Contrats | ✅ | `/carriere/contrats`. Essai art. 49 (1/2/3 mois), délai 30 j art. 52. Salaire min. de classe pendant l’essai |
| 4.2 Carrière | ✅ | Affectations, nominations, lots, synthèse, **reclassements art. 73–75** |
| 4.3 Affectations | ✅ | Unitaire + groupée, notes de service |
| 4.4 Notes administratives | ⬜ | Module dédié non livré (les notes de service d’affectation existent) |

---

## Module 5 — Salaires & Grilles Salariales

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 5.1 Grilles & barèmes | ✅ | Génération HTTP `POST /salaires/generation`. Permissions `consulter-salaires` / `gerer-salaires`. |
| 5.2 Bulletins & historique | ✅ | `salaires_agents`, clôture, `avancerEchelon` / `avancerEchelons`, `changerClasse`, bulletin PDF |
| 5.3 Éléments de paie + lots mensuels | ✅ | Vague **D.5** livrée — [`plan-module-paie.md`](./plan-module-paie.md) |

**Référence :** [`SPEC-GRILLE-SALARIALE.md`](./SPEC-GRILLE-SALARIALE.md)

---

## Module 6 — Congés & Absences

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 6.1 Demandes de congé | ✅ | `/conges/demandes`, circuit par type (N+1 / RH / DG), PDF |
| 6.2 Solde congés | ✅ | Acquisition, paliers ancienneté CCN, débit à l’accord |
| 6.3 Absences | ✅ | `/absences`, file N+1 |
| 6.4 Planning | ⬜ | Vue calendrier agrégée non livrée |

---

## Module 7 — Évaluation & Avancements

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 7.1 Campagnes d'évaluation | ✅ | Sessions `/avancements/sessions`, éligibilité CCN 24 mois |
| 7.2 Fiches d'évaluation | ✅ | Génération auto, N+1 art. 62, réattribution, `sans-superieur` |
| 7.3 Notation | ✅ | Grille 24 q /20, avis, signatures, réclamation, chaîne art. 64 |
| 7.4 Résultats | ✅ | Commissions, tableau D5, PDF, art. 71–72, reclassements 73–75 |

Détail : [`plan-module-evaluation-notation.md`](./plan-module-evaluation-notation.md) · [`plan-evaluation-complements.md`](./plan-evaluation-complements.md)

---

## Module 8 — Formation & Développement

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 8.1 Catalogue formations | ✅ | Vague **D.4** — formation continue (les stages d’accueil restent `/integration/stages`) |
| 8.2 Plans de formation | ✅ | D.4.2 |
| 8.3 Inscriptions & suivi | ✅ | D.4.3 |
| 8.4 Certifications | ✅ | D.4.4 |
| 8.5 Conversion stagiaire → agent | ✅ | D.4.5 `convertir-agent` |

---

## Module 9 — Discipline & Contentieux

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 9.1 Sanctions & avertissements | ✅ | Vague D.2 — `/discipline` + CCN art. 90 |
| 9.2 Procédures disciplinaires | ✅ | Circuit N+1 → RH → DG (art. 91). Hors scope : conseil / recours |
| 9.3 Historique disciplinaire | ✅ | `GET /discipline/agents/{id}/historique` + `/moi/historique` |

---

## Module 10 — Tableau de Bord & Reporting

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 10.1 Dashboard RH | ⬜ | Vague **D.6** — permission `consulter-reporting` déjà seedée |
| 10.2 Statistiques | ⬜ | Stats **session d’évaluation** livrées — pas le dashboard RH |
| 10.3 Rapports PDF/Excel | ⬜ | PDF métier livrés ; reporting agrégé = D.6.3 |

---

## Module 11 — Notifications & Communication

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 11.1 Notifications système | ✅ | Inbox `/notifications`, canal `database` |
| 11.2 Emails | ⏸ | Hors MVP |
| 11.3 Alertes échéances | ✅ | `ConventionStageEnFinDateJob` (et jobs contrats) |

---

## Module 12 — GED RH

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 12.1 Classement documents | ✅ partiel | GED **légère** dossier agent — pas de GED RH versionnée |
| 12.2 Archivage & recherche | ⬜ | — |
| 12.3 Historique versions | ⬜ | — |

---

## Module 13 — Affaires sociales

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 13.1 Organismes | ✅ | Vague **D.3.1** — CNSS, mutuelle, complémentaire |
| 13.2 Affiliations | ✅ | D.3.2 — n°, dates, alerte sans affiliation |
| 13.3 Ayants droit | ✅ | D.3.3 — nominatif + pièces ; `nb_enfants` dérivé |
| 13.4 Prestations / allocations | ⬜ | D.3.4 — après éléments de paie D.5 |
| 13.5 Santé / AT-MP / retraite | ⬜ | D.3.5 — plus tard |

Modules 8, 9, 10, 13 : **globaux** (rôle `rh`). Cloisonnement par bureau = Vague F, plus tard.

---

## Prochaine étape recommandée

**Vague E** — lots **A–E** livrés. **D.5 Paie livré** (compléments : autos paramétrés, génération bulk). Suite : **D.6 Reporting**. Plan [`plan-module-paie.md`](./plan-module-paie.md).

Puis D.3.4 Prestations → D.6 Reporting. **Pas** de cloisonnement avant Vague F.

Suivi opérationnel : [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md).
