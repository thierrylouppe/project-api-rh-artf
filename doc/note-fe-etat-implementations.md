# Note FE — état des implémentations API

> Document **vivant** : à mettre à jour à chaque livraison API qui impacte le front.  
> Objectif : un seul point d’entrée pour les échanges FE (quoi appeler, quoi ne plus attendre, où lire le détail).  
> Dernière mise à jour : **2026-09-01**

Détail métier / contrats : les notes liées ci-dessous. **Ce fichier reste résumé.**

| Sujet | Fichier |
|-------|---------|
| Suivi implémentation (vagues A–D) | [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md) |
| Auth, rôles, menus, comptes démo | [`note-fe-roles-comptes.md`](./note-fe-roles-comptes.md) |
| Routes carrière, lots, checklist 14/15 | [`note-fe-routes-carriere.md`](./note-fe-routes-carriere.md) |
| Workflow intégration par type | [`workflow-integration-par-type.md`](./workflow-integration-par-type.md) |
| Swagger / Try it out | [`swagger-front.md`](./swagger-front.md) |
| Maquettes | [`maquettes/README.md`](./maquettes/README.md) |

---

## 1. Conventions (inchangées)

- Préfixe : `/api`
- Auth : `Authorization: Bearer {token}` (Sanctum)
- Login : `POST /login` → `data.token` + `data.user.roles[].permissions[].name`
- Succès : `{ "data": …, "message": "…" }`
- Validation : `422` + `errors` par champ
- Permission manquante : `403` → masquer l’action, ne pas la proposer
- Source de vérité des endpoints : Swagger `/api/documentation`

Menus : **permissions**, pas le nom du rôle. Voir la note rôles.

---

## 2. Carte des modules (orientation écrans)

| Zone FE | Préfixe | Statut API | Orientation |
|---------|---------|------------|-------------|
| Auth / users / rôles | `/login`, `/user`, `/users`, `/roles` | **Livré** | Guards via permissions. Rôle `rh` = seul métier RH (hors `admin`). |
| Structure org. | `/localites` … `/bureaux` | **Livré** | Hiérarchie Localité → Administration → Direction → Service → Bureau. `byParent` pour les selects. |
| Référentiels | `/diplomes`, `/grades`, `/types-integrations`, etc. | **Livré** | Listes pour formulaires. Circuit configurable : `GET/PUT /types-integrations/{id}/circuit`. |
| Intégration (entrée) | `/integration/…` | **Livré** | Dossier + documents + circuit + acte + compte + matériel + prise de service + stages. **Pas** affectation/nomination ici (carrière). |
| Personnel | `/personnel/agents`, `/personnel/stagiaires` | **Livré** | Listes post-intégration (intégrés vs stagiaires). Fiche détail agent d’intégration : `GET /integration/agents/{id}`. |
| Carrière | `/carriere/…` | **Livré** | Affectations, nominations, contrats, salaires agent, synthèse. Alias `/integration/…` encore OK **sauf** `GET /carriere/agents/{id}`. |
| Grille / salaires | `/grille-classes`, `/salaires`, `/salaires-agents` | **Livré** | Permissions `consulter-salaires` / `gerer-salaires`. |
| Congés / absences | — | **Pas livré** | Référentiels `types-conges` / `types-absences` seulement. Pas de demandes ni validations. |
| Évaluations | — | **Pas livré** | — |
| Reporting / dashboard | — | **Pas livré** | Permission `consulter-reporting` seedée, pas d’API. |
| Inbox notifications | `/notifications` | **Livré** | Inbox utilisateur (`auth:sanctum`). Voir §2b. |

---

## 2b. Notifications — cloche

Préfixe : **`/api/notifications`** (`Authorization: Bearer` requis, pas de permission dédiée).

| Méthode | URI | Usage FE |
|---------|-----|----------|
| `GET` | `/notifications` | Inbox paginée. Query optionnelle : `non_lues=1`, `per_page`, `page`. |
| `GET` | `/notifications/non-lues` | Badge / liste courte. |
| `POST` | `/notifications/{id}/lu` | `{id}` = UUID. 404 si ce n’est pas la notif de l’utilisateur. |
| `POST` | `/notifications/tout-lire` | Marque tout comme lu. |

Forme `GET /notifications` :

```json
{
  "data": [
    {
      "id": "uuid",
      "type": "RhEvenementNotification",
      "domaine": "integration",
      "action": "validee_rh",
      "message": "…",
      "data": { "domaine": "integration", "action": "validee_rh", "message": "…", "dossier_id": 1 },
      "lu": false,
      "read_at": null,
      "created_at": "…"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1, "non_lues": 1 },
  "message": "Notifications récupérées"
}
```

`domaine` utile pour le routage d’écran : `integration`, `affectation`, `nomination`, `lot_affectation`, `lot_nomination`, `compte`, `prise_de_service`, `stage`.

---

## 3. Intégration — à retenir pour le wizard

Deux chemins API ; le FE actuel utilise **B**.

| | A — séquentiel | B — post-`integrer` (FE) |
|---|----------------|---------------------------|
| Après `VALIDE_DG` | acte → [contrat signé] → matricule → … → `integrer` | `POST …/dossiers/{id}/integrer` tout de suite → `INTEGRE` |
| Statut dossier | avance (`ACTE_GENERE`, etc.) | reste `INTEGRE` pour acte / matricule / contrat signé |

À faire côté FE :

- Flags `TypeIntegration` (`necessite_contrat`, `necessite_validation_dg`, `necessite_compte_utilisateur`, `estUnStage`) pour afficher/masquer les étapes.
- Checklist : `GET /integration/dossiers/{id}/taches-post-integration`. Compter uniquement `obligatoire === true`.
- Étapes **14** (affectation) et **15** (nomination) : **optionnelles**, liens vers écrans carrière (`agent_id` prérempli). `INTEGRE` **ne dépend plus** d’une affectation ni d’une nomination.
- Ne plus attendre `AFFECTE` / `NOMME` sur le dossier après activation carrière.

Détail : [`workflow-integration-par-type.md`](./workflow-integration-par-type.md).

---

## 4. Carrière — à retenir pour les écrans

Préfixe canonique : **`/api/carriere`**. Basculer progressivement ; prévenir l’API quand c’est fait (retrait des alias).

- Unitaire **et** groupé (lot) : un circuit, un acte PDF pour le lot.
- Ligne d’un lot : pas d’activer / rejeter / PUT isolé.
- Activation : body `dossier_integration_id` encore **accepté mais ignoré** (le dossier ne change pas de statut).
- Statuts minuscules : `en_attente`, `approuvee`, `active`, `cloturee`, `rejetee` + `statut_label`.
- Synthèse : `GET /carriere/agents/{id}` (identité + contrat / affectation / nomination / salaire actuel). **Pas d’alias** `/integration`.
- Listes métier : `GET /personnel/agents` (intégrés) vs `GET /integration/agents` (tous les dossiers).

Détail : [`note-fe-routes-carriere.md`](./note-fe-routes-carriere.md). Maquettes affectation / nomination dans `doc/maquettes/`.

---

## 5. Permissions utiles (menus)

| Besoin écran | Permission |
|--------------|------------|
| Recrutement / intégration | `consulter-recrutement`, `creer-recrutement`, `valider-recrutement` |
| Contrats | `consulter-contrats`, `creer-contrats`, `modifier-contrats` |
| Nominations (menus) | `consulter-nominations`, `gerer-nominations` — **pas encore** de middleware `permission:` sur les routes nomination |
| Salaires | `consulter-salaires`, `gerer-salaires` (routes protégées) |
| Users | `consulter-utilisateurs`, `creer-utilisateurs`, `modifier-utilisateurs` |
| Rôles | `consulter-roles`, `creer-roles`, `modifier-roles` |

Hiérarchie (`directeur`, `chef-service`, …) : **pas** de menus salaires / contrats / recrutement / reporting. Périmètre « ma structure seulement » : **pas encore** filtré côté API.

---

## 6. Hors périmètre actuel (ne pas concevoir d’écrans API)

- Demandes de congés / absences / soldes
- Campagnes et fiches d’évaluation
- Catalogue formations, discipline, GED hors documents d’intégration
- Dashboard / exports reporting
- Mail / SMS (canal `database` uniquement pour l’instant)

---

## 7. Journal (mettre à jour ici)

Format : date · quoi · impact FE (1 ligne).

| Date | Implémentation | Impact FE |
|------|----------------|-----------|
| 2026-09-01 | Inbox `/notifications` + événements intégration / affectation / stage | Brancher la cloche : liste, badge `meta.non_lues`, marquer lu |
| 2026-08-25 | Création de ce fichier | Point d’entrée unique pour les échanges |
| 2026-08-23 | Préfixe `/carriere`, lots affectation/nomination, checklist 14/15 optionnelles | Pointer vers `/carriere` ; ne plus bloquer le wizard sur 14 |
| 2026-08-16 | Rôles globaux, `rh` seul métier RH, comptes démo | Menus par permissions ; 7 comptes seeder |

---

## 8. Comment maintenir ce fichier (backend)

À chaque PR / livraison qui touche le contrat HTTP :

1. Une ligne dans le **journal** (§7).
2. Mettre à jour le **statut** du module concerné (§2) si besoin.
3. Si le contrat change : 3–10 lignes max ici + renvoyer vers une note détaillée (nouvelle ou existante).
4. Si breaking : le dire explicitement (alias, champ, statut dossier).
5. Régénérer Swagger (`php artisan l5-swagger:generate`) et le mentionner dans le journal si les tags/chemins changent.
