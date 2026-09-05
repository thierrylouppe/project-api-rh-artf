# Note FE — état des implémentations API

> Document **vivant** : à mettre à jour à chaque livraison API qui impacte le front.  
> Objectif : un seul point d’entrée pour les échanges FE (quoi appeler, quoi ne plus attendre, où lire le détail).  
> Dernière mise à jour : **2026-09-05**

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
| Référentiels | `/diplomes`, `/grades`, `/types-integrations`, etc. | **Livré** | Listes pour formulaires. Circuit configurable : `GET/PUT /types-integrations/{id}/circuit`. `GET /diplomes` : chaque item porte `classe_grille` (catégorie, grade, **échelon de départ**). **Pas** de `fonction_id` (nomination). Pré-remplissage UX, champs toujours modifiables. |
| Intégration (entrée) | `/integration/…` | **Livré** | Dossier + documents + circuit + acte + compte + matériel + prise de service + stages. **Pas** affectation/nomination ici (carrière). |
| Personnel | `/personnel/…` | **Livré** | Listes + **fiche vie courante** (infos, contacts, GED, archivage). §2d. Fiche wizard : `GET /integration/agents/{id}`. |
| Carrière | `/carriere/…` | **Livré** | Affectations, nominations, contrats, salaires agent, synthèse. Alias `/integration/…` encore OK **sauf** `GET /carriere/agents/{id}`. |
| Grille / salaires | `/grille-classes`, `/salaires`, `/salaires-agents` | **Livré** | Permissions `consulter-salaires` / `gerer-salaires`. |
| Congés / absences | `/conges/…`, `/absences` | **Livré** | Circuit **par type** (N+1 / RH / DG), soldes, justificatif, PDF. Contrat FE : §2c. |
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

`domaine` utile pour le routage d’écran : `integration`, `affectation`, `nomination`, `lot_affectation`, `lot_nomination`, `compte`, `prise_de_service`, `stage`, `conge`, `absence`.

---

## 2c. Congés & absences — contrat FE

Préfixes : **`/api/conges`**, **`/api/absences`**. Auth Bearer obligatoire. Listes **non paginées**. Pas de filtre « ma structure seulement » (V1).

### Écrans recommandés

| Écran | Qui | APIs |
|-------|-----|------|
| Mes demandes | `agent` (`creer-conges`) | `GET /conges/agents/{agent_id}/demandes` · `POST /conges/demandes` · soldes |
| File N+1 | chef avec `valider-conges` | `GET /conges/demandes?statut=soumise` puis bouton si `prochaine_etape === "valider-n1"` |
| File RH | rôle `rh` | `GET /conges/demandes?statut=soumise` et `?statut=validee_n1` · `prochaine_etape === "valider-rh"` |
| File DG | rôle `directeur-general` | `GET /conges/demandes?statut=validee_rh` · `prochaine_etape === "valider-dg"` |
| Paramétrage | `rh` / `admin` | types, jours fériés, règles d’acquisition |
| Absences | créer : `creer-absences` ; valider : `valider-absences` (**pas** les chefs de service/bureau en seeder) | `/absences` |

`agent_id` du connecté : `GET /user` → `data.agent_id` (peut être `null` pour un compte RH/DG non lié à un agent).

### Permissions (menus / guards)

| Permission | Usage |
|------------|--------|
| `consulter-conges` | Listes, détail, soldes, PDF, stats, fériés, règles |
| `creer-conges` | `POST /conges/demandes` — seeder : `agent`, `rh`, `admin` |
| `valider-conges` | Accès **routes** de workflow. **Insuffisant** pour signer : l’API vérifie N+1 / rôle `rh` / rôle `directeur-general` (sinon **403**) |
| `consulter-absences` / `creer-absences` / `valider-absences` | Idem absences. Seeder : `chef-service` et `chef-bureau` n’ont **pas** `valider-absences` |

Rôles : [`note-fe-roles-comptes.md`](./note-fe-roles-comptes.md). Comptes démo : `agent@arft.cg`, `rh@arft.cg`, `dg@arft.cg`, `chef-service@arft.cg`.

### Types de congé (pilote le formulaire et le workflow)

`GET /types-conges` (référentiel existant, champs **ajoutés**, non breaking).

```json
{
  "id": 1,
  "nom": "Congé annuel",
  "jours_max": 30,
  "necessite_n1": true,
  "necessite_rh": true,
  "necessite_dg": false,
  "debite_solde": true,
  "justificatif_requis": false
}
```

| Flag | Comportement FE |
|------|-----------------|
| `justificatif_requis` | Champ fichier obligatoire. `POST` en **`multipart/form-data`** (pas JSON). |
| `debite_solde` | Afficher le solde ; l’API refuse (422) si insuffisant. Sinon ne pas bloquer sur le solde. |
| `jours_max` | Plafond indicatif. `0` (ex. maladie) = pas de plafond côté type. |
| `necessite_*` | Ne pas afficher les boutons d’étapes inutiles. Source de vérité runtime : `data.prochaine_etape`. |

Seed (noms exacts) :

| Type | Circuit | Solde | Justificatif |
|------|---------|-------|--------------|
| Congé annuel | N+1 → RH | oui | non |
| Maternité, paternité, exceptionnels (décès / mariage), maladie | RH seule | non | oui |
| Sans solde, sabbatique | N+1 → RH → DG | non | oui |

CRUD flags : `POST/PUT /types-conges` (mêmes champs boolean). Règle annuelle : `GET/POST /conges/regles-acquisition` (`type_conge_id`, `jours_par_mois` 2.5, `jours_max` 30).

### Soumettre une demande

`POST /conges/demandes` — permission `creer-conges`.

JSON (sans fichier) :

```json
{
  "agent_id": 12,
  "type_conge_id": 1,
  "date_debut": "2026-09-07",
  "date_fin": "2026-09-11",
  "motif": "optionnel"
}
```

Avec justificatif : `FormData` — mêmes champs + `justificatif` (fichier, max 10 Mo). Ne pas forcer `Content-Type: application/json`.

L’API calcule `nb_jours` (week-ends + fériés exclus). **Ne pas** envoyer `nb_jours` / `statut`. Période sans jour ouvrable → **422** (`message`). Chevauchement avec une demande encore ouverte → **422**.

Pas de PUT/PATCH ni d’annulation après soumission.

### Réponse demande

```json
{
  "id": 1,
  "agent_id": 12,
  "agent": { },
  "type_conge_id": 1,
  "type_conge": { "necessite_n1": true, "necessite_rh": true, "necessite_dg": false, "debite_solde": true, "justificatif_requis": false },
  "date_debut": "2026-09-07",
  "date_fin": "2026-09-11",
  "nb_jours": 4,
  "motif": "…",
  "statut": "soumise",
  "statut_label": "Soumise",
  "commentaire_n1": null,
  "commentaire_rh": null,
  "commentaire_dg": null,
  "date_validation_n1": null,
  "date_validation_rh": null,
  "date_validation_dg": null,
  "prochaine_etape": "valider-n1",
  "justificatif": null
}
```

`justificatif` : `{ "nom": "certificat.pdf" }` ou `null` (pas d’URL de téléchargement du fichier).

`prochaine_etape` : `"valider-n1"` | `"valider-rh"` | `"valider-dg"` | `null` (terminée ou rejetée). **Afficher uniquement le bouton correspondant.**

Statuts `statut` (snake_case) :

| Valeur | Label |
|--------|--------|
| `soumise` | Soumise |
| `validee_n1` / `rejetee_n1` | Validée / Rejetée N+1 |
| `validee_rh` / `rejetee_rh` | Validée / Rejetée RH |
| `validee_dg` / `rejetee_dg` | Validée / Rejetée DG |

Filtres liste : `GET /conges/demandes?agent_id=&type_conge_id=&statut=` (égalité exacte). Détail : `GET /conges/demandes/{id}`. Par agent : `GET /conges/agents/{id}/demandes`.

### Qui clique quoi (important)

`valider-conges` ouvre la route ; le **métier** décide ensuite :

| `prochaine_etape` | Qui peut signer | Comment le FE le sait | Sinon |
|-------------------|-----------------|------------------------|--------|
| `valider-n1` | Compte dont `agent_id` = `superieur_hierarchique_id` de l’**affectation active** du demandeur (ou rôle `admin`) | `GET /carriere/agents/{demande.agent_id}` → `affectation_active.superieur_hierarchique_id === user.agent_id` | **403** (mauvais utilisateur) · **422** si pas d’affectation active / pas de supérieur / supérieur sans compte |
| `valider-rh` | Rôle `rh` ou `admin` | `user.roles[].name` | **403** (un chef ne signe pas la RH) |
| `valider-dg` | Rôle `directeur-general` ou `admin` | idem | **403** |

Un RH **ne peut pas** signer le N+1 (403). Ne pas proposer les 3 boutons à tout le monde.

Valider : `POST /conges/demandes/{id}/valider-n1` (body optionnel `{ "commentaire": "…" }`). Idem `valider-rh`, `valider-dg`.

Rejeter : `POST …/rejeter-n1` · `rejeter-rh` · `rejeter-dg` — body **obligatoire** `{ "commentaire": "…" }` (min. 3 caractères) sinon **422** `errors.commentaire`.

Mauvaise étape (ex. `valider-rh` alors que `prochaine_etape` est `valider-n1`) → **422** (`message`).

### Soldes

- `GET /conges/agents/{id}/soldes?annee=2026`
- `GET /conges/soldes` (tous)

```json
{ "id": 1, "agent_id": 12, "type_conge_id": 1, "type_conge": { }, "annee": 2026, "solde_initial": 30, "solde_actuel": 27 }
```

Le solde n’existe qu’après une première demande qui `debite_solde` (création paresseuse). Liste vide = normal. Débit **uniquement à la validation finale** si `debite_solde`.

### PDF

| URI | Quand |
|-----|--------|
| `GET /conges/demandes/{id}/fiche-pdf` | Dès la soumission |
| `GET /conges/demandes/{id}/attestation` | Seulement si le circuit du type est **terminé validé** (`prochaine_etape === null` et statut `validee_rh` ou `validee_dg` selon le type). Sinon **422** |

Réponse **binaire** `application/pdf` (pas JSON). Appeler avec le Bearer, `blob` / download. Ne pas parser en JSON.

### Jours fériés (paramétrage RH)

| | |
|--|--|
| Liste | `GET /conges/jours-feries` |
| Créer | `POST` `{ "nom", "date": "YYYY-MM-DD", "recurrent": true }` |
| Modifier / supprimer | `PUT` / `DELETE /conges/jours-feries/{id}` — permission `valider-conges` |

`recurrent: true` : la date (mois/jour) se répète chaque année dans le calcul des jours ouvrables.

### Stats

`GET /conges/statistiques` → `{ "total", "par_statut": { "soumise": n, … }, "jours_accordes": n }` (`jours_accordes` = demandes dont le circuit est **entièrement** validé).

### Absences (circuit unique)

Types : `GET /types-absences` → `justification_requise` (si true, `motif` obligatoire à la création). Seed : permission d’absence, maladie, formation, mission, syndicale, retard, disponibilité, non justifiée.

| | |
|--|--|
| Liste | `GET /absences?agent_id=&type_absence_id=&statut=&justifiee=` |
| Par agent | `GET /absences/agents/{id}` |
| Créer | `POST /absences` `{ "agent_id", "type_absence_id", "date_debut", "date_fin", "motif?" }` |
| Valider / rejeter | `POST /absences/{id}/valider` · `POST /absences/{id}/rejeter` (`commentaire` obligatoire au rejet) |

Statuts : `en_attente` · `validee` · `rejetee`. `nb_jours` calculé comme pour les congés. **Pas de N+1/RH/DG** : toute personne avec `valider-absences` peut valider.

### Notifications (cloche)

`domaine` : `conge` ou `absence`. Meta : `demande_id` / `absence_id`, `agent_id`.

Actions congé : `soumise`, `validee_n1`, `rejetee_n1`, `validee_rh`, `rejetee_rh`, `validee_dg`, `rejetee_dg`. Absence : `declaree`. Routage : fiche demande / absence.

### Erreurs à gérer

| Code | Cas |
|------|-----|
| 401 | Token manquant |
| 403 | Permission route **ou** mauvais signataire (N+1 / RH / DG) — `message` |
| 404 | Id inconnu |
| 422 validation | `errors` par champ (dates, fichier, `commentaire` rejet) |
| 422 métier | `message` seul (solde, chevauchement, 0 jour ouvrable, mauvaise étape, pas d’affectation N+1, attestation trop tôt) |

### Hors V1 (ne pas concevoir)

- Inbox API « mes validations uniquement » (filtrer côté FE avec `prochaine_etape` + rôle / `agent_id`)
- Édition / retrait d’une demande
- Téléchargement du justificatif
- Mail / SMS

---

## 2d. Dossier agent (vie courante)

Préfixe **`/api/personnel`**. Auth Bearer. Pas de permission dédiée (comme le reste de `/personnel` aujourd’hui). `GET /integration/agents/{id}` reste la fiche **wizard**.

| Action | Méthode | URI |
|--------|---------|-----|
| Fiche complète | `GET` | `/personnel/agents/{id}` (infos, contacts, situation, documents) |
| Infos perso / pro / famille | `GET` + `PUT` upsert | `…/informations-personnelles` · `…/informations-professionnelles` · `…/situation-familiale` (`data: null` si vide) |
| Contacts urgence | `GET/POST` · `PUT/DELETE …/{id}` | `…/contacts-urgence` |
| Documents | `GET/POST` · `GET …/{id}/fichier` (blob) · `DELETE` (soft) | `…/documents` · `…/documents/arborescence` |
| Archives | `POST …/archiver` `{ "motif" }` · `POST …/desarchiver` | Liste : `GET /personnel/agents?statut=archive` (hors liste par défaut) |

`PUT` perso : `adresse`, `quartier`, `ville`, `code_postal`, `pays`. Pro : `diplome_id`, `niveau_etude`, `specialite`, `annees_experience`, `etablissement`. Famille : `statut_matrimonial` (`celibataire` \| `marie` \| `divorce` \| `veuf` \| `union_libre`), `nb_enfants`. Documents : multipart `type_document_id`, `fichier`, `titre?`, `sous_dossier?` (défaut `general`). Types : `GET /types-documents`.

Archivage : `statut=archive`, compte utilisateur `is_active=false`, écritures dossier **422**. Désarchivage → `inactif` + compte réactivé. Stagiaire : pas d’archivage ici (module stage).

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
| Congés | `consulter-conges`, `creer-conges`, `valider-conges` — les boutons N+1/RH/DG se jouent **en plus** sur le rôle / le supérieur (§2c) |
| Absences | `consulter-absences`, `creer-absences`, `valider-absences` (`valider-absences` : pas les chefs en seeder) |
| Users | `consulter-utilisateurs`, `creer-utilisateurs`, `modifier-utilisateurs` |
| Rôles | `consulter-roles`, `creer-roles`, `modifier-roles` |

Hiérarchie (`directeur`, `chef-service`, …) : **pas** de menus salaires / contrats / recrutement / reporting. Périmètre « ma structure seulement » : **pas encore** filtré côté API.

---

## 6. Hors périmètre actuel (ne pas concevoir d’écrans API)

- Campagnes et fiches d’évaluation
- Catalogue formations, discipline, GED **versioning / recherche** (la GED agent légère est livrée, §2d)
- Dashboard / exports reporting
- Mail / SMS (canal `database` uniquement pour l’instant)

---

## 7. Journal (mettre à jour ici)

Format : date · quoi · impact FE (1 ligne).

| Date | Implémentation | Impact FE |
|------|----------------|-----------|
| 2026-09-05 | `GET /diplomes` : `classe_grille` sur chaque item + `echelon(_id)` | Auto-remplir `categorie_id` / `grade_id` / `echelon_id` au choix du diplôme. Pas de `fonction`. Nullable si pas de classe. |
| 2026-09-01 | Vague B dossier agent (`/personnel/agents/{id}` …) | Fiche vie courante, upsert infos, GED, archivage |
| 2026-09-01 | Module congés / absences (`/conges`, `/absences`) | Écrans demandes, soldes, workflow, PDF |
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
