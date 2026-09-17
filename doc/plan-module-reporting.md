# Plan — Module Reporting (Vague D.6)

> Branche : `feature/reporting-d6`  
> Date : **2026-09-17**  
> Document **vivant** : cocher au fil du code.  
> Architecture : [`architecture.md`](./architecture.md)  
> Suivi vagues : [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md)  
> Bureau cible (doc métier, pas de cloison) : Étude et Planification (`B.PL`) — [`organigramme-drhl.md`](./organigramme-drhl.md)  
> Contrat FE : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md) — **ne pas concevoir d’écrans avant l’API**

**Objectif :** offrir un **tableau de bord RH en lecture seule** (effectifs, répartitions, stats, alertes, exports) **sans recoder** les stats déjà livrées par module.

**État :** décisions **figées** — D.6.1–D.6.4 **livrés**.

---

## 1. Cadre (non négociable)

Chaîne : Route → FormRequest (filtres) → Controller → **Service** → **Interface** → Repository → Model.

- Controller : Services uniquement. Mince. Pas de CRUD (`ReportingController` n’étend pas `BaseController`).
- Service : Interfaces (+ `ReportingExportService`). Agrégats métier ici (qui compte, qui est exclu).
- Repository : Eloquent uniquement. Pas de JSON métier.
- Bindings `Interface → Repository` dans `AppServiceProvider::register()`.
- Auth : `auth:sanctum` + `permission:consulter-reporting`.
- Préfixe API : **`/api/reporting`**. Extension, pas rupture.
- **Lecture seule.** Pas de table, pas de workflow, pas de notifications.
- Cloisonnement bureau `B.PL` : Vague **F** (pas de filtre « ma structure seulement »). Filtres direction / service / bureau = **choix utilisateur**, pas un périmètre automatique.
- Pas de permission `gerer-reporting`.

---

## 2. Frontière avec l’existant — **ne pas recoder**

| Déjà livré | D.6 |
|------------|-----|
| `GET /avancements/sessions/{id}/stats` | Agrégat **année** + **session courante** (ouverte, sinon dernière clôturée) |
| `GET /avancements/sessions/{id}/sans-superieur` | Alerte **globale** agents sans N+1 (hors session) |
| `GET /affaires-sociales/alertes/sans-affiliation-cnss` | Compteur + liste courte ; détail métier inchangé |
| `GET /carriere/nominations/postes-vacants` | Compteur + liste courte |
| Export masse `/paie/lots/{id}/export` | Carte dashboard du **dernier lot clôturé** ; export paie **reste** à `/paie` |
| PDF congés / fiches / bulletins | Rapports **agrégés** seulement |
| Inbox `/notifications` | Pas de nouvelles notifs V1 |

---

## 3. Décisions figées

| # | Décision |
|---|----------|
| 1 | Effectif **présent** = `actif` + `stagiaire` + `suspendu`. Positions CCN (détachement, dispo, sous le drapeau, position exceptionnelle) = tranches de `repartition_statuts`, hors présent. `archive` / `retraite` / `inactif` exclus du présent. |
| 2 | Genre = `agents.genre` (`M` / `F` / `inconnu`). |
| 3 | **Drill-down V1** : filtres `direction_id` → `service_id` → `bureau_id` (les trois). Pyramide d’âge : `&lt;25`, `25–34`, `35–44`, `45–54`, `55+`, `inconnu`. |
| 4 | Snapshot **live**. `annee` (défaut : année civile en cours) pour mouvements, stats, exports. |
| 5 | Export **PDF + CSV** (`;` + BOM, comme la paie). Pas de `.xlsx`. |
| 6 | Dashboard = cartes + répartitions. Stats congés / évaluations = endpoints dédiés. |
| 7 | Alertes = compteurs + listes courtes (plafond 50). Détail métier sur les routes d’origine. |
| 8 | **DG** : `consulter-reporting` (rôle `directeur-general`). Pas les autres rôles hiérarchiques. |
| 9 | Carte **masse salariale** = dernier lot `cloture` (`total_net`, gains, retenues, nb lignes, période). `null` si aucun. |
| 10 | Alertes contrats : fenêtres **30 j** et **60 j** (deux compteurs ; 60 **inclut** les 30). |
| 11 | Répartitions **type d’intégration** et **fonction** : oui. |
| 12 | Cartes **entrées / sorties** V1 : entrées = `date_prise_service` dans l’année ; sorties = `archived_at` dans l’année. |
| 13 | **Dossier incomplet** (recommandé) : agent **présent** sans fiche infos perso **ou** infos pro **ou** au moins un contact d’urgence. CNSS = alerte séparée. Situation familiale / photo / RIB **non** bloquants. |
| 14 | Export / stats évaluations : **les deux** — agrégat année **et** session courante (`portee=annee\|session`). |
| 15 | Alertes V1 (recommandé) : `sans_n1`, `dossier_incomplet`, `sans_affiliation_cnss`, `contrat_echeance_30`, `contrat_echeance_60`, `poste_vacant`. Pas d’essai / GPEEC. |

---

## 4. Fonctionnalités

### D.6.1 — Dashboard & effectifs ✅

`GET /api/reporting/dashboard`

Cartes : effectif présent, dont stagiaires, dont suspendus, `repartition_statuts` (tous statuts), entrées / sorties de l’année, masse salariale (dernier lot clôturé).

Répartitions (population **présente**) : direction, grade, genre, âge, type d’intégration, fonction.

`GET /api/reporting/effectifs` : liste paginée (même population / filtres).

`GET /api/reporting/repartitions?axe=` : `direction`, `grade`, `genre`, `age`, `statut`, `type_integration`, `fonction`.

### D.6.2 — Stats congés / évaluations ✅

`GET /api/reporting/stats/conges?annee=`

- Demandes de l’année : total, par statut
- `jours_poses` (somme `nb_jours`) vs `jours_accordes` (`TypeConge::estAccordee`)
- Par type de congé
- Absences de l’année : total, par type, par statut
- Agents en congé **aujourd’hui** (demandes accordées dont la période couvre `today`)

`GET /api/reporting/stats/evaluations?annee=`

- Bloc `annee` : sessions (ouvertes / clôturées / annulées), fiches par statut, moyenne /20, mentions
- Bloc `session_courante` : session ouverte, sinon dernière clôturée, sinon `null` (même forme que les stats session, **sans** remplacer `GET /avancements/sessions/{id}/stats`)

### D.6.3 — Exports ✅

`GET /api/reporting/exports/{type}?format=pdf|csv`

| Type | Contenu |
|------|---------|
| `effectifs` | Liste population présente + totaux |
| `conges` | Demandes de l’année + agrégats D.6.2 |
| `evaluations` | `portee=annee` (défaut) ou `portee=session` (+ `session_id` optionnel) |

### D.6.4 — Alertes conformité ✅

`GET /api/reporting/alertes`

| Code | Règle |
|------|--------|
| `sans_n1` | Présent, sans affectation active **ou** sans `superieur_hierarchique_id` |
| `dossier_incomplet` | Présent, manque perso / pro / contact urgence |
| `sans_affiliation_cnss` | Même règle que D.3 (`hors archive` et `hors stagiaire`) |
| `contrat_echeance_30` | Contrat `actif` avec `date_fin` dans 0–30 j |
| `contrat_echeance_60` | Contrat `actif` avec `date_fin` dans 0–60 j (inclut les 30) |
| `poste_vacant` | `NominationInterface::postesVacants()` |

---

## 5. API V1

Tout : `auth:sanctum` + `permission:consulter-reporting`.

```
GET /api/reporting/dashboard
GET /api/reporting/effectifs          ?direction_id&service_id&bureau_id&statut&per_page
GET /api/reporting/repartitions       ?axe=direction|grade|genre|age|statut|type_integration|fonction
GET /api/reporting/stats/conges       ?annee
GET /api/reporting/stats/evaluations  ?annee
GET /api/reporting/alertes
GET /api/reporting/exports/{type}     ?format=pdf|csv&annee&portee=annee|session&session_id
```

OpenAPI : tag `Reporting`. `operationId` préfixe `reporting`.

---

## 6. Couches

Pas de migration, pas de modèle métier.

```
ReportingInterface → ReportingRepository → binding
    → ReportingService
    → ReportingExportService
    → FilterRequest / RepartitionRequest / ExportRequest
    → ReportingEffectifResource
    → ReportingController
    → routes // ── Reporting D.6 ──
```

`StatutAgent::effectifPresent()` pour la population.

---

## 7. Hors scope V1

- Cloisonnement automatique « ma structure » (Vague F)
- GPEEC (prévision retraite, organigramme prévisionnel)
- Export paie (reste `/paie`)
- Stats formation / discipline
- Historisation / snapshots mensuels
- Excel `.xlsx`
- Mail / SMS, nouvelles notifications
- `gerer-reporting`
- Situation familiale / photo / RIB dans « dossier incomplet »

---

## 8. Tests Feature

| Test | Scénario |
|------|----------|
| `ReportingTest` | 401 / 403 ; `rh` et `directeur-general` 200 ; agent 403 |
| Dashboard | Effectif présent = actif+stagiaire+suspendu ; retraite exclue ; masse `null` sans lot |
| Répartition | Axes valides ; axe inconnu 422 ; genre / type_integration / fonction |
| Stats | Congés / évaluations par année |
| Alertes | Compteurs ; dossier incomplet |
| Export | CSV/PDF 200 ; type / format invalide 422 |

---

## 9. Impacts transverses

| Zone | Action |
|------|--------|
| `AppServiceProvider::register()` | `ReportingInterface` |
| `routes/api.php` | Section `// ── Reporting D.6 ──` |
| `RoleSeeder` | `consulter-reporting` sur `directeur-general` |
| `PermissionSeeder` | inchangé |
| `resources/views/pdf/reporting-*.blade.php` | nouveaux |
| `note-fe-etat-implementations.md` | §2i |
| `note-fe-roles-comptes.md` | DG voit le reporting |
| Endpoints métier cités § 2 | **aucun** rename / retrait |
