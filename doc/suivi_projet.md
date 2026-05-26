# Suivi de projet — Gestion RH API

> Dernière mise à jour : 2026-05-26  
> Références : [`roadmap.md`](./roadmap.md) · [`structuration_par_module.md`](./structuration_par_module.md)

**Légende :** ⬜ À réaliser · 🔄 En cours · ✅ Réalisé · ⏸ Non réalisé (reporté)

**Base de données :** MySQL `bd_api_rh_artf` (utf8mb4_unicode_ci) sur `127.0.0.1:3306`

---

## Phase 0 — Socle technique

| # | Tâche | Statut | Résumé |
|---|-------|--------|--------|
| 0.1 | Dépendances Sanctum, Spatie, DomPDF | ✅ | Packages installés et publiés |
| 0.1 | Dépendances Swagger (l5-swagger) | ⏸ | Incompatible Laravel 13 — à réinstaller plus tard |
| 0.1 | Dépendances Pest | ⏸ | Incompatible Laravel 13 / PHPUnit 12 — PHPUnit conservé |
| 0.1 | Config `.env` (queue, cache, session) | ✅ | Queue, cache et session configurés en `database` |
| 0.1 | Config `sanctum.php` et `permission.php` | ✅ | Guard `api`, expiration token 480 min, cache Spatie actif |
| 0.2 | Middlewares `CheckPermission` / `CheckRole` | ✅ | Réponses JSON 403 sur permission ou rôle manquant |
| 0.2 | Alias middlewares + routes API dans `bootstrap/app.php` | ✅ | Alias `permission`/`role` et `routes/api.php` enregistrés |
| 0.3 | `BaseInterface`, `BaseRepository`, `BaseService`, `BaseController` | ✅ | CRUD générique prêt pour héritage par module |
| 0.4 | Traits `HasAutoSigle`, `HasFilterScope` | ✅ | Sigle auto depuis le nom et scope de filtrage générique |
| 0.5 | `OpenApiDefinition` Swagger | ✅ | Annotations `@OA\Info` et schéma bearer Sanctum |
| 0.6 | `AppServiceProvider` (bindings + observers) | ✅ | Tableau `$repositoryBindings` prêt pour les modules |

**Phase 0 : terminée** (hors Swagger package et Pest, reportés)

---

## Module 1 — Paramétrage & Référentiels

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 1.1 Structures organisationnelles | ✅ | Hiérarchie Localite → Administration → Direction → Service → Bureau avec CRUD, sigles auto, endpoints `byParent` et seeders (4/3/7/9/8 lignes) |
| 1.2 Référentiels RH | ✅ | 11 référentiels CRUD (diplômes, grades, catégories, échelons, fonctions, types contrat/document/recrutement/absence/congé, motifs) avec seeders |
| 1.3 Administration système | ✅ | Auth Sanctum, users/rôles/permissions, audit logs, paramètres app — 36 permissions, 5 rôles, 2 utilisateurs seedés |

---

## Module 2 — Gestion du Personnel / Dossier Agent

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 2.1 Fiche agent | ⬜ | — |
| 2.2 Documents d'entrée | ⬜ | — |
| 2.3 Compte utilisateur lié | ⬜ | — |

---

## Module 3 — Entrée dans l'administration

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 3.1 Recrutement externe | ⬜ | — |
| 3.2 Autres modes d'intégration | ⬜ | — |
| 3.3 Workflow d'intégration | ⬜ | — |

---

## Module 4 — Contrats & Situation Administrative

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 4.1 Contrats | ⬜ | — |
| 4.2 Carrière | ⬜ | — |
| 4.3 Affectations | ⬜ | — |
| 4.4 Notes administratives | ⬜ | — |

---

## Module 5 — Salaires & Grilles Salariales

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 5.1 Grilles & barèmes | ⬜ | — |
| 5.2 Bulletins & historique | ⬜ | — |

---

## Module 6 — Congés & Absences

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 6.1 Demandes de congé | ⬜ | — |
| 6.2 Solde congés | ⬜ | — |
| 6.3 Absences | ⬜ | — |
| 6.4 Planning | ⬜ | — |

---

## Module 7 — Évaluation & Avancements

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 7.1 Campagnes d'évaluation | ⬜ | — |
| 7.2 Fiches d'évaluation | ⬜ | — |
| 7.3 Notation | ⬜ | — |
| 7.4 Résultats | ⬜ | — |

---

## Module 8 — Formation & Développement

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 8.1 Catalogue formations | ⬜ | — |
| 8.2 Plans de formation | ⬜ | — |
| 8.3 Inscriptions & suivi | ⬜ | — |
| 8.4 Certifications | ⬜ | — |

---

## Module 9 — Discipline & Contentieux

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 9.1 Sanctions & avertissements | ⬜ | — |
| 9.2 Procédures disciplinaires | ⬜ | — |
| 9.3 Historique disciplinaire | ⬜ | — |

---

## Module 10 — Tableau de Bord & Reporting

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 10.1 Dashboard RH | ⬜ | — |
| 10.2 Statistiques | ⬜ | — |
| 10.3 Rapports PDF/Excel | ⬜ | — |

---

## Module 11 — Notifications & Communication

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 11.1 Notifications système | ⬜ | — |
| 11.2 Emails | ⬜ | — |
| 11.3 Alertes échéances | ⬜ | — |

---

## Module 12 — GED RH

| Sous-module | Statut | Résumé |
|-------------|--------|--------|
| 12.1 Classement documents | ⬜ | — |
| 12.2 Archivage & recherche | ⬜ | — |
| 12.3 Historique versions | ⬜ | — |
