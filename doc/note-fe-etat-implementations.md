# Note FE — état des implémentations API

> Document **vivant** : à mettre à jour à chaque livraison API qui impacte le front.  
> Objectif : un seul point d’entrée pour les échanges FE (quoi appeler, quoi ne plus attendre, où lire le détail).  
> Dernière mise à jour : **2026-09-16**

Détail métier / contrats : les notes liées ci-dessous. **Ce fichier reste résumé.**

| Sujet | Fichier |
|-------|---------|
| Suivi implémentation (vagues A–D + F) | [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md) — D.4 livré ; prochain **Vague E** (CCN 3–5) puis D.5 ; cloisonnement **plus tard** |
| Conformité CCN intégration / carrière / grille | [`plan-conformite-ccn-modules-3-4-5.md`](./plan-conformite-ccn-modules-3-4-5.md) — **lot A livré** (essai + 30 j) ; lots B–E à venir |
| Restes évaluation | [`plan-evaluation-complements.md`](./plan-evaluation-complements.md) — **lots A–D livrés** |
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
| Carrière | `/carriere/…` | **Livré** | Affectations, nominations, contrats, salaires agent, synthèse, **reclassements art. 73–75**. Alias `/integration/…` encore OK **sauf** `GET /carriere/agents/{id}`. |
| Grille / salaires | `/grille-classes`, `/salaires`, `/salaires-agents` | **Livré** | `consulter-salaires` / `gerer-salaires`. Historique : `type_changement` peut valoir `reclassement`, `hors_classe`, `reconversion` (art. 73–75). |
| Congés / absences | `/conges/…`, `/absences` | **Livré** | Circuit **par type** (N+1 / RH / DG), soldes, justificatif, PDF. Contrat FE : §2c. |
| Évaluations | `/avancements/…` | **Livré** | P1–P5 + lots A–C (art. 62, tableau D5, PDF). Contrat : §7b. Reclassement de **classe** : §4 (`/carriere/reclassements`), pas ici. |
| Discipline | `/discipline` | **Livré** | Vague D.2 + CCN art. 89–91 — contrat §2e. Menus : `consulter-discipline` (RH/DG), `proposer-discipline` (N+1), `prononcer-discipline` (DG). |
| Affaires sociales | `/affaires-sociales` | **Livré (P1)** | Vague D.3.1–D.3.3. Contrat §2f. Rôle `rh` global (pas de menu par bureau). Prestations / santé **pas** livrés. |
| Formation | `/formations` | **Livré** | Vague D.4. Contrat §2g. Stages d’accueil restent `/integration/stages`. Conversion : `POST /integration/stages/{id}/convertir-agent`. |
| Paie (lots / éléments) | `/paie` | **Pas livré** | Vague D.5. Grille + salaire indiciaire **déjà** sous `/salaires-agents`. |
| Reporting / dashboard | `/reporting` | **Pas livré** | Vague D.6. Permission `consulter-reporting` seedée, pas d’API. |
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

`domaine` utile pour le routage d’écran : `integration`, `affectation`, `nomination`, `lot_affectation`, `lot_nomination`, `compte`, `prise_de_service`, `stage`, `conge`, `absence`, `discipline`.

---

## 2c. Congés & absences — contrat FE

Préfixes : **`/api/conges`**, **`/api/absences`**. Auth Bearer obligatoire. Listes **non paginées**. Pas de filtre « ma structure seulement » (V1).

### Écrans recommandés

| Écran | Qui | APIs |
|-------|-----|------|
| Mes demandes | `agent` (`creer-conges`) | `GET /conges/agents/{agent_id}/demandes` · `POST /conges/demandes` · soldes |
| File à valider | `valider-conges` | **`GET /conges/demandes/a-valider`** — uniquement les dossiers que le user peut signer (N+1 / RH / DG). `admin` voit toute la file. |
| Paramétrage | `rh` / `admin` | types, jours fériés, règles d’acquisition |
| Absences | `creer-absences` · file **`GET /absences/a-valider`** · valider = **N+1** (même règle que les congés). Chefs ont `valider-absences`. |

`agent_id` du connecté : `GET /user` → `data.agent_id` (peut être `null` pour un compte RH/DG non lié à un agent).

### Permissions (menus / guards)

| Permission | Usage |
|------------|--------|
| `consulter-conges` | Listes, détail, soldes, PDF, stats, fériés, règles |
| `creer-conges` | `POST /conges/demandes` · `POST …/annuler` — seeder : `agent`, `rh`, `admin` |
| `valider-conges` | Accès **routes** de workflow. **Insuffisant** pour signer : l’API vérifie N+1 / rôle `rh` / rôle `directeur-general` (sinon **403**) |
| `consulter-absences` / `creer-absences` / `valider-absences` | Absences. `valider-absences` : chefs + RH + DG. **Signer** = N+1 (ou `admin`), sinon **403** |

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

Seed (noms exacts — conformes CCN ARTF art. 77) :

| Type | `jours_max` | Circuit | Solde | Justificatif |
|------|-------------|---------|-------|--------------|
| Congé annuel | 30 | N+1 → RH | oui | non |
| Congé de maternité | **105** (15 sem.) | RH | non | oui |
| Congé de paternité | **2** | RH | non | oui |
| Congé exceptionnel — mariage du salarié | 5 | RH | non | oui |
| Congé exceptionnel — mariage d'un enfant | 2 | RH | non | oui |
| Congé exceptionnel — baptême d'un enfant | 1 | RH | non | oui |
| Congé exceptionnel — déménagement | 2 | RH | non | non |
| Congé exceptionnel — décès du conjoint | **10** | RH | non | oui |
| Congé exceptionnel — décès d'un parent (père, mère, frère, sœur, enfant) | 5 | RH | non | oui |
| Congé exceptionnel — retrait de deuil | 2 | RH | non | non |
| Congé exceptionnel — construction de la pierre tombale | 2 | RH | non | non |
| Congé maladie — ascendants | 4 | RH | non | oui |
| Congé maladie — conjoint | 7 | RH | non | oui |
| Congé maladie — 1 enfant à charge | 5 | RH | non | oui |
| Congé maladie — 2 enfants à charge | 9 | RH | non | oui |
| Congé maladie — 3 enfants et plus à charge | 12 | RH | non | oui |
| Congé maladie | 0 (illimité) | RH | non | oui |
| Congé pour convenances personnelles | 180 (6 mois) | N+1 → RH | non | non |
| Congé pour concours | 1 | RH | non | oui |
| Congé d'éducation et formation syndicale | 0 (variable) | RH | non | oui |
| Congé sans solde | 90 | N+1 → RH → DG | non | oui |
| Congé sabbatique | 180 | N+1 → RH → DG | non | oui |

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

L’API calcule `nb_jours` (week-ends + fériés exclus). **Ne pas** envoyer `nb_jours` / `statut`. Période sans jour ouvrable → **422** (`message`). Chevauchement avec une demande **ouverte ou accordée** (y compris `validee_dg`) **ou une absence** `en_attente` / `validee` → **422**. Idem à la création d’une absence.

Pas de PUT/PATCH après soumission. **Annulation** : `POST /conges/demandes/{id}/annuler` tant que `statut=soumise` (demandeur / `created_by` / `admin`). Ensuite **422**.

**Validations CCN ARTF supplémentaires (422 métier) :**

| Règle | Déclencheur | Message API |
|-------|------------|-------------|
| Congé annuel — 12 mois de service requis | `date_prise_service` absente **ou** ancienneté < 12 mois au départ | `"Le congé annuel est acquis après 12 mois de service effectif…"` |
| Convenances personnelles — min 15 jours | `nb_jours < 15` | `"Le congé pour convenances personnelles ne peut être inférieur à 15 jours ouvrables…"` |

### Réponse demande

```json
{
  "id": 1,
  "agent_id": 12,
  "agent": { "id": 12, "matricule": null, "nom": "Agent", "prenom": "Jean", "nom_complet": "Jean Agent" },
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

`justificatif` : `{ "nom": "certificat.pdf", "url": "/api/conges/demandes/{id}/justificatif" }` ou `null`. Téléchargement : `GET` cette URL (blob + Bearer).

`prochaine_etape` : `"valider-n1"` | `"valider-rh"` | `"valider-dg"` | `null` (terminée ou rejetée). **Afficher uniquement le bouton correspondant.**

Statuts `statut` (snake_case) :

| Valeur | Label |
|--------|--------|
| `soumise` | Soumise |
| `annulee` | Annulée |
| `validee_n1` / `rejetee_n1` | Validée / Rejetée N+1 |
| `validee_rh` / `rejetee_rh` | Validée / Rejetée RH |
| `validee_dg` / `rejetee_dg` | Validée / Rejetée DG |

Filtres liste : `GET /conges/demandes?agent_id=&type_conge_id=&statut=` (égalité exacte). File signataire : `GET /conges/demandes/a-valider` (`valider-conges`). Détail : `GET /conges/demandes/{id}`. Par agent : `GET /conges/agents/{id}/demandes`.

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
{ "id": 1, "agent_id": 12, "type_conge_id": 1, "type_conge": { }, "annee": 2026, "solde_initial": 36, "solde_actuel": 33, "jours_anciennete": 6 }
```

Le solde des types `debite_solde` est **créé à la lecture** (`GET …/soldes?annee=`). Liste vide uniquement s’il n’existe aucun type à débit. Débit **uniquement à la validation finale**.

`solde_initial` = base (2,5 × 12 plafonné) **+** `jours_anciennete`. L’ancienneté = années révolues au **1er janvier** de l’année du solde, depuis `date_prise_service`. Sans date → bonus 0. Un solde déjà créé **n’est pas recalculé**.

### Paliers d’ancienneté (paramétrage RH)

Même modèle que les fériés. Permission lecture : `consulter-conges` ; écriture : `valider-conges`.

| | |
|--|--|
| Liste | `GET /conges/paliers-anciennete` |
| Créer | `POST` `{ "anciennete_min": 5, "anciennete_max": 9, "jours_bonus": 6 }` |
| Modifier / supprimer | `PUT` / `DELETE /conges/paliers-anciennete/{id}` |

`anciennete_max` nullable = pas de plafond. Chevauchement de paliers → **422**.

**Seed CCN ARTF art. 77 (8 paliers) :**

| Tranche | Jours bonus (s'ajoutent à la base 30j) |
|---------|----------------------------------------|
| 0–4 ans | +0 |
| 5–9 ans | **+6** |
| 10–14 ans | **+8** |
| 15–19 ans | **+10** |
| 20–24 ans | **+12** |
| 25–29 ans | **+14** |
| 30–34 ans | **+16** |
| 35 ans et + | **+18** |

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

### Absences (N+1)

Types : `GET /types-absences` → `justification_requise` (si true, `motif` obligatoire à la création). Seed **(7 types)** : permission d’absence, maladie, formation, mission, syndicale, retard, non justifiée.

> ⚠️ **« Mise en disponibilité » retiré** de `type_absences` (CCN art. 79) — la disponibilité est désormais un `statut` agent (`disponibilite`), pas un type d’absence.

| | |
|--|--|
| Liste | `GET /absences?agent_id=&type_absence_id=&statut=&justifiee=` |
| File N+1 | `GET /absences/a-valider` |
| Par agent | `GET /absences/agents/{id}` |
| Créer | `POST /absences` `{ "agent_id", "type_absence_id", "date_debut", "date_fin", "motif?" }` |
| Valider / rejeter | `POST /absences/{id}/valider` · `POST /absences/{id}/rejeter` (`commentaire` obligatoire au rejet) — **N+1 seulement** (ou `admin`) |

Statuts : `en_attente` · `validee` · `rejetee`. Pas d’étape RH/DG. Un RH avec `valider-absences` reçoit **403** s’il n’est pas le supérieur.

### Notifications (cloche)

`domaine` : `conge` ou `absence`. Meta : `demande_id` / `absence_id`, `agent_id`.

Actions congé : `soumise`, `annulee`, `validee_n1`, `rejetee_n1`, `validee_rh`, `rejetee_rh`, `validee_dg`, `rejetee_dg`. Absence : `declaree`, `validee`, `rejetee`.

### Erreurs à gérer

| Code | Cas |
|------|-----|
| 401 | Token manquant |
| 403 | Permission route **ou** mauvais signataire (N+1 / RH / DG) — `message` |
| 404 | Id inconnu |
| 422 validation | `errors` par champ (dates, fichier, `commentaire` rejet) |
| 422 métier | `message` seul (solde, chevauchement, 0 jour ouvrable, mauvaise étape, pas d’affectation N+1, attestation trop tôt, **congé annuel < 12 mois de service**, **convenances personnelles < 15 jours**) |

### Hors V1 (ne pas concevoir)

- Édition d’une demande déjà soumise
- Mail / SMS
- Pagination, filtre « ma structure seulement »

---

## 2c-bis. Positions conventionnelles — `statut` agent (CCN art. 76–80)

Le champ `agent.statut` (renvoyé par `GET /personnel/agents/{id}` et les synthèses carrière) peut prendre les valeurs suivantes :

| Valeur `statut` | Label affiché | Art. CCN | Notes FE |
|-----------------|---------------|----------|----------|
| `actif` | Actif | Art. 77 | État normal |
| `stagiaire` | Stagiaire | Art. 77 | Module stage |
| `detachement` | En détachement | Art. 78 | Rémunération maintenue, avancement maintenu |
| `disponibilite` | En disponibilité | **Art. 79** 🆕 | Rémunération et avancement suspendus. Max 2 ans renouvelable 2 fois |
| `position_exceptionnelle` | Position exceptionnelle | Art. 80 | Cabinets ministériels — droits maintenus |
| `sous_le_drapeau` | Sous le drapeau | **Art. 80** 🆕 | Service national — régime des congés administratifs |
| `suspendu` | Suspendu | — | Décision disciplinaire |
| `inactif` | Inactif | — | |
| `retraite` | Retraité | — | |
| `archive` | Archivé | — | Voir §2d archivage |

**Modification** : `PUT /personnel/agents/{id}` — champ `statut`. Seul `rh` / `admin` peut modifier.
Valeurs modifiables : `actif`, `inactif`, `suspendu`, `retraite`, `detachement`, `position_exceptionnelle`, `disponibilite`, `sous_le_drapeau`.

**Impact évaluation** : agents en `detachement`, `disponibilite`, `position_exceptionnelle`, `sous_le_drapeau`, `stagiaire` → **exclus automatiquement** des sessions d'évaluation.

**Impact congés** : un agent doit avoir `date_prise_service` renseignée et ≥ 12 mois de service pour soumettre un congé annuel.

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

`PUT` perso : `adresse`, `quartier`, `ville`, `code_postal`, `pays`. Pro : `diplome_id`, `niveau_etude`, `specialite`, `annees_experience`, `etablissement`. Famille : `statut_matrimonial` (`celibataire` \| `marie` \| `divorce` \| `veuf` \| `union_libre`), `nb_enfants`. **D.3 :** si des enfants nominatifs existent (`/affaires-sociales/ayants-droit`), `nb_enfants` est **recalculé** (enfants `a_charge`) et la valeur POST est ignorée. Documents : multipart `type_document_id`, `fichier`, `titre?`, `sous_dossier?` (défaut `general`). Types : `GET /types-documents`.

Archivage : `statut=archive`, compte utilisateur `is_active=false`, écritures dossier **422**. Désarchivage → `inactif` + compte réactivé. Stagiaire : pas d’archivage ici (module stage).

---

## 2e. Discipline — contrat FE

Préfixe : **`/api/discipline`**. Auth Bearer obligatoire. Listes **non paginées**. Source CCN : art. **90–91**.

**Breaking (vague CCN) :** le DG **seul** prononce (`prononcer-discipline`). La RH n’a plus `POST …/valider` ni `…/rejeter`. `prochaine_etape` après instruction = **`prononcer`** (plus `valider`). Quatre types CCN seulement en actif (mutation / rétrogradation désactivés au seed). Une **pièce jointe** est obligatoire avant `instruire`. Reconnecter les comptes après seed (`proposer-discipline`, `prononcer-discipline`).

### Permissions

| Permission | Usage | Seeder |
|------------|--------|--------|
| `consulter-discipline` | Listes RH/DG, détail, historique agent | `rh`, `admin`, `directeur-general` |
| `gerer-discipline` | Types, instruire, avertissements, pièces (instruction) | `rh`, `admin` |
| `proposer-discipline` | Soumettre un rapport (N+1 de l’agent, ou RH pour tout agent) | `rh`, `admin`, `directeur`, `chef-service`, `chef-bureau` |
| `prononcer-discipline` | File à prononcer, prononcer / classer sans suite | `directeur-general`, `admin` |

Les chefs **n’ont pas** `consulter-discipline` (pas de menu RH global). Ils voient **leurs** rapports : `GET /discipline/sanctions/mes-rapports`.

L’**agent** n’a pas `consulter-discipline`. Self-service **sans permission dédiée** :

| Écran | Qui | APIs |
|-------|-----|------|
| Mes sanctions / historique | compte lié à un `agent_id` | **`GET /discipline/moi/historique`** · `GET /discipline/moi/sanctions` · `GET /discipline/moi/sanctions/{id}` |
| PDF décision | idem, après prononcé | **`GET /discipline/moi/sanctions/{id}/pdf-decision`** |
| Mes avertissements | idem | `GET /discipline/moi/avertissements` · `GET /discipline/moi/avertissements/{id}` |

`GET /user` → `data.agent_id` : si `null`, ces routes répondent **403**. Un dossier d’un autre agent : **404**. `notes_instruction` est **masqué** (réservé à `consulter-discipline` / `gerer-discipline`).

Deep-link notification (`sanction_id`) : agent → `GET /discipline/moi/sanctions/{id}` ; N+1 → `GET /discipline/sanctions/{id}` (ses rapports) ; RH/DG → même URL.

### Écrans recommandés

| Écran | Qui | APIs |
|-------|-----|------|
| Types de sanctions | `gerer-discipline` (écriture) · lecture aussi `proposer-discipline` | `GET/POST /discipline/types-sanctions` · `PUT/DELETE …/{id}` |
| Soumettre un rapport | `proposer-discipline` | **`POST /discipline/sanctions`** · `GET /discipline/sanctions/mes-rapports` |
| File à instruire | `gerer-discipline` | **`GET /discipline/sanctions/a-instruire`** (`en_attente`) |
| File à prononcer | `prononcer-discipline` | **`GET /discipline/sanctions/a-prononcer`** (`instruite`) — alias `GET …/a-valider` |
| Dossier | RH / DG / auteur | `GET/PUT /discipline/sanctions/{id}` · `POST …/{id}/instruire` · `POST …/{id}/valider` · `POST …/{id}/rejeter` |
| Pièces (art. 91) | auteur (avant instruction) ou RH | `GET/POST /discipline/sanctions/{id}/pieces` · `GET/DELETE …/pieces/{pieceId}` (multipart `fichier`) |
| PDF | RH / DG / auteur | `GET …/{id}/pdf-rapport` · `GET …/{id}/pdf-decision` (après prononcé) |
| Avertissements | `gerer-discipline` | `GET/POST /discipline/avertissements` |
| Historique agent (RH) | `consulter-discipline` | **`GET /discipline/agents/{id}/historique`** |
| Mes dossiers (agent) | compte avec `agent_id` | **`GET /discipline/moi/historique`** |

### Types CCN (art. 90)

`data.code` : `avertissement_ecrit` · `blame_ecrit` · `mise_a_pied` · `licenciement`.  
`exige_nb_jours` + `nb_jours_min` / `nb_jours_max` (1–8) sur la mise à pied. Types hors CCN : `actif=false` au seed (mutation d’office, rétrogradation). Un type CCN **ne peut pas** être supprimé (422).

### Workflow sanction

`en_attente` (rapport soumis) → RH `instruire` → `instruite` → DG **`valider` (prononcer)** **ou** **`rejeter` (classer)**. On **ne** classe **pas** depuis `en_attente` (supprimer le rapport à la place). `data.prochaine_etape` : `instruire` \| `prononcer` \| `null`.

`statut_label` : `Rapport soumis` · `Instruite — à prononcer` · `Prononcée` · `Classée sans suite`. Valeurs `statut` inchangées (`en_attente`, `instruite`, `validee`, `rejetee`).

`POST /discipline/sanctions` : `{ "agent_id", "type_sanction_id", "motif", "date_faits", "nb_jours"?, "avec_indemnite"? }`.  
- N+1 : uniquement ses agents (affectation active). RH (`gerer-discipline`) : tout agent non archivé.  
- Mise à pied : `nb_jours` **obligatoire**, 1–8.  
- Licenciement : `avec_indemnite` (défaut `true`).

`POST …/instruire` : `{ "notes_instruction": "…", "decision": "…" }` (`decision` optionnelle). **Au moins une pièce** sinon 422.  
`POST …/valider` (DG) : `{ "decision": "…", "date_decision": "Y-m-d"?, "commentaire": "?", "nb_jours"?, "date_debut_effet"?, "avec_indemnite"? }`. Mise à pied : `date_fin_effet` = début + `nb_jours` − 1 (jours calendaires).  
`POST …/rejeter` (DG) : `{ "commentaire": "…" }` (min. 3 caractères).

Pièce : multipart `fichier` (pdf/jpg/png/doc/docx, max 10 Mo).

Suppression d’un dossier : uniquement `en_attente` (auteur ou RH). Un dossier **instruit ou prononcé** n’est pas supprimable (conservation art. 91). Agent archivé : 422.

### Conservation, récidive, effets (vague B)

- `conservee_jusqu_au` : date de dépôt (ou de décision si plus tardive) **+ 5 ans**. `dans_delai_conservation` = encore dans ce délai.
- Détail / création : `recidive` (au moins une sanction **prononcée** sur 5 ans) + `antecedents_5_ans[]` `{ id, type, date_decision, motif }`. Historique : `data.recidive`.
- **Pas d’échelle automatique** (art. 90 : sanctions adaptées à la gravité, pas successives).
- Mise à pied prononcée : si `date_debut_effet` ≤ aujourd’hui, `agent.statut` → `suspendu`. Job quotidien `AppliquerEffetsMiseAPiedJob` (08h00) suspend / réactive à l’échéance. Compte utilisateur **inchangé**.
- Licenciement prononcé : archivage agent (`statut=archive`, compte `is_active=false`). **Pas** de calcul d’indemnité art. 111–114 (`avec_indemnite` reste un booléen).

Notifications : `domaine: discipline`, actions `creee` \| `instruite` \| `validee` \| `rejetee` \| `avertissement_cree`. Meta : `sanction_id` / `avertissement_id` + `agent_id`. L’auteur de l’action n’est **pas** notifié. `creee` / `validee` / `rejetee` : RH + agent + auteur du rapport. `instruite` : DG + agent + auteur du rapport.

`createur` / `validateur` / `emetteur` : `{ id, name }` ou `null`. `agent` : identité légère `{ id, matricule, nom, prenom, nom_complet }`. `pieces[]` : `{ id, nom_original, mime_type, taille, uploader }`.

**Hors scope :** conseil de discipline, recours, indemnité calculée (art. 111–114), art. 140–149, abandon de poste 107–108, récompenses art. 88.

---

## 2f. Affaires sociales — contrat FE (P1)

Préfixe : **`/api/affaires-sociales`**. Auth Bearer obligatoire. Listes **non paginées**. Source CCN : art. **58–59** (enfants à charge).  
P1 = organismes + affiliations + ayants droit. **Pas** de prestations (D.3.4, après paie) ni santé / AT-MP (D.3.5).

Reconnecter les comptes RH / DG / admin après seed (`consulter-affaires-sociales`, `gerer-affaires-sociales`).

### Permissions

| Permission | Usage | Seeder |
|------------|--------|--------|
| `consulter-affaires-sociales` | Listes, détail, alertes, dossier social, téléchargement pièces | `rh`, `admin`, `directeur-general` |
| `gerer-affaires-sociales` | CRUD organismes / affiliations / ayants droit / pièces | `rh`, `admin` |

Pas de self-service agent en P1. Les chefs **n’ont pas** le menu.

### Écrans recommandés

| Écran | Qui | APIs |
|-------|-----|------|
| Organismes | lecture `consulter-…` · écriture `gerer-…` | `GET/POST /affaires-sociales/organismes` · `GET/PUT/DELETE …/organismes/{id}` |
| Affiliations | idem | `GET/POST /affaires-sociales/affiliations` · `GET/PUT/DELETE …/affiliations/{id}` · `GET …/agents/{id}/affiliations` |
| Alerte sans CNSS | `consulter-…` | **`GET /affaires-sociales/alertes/sans-affiliation-cnss`** |
| Ayants droit | idem | `GET/POST /affaires-sociales/ayants-droit` · `GET/PUT/DELETE …/ayants-droit/{id}` · `GET …/agents/{id}/ayants-droit` |
| Pièces ayant droit | idem | `GET/POST …/ayants-droit/{id}/pieces` · `GET/DELETE …/pieces/{pieceId}` (multipart `fichier` + `type_piece`) |
| Dossier social agent | `consulter-…` | **`GET /affaires-sociales/agents/{id}/dossier-social`** |

### Organismes

`data.type` : `cnss` \| `mutuelle` \| `complementaire` \| `autre`.  
Seed : CNSS (`code=CNSS`, `systeme=true`) — **non supprimable**, type/code non modifiables.  
Liste : actifs par défaut ; `?actif=all` pour tout voir. Organisme inactif : nouvelle affiliation **422**. Organisme déjà utilisé : suppression **422**.

### Affiliations

`POST /affaires-sociales/affiliations` : `{ "agent_id", "organisme_id", "numero_affiliation"?, "date_debut", "date_fin"?, "statut"?, "notes"? }`.

- `statut` : `active` \| `suspendue` \| `cloturee` (défaut `active`).
- Une seule affiliation **active** par couple agent + organisme (sinon 422).
- CNSS : si `numero_affiliation` omis, reprise de `agent.numero_cnss`. Une affiliation CNSS active **écrit** `agent.numero_cnss`.
- Agent archivé : 422.

Alerte : agents non archivés / non stagiaires **sans** affiliation CNSS active. Identité légère + `numero_cnss` + `statut`.

### Ayants droit (art. 59)

`POST` : `{ "agent_id", "type", "nom", "prenom", "date_naissance", "sexe"?, "lien_juridique", "qualite_age"?, "date_debut"?, "date_fin"?, "actif"? }`.

| Champ | Valeurs |
|-------|---------|
| `type` | `conjoint` \| `enfant` |
| `lien_juridique` conjoint | `mariage` \| `union_libre` |
| `lien_juridique` enfant | `mariage` \| `naturel_reconnu` \| `adoption` \| `tutelle` |
| `qualite_age` (enfants, défaut `standard`) | `standard` (moins de **16** ans) · `apprentissage` (moins de **17**) · `etudes` / `infirmite` (moins de **21**) |

Règles métier (422) :

- un seul **conjoint actif** ;
- max **2** enfants actifs sous **tutelle** ;
- agent archivé.

Champs calculés (lecture) : `age`, `age_limite`, `a_charge`, `eligible_arbre_noel` (enfant 0–16 ans inclus, art. 58).

`nb_enfants` de `PUT /personnel/agents/{id}/situation-familiale` : si des enfants nominatifs existent, **ignoré** et recalculé (`a_charge`). Ne plus en faire la saisie maître.

Pièce : multipart `fichier` (pdf/jpg/png/doc/docx, max 10 Mo) + `type_piece` : `acte_naissance` \| `acte_mariage` \| `jugement_tutelle` \| `certificat_scolarite` \| `certificat_apprentissage` \| `certificat_medical` \| `autre`.

### Dossier social

`GET …/agents/{id}/dossier-social` :

```json
{
  "data": {
    "agent": { "id": 1, "matricule": "…", "nom": "…", "prenom": "…", "nom_complet": "…", "numero_cnss": "…", "statut": "actif" },
    "affiliations": [],
    "ayants_droit": [],
    "synthese": {
      "affiliation_cnss": false,
      "nb_enfants_a_charge": 0,
      "nb_enfants_arbre_noel": 0,
      "prime_arbre_noel_forfaitaire": true,
      "nb_enfants_tutelle": 0,
      "a_conjoint_a_charge": false
    }
  }
}
```

`nb_enfants_arbre_noel` plafonné à **3**. `prime_arbre_noel_forfaitaire=true` si aucun enfant 0–16 ans (part forfaitaire art. 58) — **pas de versement** en P1.

**Hors P1 :** demandes de prestations, capital décès, frais médicaux, AT-MP, assurances, paie.

---

## 2g. Formations — contrat FE (D.4)

Préfixe : **`/api/formations`**. Auth Bearer. Listes **non paginées**. Source CCN : art. **92–104**.  
Les stages d’**accueil** restent sous `/integration/stages`.

Reconnecter RH / DG / admin (`consulter-formations`, `gerer-formations`).

### Permissions

| Permission | Usage | Seeder |
|------------|--------|--------|
| `consulter-formations` | Catalogue, plans, inscriptions, certifications, pièce | `rh`, `admin`, `directeur-general` |
| `gerer-formations` | CRUD + valider / exécuter / inscrire / clôturer | `rh`, `admin` |

`POST /integration/stages/{id}/convertir-agent` : `gerer-formations` **ou** `creer-recrutement`.

### Écrans recommandés

| Écran | APIs |
|-------|------|
| Catalogue | `GET/POST /formations/catalogue` · `GET/PUT/DELETE …/catalogue/{id}` |
| Plan annuel | `GET/POST /formations/plans` · `POST …/plans/{id}/lignes` · `POST …/valider` · `…/executer` · `…/cloturer` |
| Inscriptions | `GET/POST /formations/inscriptions` · `POST …/{id}/confirmer-presence` · `POST …/{id}/cloturer` · `POST …/{id}/annuler` · `GET …/agents/{id}/inscriptions` |
| Certifications | `POST /formations/certifications` (multipart `fichier` optionnel) · `GET …/agents/{id}/certifications` · `GET …/{id}/fichier` |
| Conversion stagiaire | **`POST /integration/stages/{id}/convertir-agent`** — stage **clôturé** uniquement |

### Catalogue

`type_action` : `sur_le_tas` \| `seminaire` \| `perfectionnement` \| `qualification` \| `ecole` \| `camrtf`.  
`modalite` : `interne` \| `externe`.  
Plafonds durée : perfectionnement **9 mois** (art. 99), qualification / école **36 mois** (art. 102). Liste : actifs par défaut ; `?actif=all`.

`anciennete_min_ans` défaut **3** (art. 92). `debit_formation_mois` optionnel (art. 104) → calcule `debit_jusqu_au` à l’inscription.

### Plan annuel

`brouillon` → `valide` → `execute` → `cloture`. Modification / lignes **uniquement** en brouillon. Validation **422** sans ligne. Une année = un plan.

### Inscriptions

`POST` : `{ agent_id, formation_id, plan_id?, date_debut?, date_fin?, admission_sur_titre? }`.

- Agent archivé ou stagiaire : 422.
- Ancienneté < `anciennete_min_ans` : 422.
- `plan_id` : plan `valide` ou `execute`, formation prévue dans les lignes.
- `admission_sur_titre` : âge ≤ 50 **et** dernier échelon (n° 12) — art. 103.
- Perfectionnement / qualification : clôture exige `rapport_remis=true` (art. 100).

Statuts : `inscrite` → `presente` (`confirmer-presence`) → `terminee` (`cloturer`) ; ou `annulee`.

### Certifications

Lien optionnel `diplome_id` (référentiel déjà utilisé par le reclassement art. 73). Pièce pdf/jpg/png max 10 Mo.

### Conversion stagiaire → agent

Stage `TERMINE` uniquement. Ouvre un dossier d’intégration **Recrutement externe** (`BROUILLON`) sur le même agent. 2ᵉ appel : 422. Le wizard d’intégration existant prend le relais.

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
- Reclassements (art. 73–75) : `GET/POST /carriere/reclassements` — **pas** dans `/avancements`. Lien depuis la fiche agent.

### Contrats — essai art. 49 / délai art. 52 (Vague E lot A)

`GET/POST /carriere/contrats` (alias `/integration/contrats`). Recrutement externe : **`necessite_contrat = true`** (comme Contractuel). Reconnecter après reseed des types.

À la création **CDI/CDD**, l’API pose l’essai et un salaire à l’**échelon 1** (minimum de la classe), même si l’agent a un échelon cible plus élevé.

`data.essai` :

| Champ | Valeurs |
|-------|---------|
| `statut` | `en_cours` \| `renouvele` \| `concluant` \| `rompu` \| `non_applicable` (STG/CONS) |
| `duree_mois` | **1** (classes 1–4) / **2** (5–6) / **3** (7–10) |
| `prochaine_etape` | `confirmer-essai` tant que l’essai est ouvert, sinon `null` |
| `peut_renouveler` | `true` seulement si `en_cours` et pas encore renouvelé |

| Méthode | URL | Permission |
|---------|-----|------------|
| `POST` | `/carriere/contrats/{id}/renouveler-essai` | `modifier-contrats` |
| `POST` | `/carriere/contrats/{id}/confirmer-essai` | `modifier-contrats` — passe le salaire à l’échelon prévu |
| `POST` | `/carriere/contrats/{id}/rompre-essai` | `modifier-contrats` — `{ "commentaire": "…" }` optionnel ; **sans** préavis ni indemnité |
| `GET` | `/carriere/contrats/alertes/delai-30-jours` | `consulter-contrats` — PDS + 30 j ouvrables sans CDI/CDD |

Notifications : `domaine: contrat` (`essai_renouvele`, `essai_concluant`, `essai_rompu`, `essai_echeance`) ; `domaine: integration` (`contrat_delai_depasse`).

`data.mentions` reprend les mentions art. 52 (identité, essai, emploi, rémunération, **lieu de travail** si affectation active). `data.agent` est une identité légère (`id`, `matricule`, `nom`, `prenom`, `nom_complet`). Champ optionnel à la création : `lieu_recrutement`.

### Reclassements — art. 73–75 (lot D)

Hors notation. Changement de **classe** ou d’emploi. L’avancement d’échelon (`avancer-echelon`) reste dans la même classe.

| Méthode | URL | Permission | Qui |
|---------|-----|------------|-----|
| `GET` | `/carriere/reclassements` | `consulter-salaires` | RH, admin, **DG** |
| `POST` | `/carriere/reclassements` | `gerer-salaires` | RH |
| `GET` | `/carriere/reclassements/{id}` | `consulter-salaires` | + `eligibilite`, `prochaine_etape` |
| `POST` | `/carriere/reclassements/{id}/approuver` | `consulter-salaires` | **73** : rôle `rh`. **74/75** : rôle `directeur-general`. `admin` toujours. **403** sinon |
| `POST` | `/carriere/reclassements/{id}/rejeter` | idem | idem |
| `POST` | `/carriere/reclassements/{id}/appliquer` | `gerer-salaires` | RH — idempotent (`meta.applique`) |
| `GET` | `/carriere/agents/{id}/reclassements` | `consulter-salaires` | Historique |

**Body créer :**
```json
{
  "agent_id": 12,
  "type": "reclassement_formation",
  "motif": "Formation autorisée, diplôme reconnu au dossier.",
  "diplome_id": 4,
  "classe_cible_id": null,
  "fonction_cible_id": null,
  "motif_reconversion": null,
  "piece_path": null
}
```

`type` : `reclassement_formation` (73) · `reclassement_exceptionnel` (74a) · `hors_classe` (74b) · `reconversion` (75).

`statut` : `soumis` → `approuve` \| `rejete` → `applique` (`annule` réservé, pas d’endpoint V1).

| Type | Champs métier | Effet à `appliquer` |
|------|----------------|---------------------|
| 73 | `diplome_id` **obligatoire** et **déjà** sur `informations_professionnelles`. Classe = celle du diplôme (supérieure). | Nouvelle classe, échelon **1** |
| 74a | `classe_cible_id` supérieure (pas Hors classe). ≥ 50 ans, ≥ 15 ans ancienneté, ≥ 3 ans dans la classe. | Nouvelle classe, échelon **1** |
| 74b | Inspecteur principal + 8ᵉ échelon + ≥ 25 ans. Cible = Classe X / Hors Classe (seed). | Classe X, échelon **1**. **422** si grille non générée pour cette classe |
| 75 | `motif_reconversion` : `baisse_activite` \| `reorganisation` \| `maladie`. `fonction_cible_id` obligatoire. `piece_path` si maladie. `classe_cible_id` optionnel. | Nouvelle fonction ; classe seulement si fournie |

`prochaine_etape` : `approuver` (soumis) · `appliquer` (approuve) · `null`.

**Body approuver / rejeter :** `{ "commentaire": "optionnel" }`

**Réponse `appliquer` :**
```json
{
  "success": true,
  "data": { "id": 1, "statut": "applique", "prochaine_etape": null },
  "message": "Reclassement appliqué.",
  "meta": { "applique": true }
}
```
2ᵉ appel : `200`, `meta.applique: false`, message idempotent. Pas de 2ᵉ ligne de paie.

**GET show** (champs utiles FE) :
```json
{
  "id": 1,
  "agent_id": 12,
  "type": "reclassement_formation",
  "type_label": "Reclassement après formation (art. 73)",
  "article": "73",
  "statut": "soumis",
  "statut_label": "Soumis",
  "prochaine_etape": "approuver",
  "classe_origine": { "id": 1, "categorie": "Classe I", "grade": "Personnel de service", "coefficient": 45 },
  "classe_cible": { "id": 2, "categorie": "Classe II", "grade": "Personnel de service spécialisé", "coefficient": 50 },
  "echelon_origine": 3,
  "echelon_cible": 1,
  "age_ans": 46,
  "anciennete_ans": 8,
  "annees_dans_classe": 8,
  "eligibilite": { "ok": true, "age_ans": 46, "anciennete_ans": 8, "annees_dans_classe": 8, "messages": [] }
}
```
`eligibilite` est sur le **détail** (`GET /{id}`), pas forcément sur la liste.

**422** si les conditions CCN ne sont pas réunies (afficher `errors.*`, ne pas masquer le bouton au hasard). Un seul dossier `soumis`/`approuve` à la fois par agent.

| Cas | `errors.*` |
|-----|------------|
| 73 sans diplôme / pas au dossier | `diplome_id` |
| 74a âge inférieur à 50 | `age` |
| 74a ancienneté inférieure à 15 ans | `anciennete` |
| 74a moins de 3 ans dans la classe | `classe` |
| 74b pas 8ᵉ échelon / pas inspecteur principal | `echelon` / `grade` |
| 75 maladie sans `piece_path` | `piece_path` (`string`, chemin GED — **pas** d’upload multipart V1) |
| Hors classe sans ligne de grille | `classe_cible_id` |
| Déjà un dossier ouvert | `agent_id` |
| Approuver si pas `soumis` | `statut` |

Le DG a `consulter-salaires` (seeder) pour cette file — **pas** `gerer-salaires` (il n’écrit pas le bulletin). Après seed local : reconnecter le compte DG.

Détail : [`plan-evaluation-complements.md`](./plan-evaluation-complements.md) lot D.

- Listes métier : `GET /personnel/agents` (intégrés) vs `GET /integration/agents` (tous les dossiers).

Détail : [`note-fe-routes-carriere.md`](./note-fe-routes-carriere.md). Maquettes affectation / nomination dans `doc/maquettes/`.

---

## 5. Permissions utiles (menus)

| Besoin écran | Permission |
|--------------|------------|
| Recrutement / intégration | `consulter-recrutement`, `creer-recrutement`, `valider-recrutement` |
| Contrats | `consulter-contrats`, `creer-contrats`, `modifier-contrats` |
| Nominations (menus) | `consulter-nominations`, `gerer-nominations` — **pas encore** de middleware `permission:` sur les routes nomination |
| Salaires / reclassements | `consulter-salaires`, `gerer-salaires` — DG a **lecture** `consulter-salaires` (file art. 74–75) |
| Congés | `consulter-conges`, `creer-conges`, `valider-conges` — les boutons N+1/RH/DG se jouent **en plus** sur le rôle / le supérieur (§2c) |
| Absences | `consulter-absences`, `creer-absences`, `valider-absences` — signer = N+1 |
| Discipline | `consulter-discipline`, `gerer-discipline`, `proposer-discipline`, `prononcer-discipline` — circuit N+1 → RH → DG. Contrat §2e |
| Affaires sociales | `consulter-affaires-sociales`, `gerer-affaires-sociales` — P1 organismes / affiliations / ayants droit. Contrat §2f |
| Formations | `consulter-formations`, `gerer-formations` — catalogue, plan, inscriptions, certifications. Contrat §2g |
| Users | `consulter-utilisateurs`, `creer-utilisateurs`, `modifier-utilisateurs` |
| Rôles | `consulter-roles`, `creer-roles`, `modifier-roles` |

Hiérarchie (`directeur`, `chef-service`, …) : **pas** de menus salaires / contrats / recrutement / reporting, **sauf le DG** qui voit la file de reclassements (lecture + approuver 74/75). Périmètre « ma structure seulement » : **pas encore** filtré côté API.

---

## 6. Hors périmètre actuel (ne pas concevoir d’écrans API)

- ~~Campagnes et fiches d’évaluation~~ → **livré** P1–P5 + lots A–D (§7b + §4)
- ~~Reclassement / hors classe / reconversion (art. 73–75)~~ → **livré** §4
- Concours, PDF **acte** de reclassement → hors D.4
- ~~Discipline~~ → **livré** §2e
- ~~Affaires sociales P1~~ → **livré** §2f (prestations D.3.4 / santé D.3.5 encore hors scope)
- ~~Catalogue formations~~ → **livré** §2g
- Conformité CCN 3–5 lots B–E (pièces, positions, hors grille DG) → [`plan-conformite-ccn-modules-3-4-5.md`](./plan-conformite-ccn-modules-3-4-5.md) — **lot A livré** (§4 contrats / essai)
- Paie lots, dashboard → vagues D.5–D.6 (ne pas concevoir d’écrans avant l’API)
- GED **versioning / recherche** (la GED agent légère est livrée, §2d)
- Cloisonnement menus par bureau DRHL → Vague F, **après** D.2–D.6
- Mail / SMS (canal `database` uniquement pour l’instant)

---

## 7b. Module Évaluation / Notation / Avancement — P1–P5 + lots A–C

> Préfixe : `/api/avancements/`  
> Auth : `auth:sanctum` + `permission:consulter-evaluations | creer-evaluations | valider-evaluations`  
> Lots A–C (2026-09-14) : N+1 art. 62, tableau D5, PDF fiche + synthèse — voir sous-sections plus bas.  
> Art. 73–75 : **pas ici** → `/api/carriere/reclassements` (§4).

### Permissions par rôle (seeder mis à jour)

| Rôle | Permissions évaluation |
|------|------------------------|
| `rh` | `consulter-evaluations`, **`creer-evaluations`**, `valider-evaluations` |
| `directeur-general`, `directeur` | `consulter-evaluations`, `valider-evaluations` |
| `chef-service`, `chef-bureau` | `consulter-evaluations`, `valider-evaluations` |
| `agent` | `consulter-evaluations` |

### Endpoints disponibles (Phase 1, 2 et 3)

#### Sessions (RH — `creer-evaluations`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/avancements/sessions` | Liste des sessions (filtrables par `statut`) |
| GET | `/avancements/sessions/{id}` | Détail + évaluations chargées |
| GET | `/avancements/sessions/{id}/tableau` | Fiches `finalisee` **inscrites** au tableau (D5) — permission `consulter-evaluations` |
| **POST** | `/avancements/sessions` | **Ouvrir une session** → génère automatiquement les fiches éligibles |
| PUT | `/avancements/sessions/{id}` | Modifier description / dates |
| **POST** | `/avancements/sessions/{id}/cloturer` | Clôturer |
| **POST** | `/avancements/sessions/{id}/annuler` | Annuler (annule les fiches `en_attente`/`en_cours`) |
| POST | `/avancements/sessions/{id}/generer-fiches` | Regénérer fiches manquantes |
| GET | `/avancements/sessions/{id}/sans-superieur` | Agents éligibles **sans N+1** (à corriger en affectation) |

#### Grille de critères (RH — `creer-evaluations` pour CRUD)

| Méthode | Endpoint |
|---------|----------|
| GET / POST / PUT / DELETE | `/avancements/questions-evaluation[/{id}]` |

**Seeder 24 questions** : 12 compétences pro (/10) + 3 assiduité (/3) + 9 relations sociales (/7) = **20 pts**.

#### Fiches d’évaluation

| Méthode | Endpoint | Acteur | Permission |
|---------|----------|--------|------------|
| GET | `/avancements/evaluations` | RH | `consulter-evaluations` |
| GET | `/avancements/evaluations/{id}` | Tous | `consulter-evaluations` |
| GET | `/avancements/evaluations/superieur/mes-evaluations` | N+1 | `consulter-evaluations` |
| GET | `/avancements/evaluations/agent/mes-evaluations` | Agent | `consulter-evaluations` |
| **POST** | `/avancements/evaluations/{id}/noter` | N+1 | `valider-evaluations` |
| PUT | `/avancements/evaluations/{id}/contexte` | N+1 | `valider-evaluations` |
| POST | `/avancements/evaluations/{id}/signer-evaluateur` | N+1 (sans avis — Phase 1) | `valider-evaluations` |
| **POST** | `/avancements/evaluations/{id}/avis-et-signer` | N+1 — **Phase 2** | `valider-evaluations` |
| **POST** | `/avancements/evaluations/{id}/signer-evalue` | Agent | `consulter-evaluations` |
| **POST** | `/avancements/evaluations/{id}/reclamer` | Agent — **Phase 2** | `consulter-evaluations` |
| **POST** | `/avancements/evaluations/{id}/envoyer-rh` | Agent/N+1 — **Phase 2** | `consulter-evaluations` |
| POST | `/avancements/evaluations/{id}/valider-rh` | RH | `valider-evaluations` |
| POST | `/avancements/evaluations/{id}/inscrire-tableau` | RH | `valider-evaluations` |
| POST | `/avancements/evaluations/{id}/retirer-tableau` | RH | `valider-evaluations` |
| **GET** | `/avancements/evaluations/{id}/fiche-pdf` | Agent / N+1 / RH-DG | `consulter-evaluations` |
| POST | `/avancements/evaluations/{id}/annuler` | RH | `valider-evaluations` |
| **PUT** | `/avancements/evaluations/{id}/superieur` | RH | `creer-evaluations` |

### Payload fiche (GET show)

```json
{
  "id": 1,
  "session_id": 1,
  "session": { "id": 1, "debut_session": "2026-09-01", "statut": "ouverte", "statut_label": "Ouverte" },
  "agent_id": 5,
  "agent": { "id": 5, "matricule": "AG005", "nom": "DUPONT", "prenom": "Jean", "nom_complet": "Jean DUPONT" },
  "superieur_id": 3,
  "superieur": { "id": 3, "matricule": "AG003", "nom": "MARTIN", "prenom": "Marie", "nom_complet": "Marie MARTIN" },
  "affectation_notation_id": 12,
  "affectation_notation": {
    "id": 12,
    "date_affectation": "2024-01-01",
    "date_fin": null,
    "structure": { "id": 2, "nom": "DRHL", "type": "Direction" }
  },
  "note_globale": 15.5,
  "mention": "Très bien",
  "statut": "notee",
  "statut_label": "Notée (non signée)",
  "prochaine_etape": "avis_et_signer",
  "inscrit_tableau": false,
  "avis_superieur": null,
  "signe_par_evaluateur_at": null,
  "signe_par_evalue_at": null,
  "reclamation": null,
  "notes": [
    { "question_id": 1, "question": { "libelle": "Connaissance technique", "type_critere": "competence_pro", "bareme_max": 1 }, "note_obtenue": 0.9 }
  ]
}
```

### Payload noter (POST `/noter`)

```json
{ "question_id": 3, "note_obtenue": 0.8, "commentaire": "Très bon niveau" }
```
Réponse : fiche complète recalculée (même payload que `show`).

### Payload valider-rh (POST `/valider-rh`)

```json
{ "conforme": true, "commentaire": "Dossier complet et conforme." }
```
`conforme: true` → `finalisee` **et** `inscrit_tableau: true` (défaut D5).  
`conforme: false` → `rejetee` + retour notateur (`inscrit_tableau` inchangé, reste `false`).

### Tableau d’avancement (D5) et PDF

Ne pas casser la liste `GET /avancements/evaluations` (toutes les fiches). Écran « tableau » = `GET /avancements/sessions/{id}/tableau`. Filtre optionnel sur la liste : `?inscrit_tableau=1`.

| Méthode | URL | Quand | Qui |
|---------|-----|--------|-----|
| `GET` | `/avancements/sessions/{id}/tableau` | Fiches `finalisee` + `inscrit_tableau=true` | `consulter-evaluations` |
| `POST` | `/avancements/evaluations/{id}/inscrire-tableau` | Fiche `finalisee` ; **422** si commission d’avancement clôturée | `valider-evaluations` |
| `POST` | `/avancements/evaluations/{id}/retirer-tableau` | Idem ; **422** si `commission_decision` déjà posée | `valider-evaluations` |
| `GET` | `/avancements/evaluations/{id}/fiche-pdf` | Dès `signee_evalue` (**422** avant). Stream `application/pdf` | Agent de la fiche, N+1 notateur, `rh` / `admin` / `directeur-general`. **403** sinon (un chef d’un autre agent ne télécharge pas) |
| `GET` | `/avancements/commissions-preparatoires/{id}/synthese-pdf` | Commission préparatoire **clôturée** (**422** si `en_cours`) | `rh` / `admin` / `directeur-general` seulement (**403** sinon) |

`decider` en commission d’avancement → **422** `inscrit_tableau` si la fiche a été retirée.

### Payload réattribution N+1 (PUT `/superieur`)

```json
{ "superieur_id": 4 }
```
Condition : session `ouverte` + fiche non terminée.

### Éligibilité automatique (lors de la création de session)

Un agent reçoit une fiche **si et seulement si** :
1. Statut `actif` (pas stagiaire, détachement, position_exceptionnelle, inactif, retraité, suspendu, archivé)
2. Pas DG (nomination active `poste = Directeur Général`)
3. `date_prise_service` renseignée
4. Cycle 24 mois : `année(session) − année(dernière notation finalisée ou embauche) ≥ 2`
5. Parité d’année (si `session.type_annee` renseigné) : embauché année paire → session `type_annee = paire`
6. Semestre (si `session.semestre` renseigné) : embauché en S1 (jan–juin) → session `semestre = 1`
7. N+1 identifiable **sur le poste dominant** des 24 mois précédant `debut_session` (art. 62), pas seulement l’affectation active du jour. Sans N+1 (ou si l’agent est son propre chef) → `GET sessions/{id}/sans-superieur`, **pas de fiche créée**. Champ optionnel `affectation_notation` = poste retenu.

### Avis hiérarchiques — Phase 3 (CCN art. 64)

| Méthode | Endpoint | Acteur | Permission |
|---------|----------|--------|------------|
| GET | `/avancements/evaluations/{id}/niveaux-requis` | Tous | `consulter-evaluations` |
| GET | `/avancements/evaluations/{id}/avis-hierarchiques` | Tous | `consulter-evaluations` |
| **POST** | `/avancements/evaluations/{id}/avis-hierarchiques` | Hiérarchie | `valider-evaluations` |
| PUT | `/avancements/avis-hierarchiques/{id}` | Hiérarchie | `valider-evaluations` |
| **POST** | `/avancements/avis-hierarchiques/{id}/signer` | Hiérarchie | `valider-evaluations` |

#### Chaîne des niveaux

| Niveau | Code | Ordre standard | Ordre variante DG |
|--------|------|---------------:|------------------:|
| Chef de Bureau | `chef_bureau` | 1 | 1 |
| Chef de Service | `chef_service` | 2 | 2 |
| Directeur | `directeur` | 3 | *sauté* |
| Directeur Général | `directeur_general` | 4 | 3 |

**Variante DG** : si `direction.rattache_dg = true`, le niveau `directeur` est sauté.
Le FE peut connaître la chaîne exacte via `GET /niveaux-requis` avant d'afficher les boutons.

#### Payload `poster` / `update` (POST ou PUT)

```json
{
  "niveau": "chef_service",
  "avis": "Bonne maîtrise technique, encadrement efficace.",
  "approuve": true,
  "observations": "RAS"
}
```
- `niveau` : obligatoire, enum (`chef_bureau` | `chef_service` | `directeur` | `directeur_general`)
- `avis` : optionnel, max 2000 chars
- `approuve` : optionnel, boolean (`true` = favorable, `false` = défavorable)
- `observations` : optionnel, max 1000 chars

#### Règles FE pour les avis

| Règle | Détail |
|-------|--------|
| **Séquentialité** | Afficher le bouton du niveau N seulement si le niveau N−1 est signé (`signe: true`) |
| **Signature définitive** | Griser le bouton « Modifier » si `signe: true` — PUT retourne 422 |
| **Envoi RH bloqué** | `POST /envoyer-rh` retourne 422 si un avis requis n'est pas signé — afficher un badge de blocage |

#### Payload réponse avis

```json
{
  "id": 3,
  "evaluation_id": 12,
  "niveau": "directeur",
  "niveau_label": "Directeur",
  "ordre": 1,
  "avis": "Appréciation favorable.",
  "approuve": true,
  "observations": null,
  "signe": false,
  "date_signature": null,
  "signe_par": null
}
```

### Réclamations — Phase 2 (CCN art. 65)

| Méthode | Endpoint | Acteur | Permission |
|---------|----------|--------|------------|
| GET | `/avancements/reclamations` | RH | `valider-evaluations` |
| GET | `/avancements/reclamations/en-attente` | RH | `valider-evaluations` |
| GET | `/avancements/reclamations/{id}` | RH | `valider-evaluations` |
| **POST** | `/avancements/reclamations/{id}/traiter` | RH | `valider-evaluations` |

#### Payload `avis-et-signer` (POST)

```json
{ "avis_superieur": "Agent rigoureux, maîtrise son domaine. Atteint ses objectifs." }
```
Contrainte : `avis_superieur` requis, min 10, max 2000 chars.

#### Payload `reclamer` (POST)

```json
{ "motif": "Je conteste ma note sur le critère de connaissance technique." }
```
Contrainte : `motif` requis, min 10 chars.

#### Payload `traiter` (POST `/reclamations/{id}/traiter`)

```json
{ "acceptee": true, "commentaire": "Examiné et accepté — renvoi au notateur." }
```
- `acceptee: true` → fiche revient en `en_cours` (retour au notateur).
- `acceptee: false` → note maintenue, fiche passe en `en_validation_rh`.

#### Champ `prochaine_etape` (logique FE)

| Valeur | Action à afficher | Qui |
|--------|-------------------|-----|
| `noter` | Bouton « Évaluer » | N+1 |
| `continuer_notation` | Bouton « Continuer la notation » | N+1 |
| `avis_et_signer` | Bouton « Donner mon avis et signer » | N+1 |
| `signer_evalue` | Bouton « Prendre connaissance et signer » | Agent |
| `envoyer_rh` | Bouton « Transmettre à la RH » | Agent |
| `traiter_reclamation` | Badge réclamation en attente | RH |
| `valider_rh` | Bouton « Valider » + « Rejeter » | RH |
| `inscrire_tableau` | Fiche finalisée **retirée** du tableau — bouton « Inscrire au tableau » | RH |
| `corriger_notation` | Bouton « Corriger la note » | N+1 |
| `commission_preparatoire` | Fiche finalisée **inscrite**, en attente de passage en commission | RH/DG |
| `avancer_echelon` | Bouton « Appliquer l'avancement » (Phase 4) | RH |
| `null` | Fiche terminée (échelon avancé ou décision non favorable) | — |

**Règle FE** : lire `data.prochaine_etape` en premier. Adapter boutons et call-to-action selon rôle de l'utilisateur courant.

### Mentions /20

| Mention | Note |
|---------|------|
| Excellent | ≥ 16 |
| Très bien | 14 – 15,99 |
| Bien | 12 – 13,99 |
| Moyen | 10 – 11,99 |
| Insuffisant | < 10 |

### Statuts fiche

```
en_attente → en_cours → notee → signee_evaluateur → signee_evalue ┐ → en_validation_rh → finalisee
                                                                   │                           ↘ rejetee → retour en_cours
                                                                   └ → en_reclamation ──────────↗  (si RH rejette la réclamation)
                                       annulee (depuis tout statut non terminal)
```

### Hors Phase 1

- Avis hiérarchiques séquentiels (Phase 3) ✅
- Réclamation (Phase 2) ✅
- ~~Commissions (Phase 4)~~ → Implémenté, voir §4.5
- ~~Avancement d’échelon après commission (Phase 4)~~ → Implémenté, voir §4.5

---

## 5. Phase 5 — Satellites

### 5.6 Stats session

```
GET /avancements/sessions/{id}/stats
Permission : consulter-evaluations
```

Réponse :
```json
{
  "data": {
    "session_id": 3,
    "total": 45,
    "par_statut": {
      "finalisee": 38,
      "en_validation_rh": 5,
      "signee_evalue": 2
    },
    "moyenne": 14.72,
    "mentions": {
      "Très bien": 18,
      "Bien": 12,
      "Excellent": 8
    }
  }
}
```

### 5.1 Bonification stage (art. 71) — +2 échelons

Parcours **séparé** du cycle 24 mois. Condition : stage ≥ 9 mois autorisé + certificat ou attestation.

| Méthode | URL | Permission | Description |
|---------|-----|-----------|-------------|
| `GET` | `/avancements/bonifications-stage` | `valider-evaluations` | Liste toutes les demandes |
| `GET` | `/avancements/bonifications-stage/en-attente` | `valider-evaluations` | Demandes en attente |
| `POST` | `/avancements/bonifications-stage` | `consulter-evaluations` | Soumettre une demande |
| `POST` | `/avancements/bonifications-stage/{id}/traiter` | `valider-evaluations` | Approuver / rejeter |
| `POST` | `/avancements/bonifications-stage/{id}/appliquer` | `valider-evaluations` | Appliquer en paie (idempotent) |

**Body soumettre :**
```json
{
  "agent_id": 12,
  "date_debut_stage": "2025-01-01",
  "date_fin_stage": "2025-11-30",
  "type_document": "certificat",
  "reference_document": "CERT-2025-001"
}
```
> `duree_mois` calculé auto. Erreur 422 si < 9 mois.

**Body traiter :**
```json
{ "approuver": true, "commentaire": "Stage confirmé." }
```

**Réponse appliquer (idempotent) :**
```json
{ "data": { "avance": true, "message": "+2 échelon(s) appliqué(s) — bonification stage art. 71." } }
```

### 5.2 Avancement exceptionnel (art. 72)

Commission d'avancement, sur proposition DG, ≤ 2 échelons.

| Méthode | URL | Permission | Description |
|---------|-----|-----------|-------------|
| `GET` | `/avancements/avancements-exceptionnels` | `valider-evaluations` | Liste |
| `GET` | `/avancements/avancements-exceptionnels/en-attente` | `valider-evaluations` | En attente |
| `POST` | `/avancements/avancements-exceptionnels` | `valider-evaluations` | Proposer (DG) |
| `POST` | `/avancements/avancements-exceptionnels/{id}/traiter` | `valider-evaluations` | Approuver / rejeter |
| `POST` | `/avancements/avancements-exceptionnels/{id}/appliquer` | `valider-evaluations` | Appliquer en paie (idempotent) |

**Body proposer :**
```json
{
  "agent_id": 12,
  "nb_echelons": 2,
  "motif": "Performances exceptionnelles justifiant l'avancement.",
  "commission_avancement_id": 5
}
```
> `nb_echelons` : 1 ou 2 uniquement (422 sinon).

### 5.3 Connaissances complémentaires

Besoins de formation identifiés pendant l'évaluation.

| Méthode | URL | Permission | Description |
|---------|-----|-----------|-------------|
| `GET` | `/avancements/evaluations/{evaluationId}/connaissances` | `consulter-evaluations` | Liste par fiche |
| `POST` | `/avancements/evaluations/{evaluationId}/connaissances` | `consulter-evaluations` | Ajouter |
| `DELETE` | `/avancements/connaissances/{id}` | `consulter-evaluations` | Supprimer |

**Body ajouter :**
```json
{
  "type": "formation",
  "domaine": "Gestion de projet",
  "description": "Formation PMP souhaitée.",
  "urgent": true
}
```
Types valides : `formation` | `certification` | `perfectionnement` | `autre`.

### 5.4 Notifications domaine `evaluation`

Intégrer via la cloche existante (`/notifications`). Le service `EvaluationNotificationService` envoie automatiquement les événements suivants dans le domaine `evaluation` :

| `action` | Déclencheur | Destinataire |
|----------|------------|--------------|
| `session_ouverte` | Création session | Tous les N+1 |
| `fiche_a_noter` | Attribution fiche | N+1 |
| `fiche_a_signer_evalue` | N+1 signé | Agent |
| `fiche_en_validation_rh` | Agent transmis RH | Équipe RH |
| `fiche_finalisee` | Validation RH | Agent |
| `fiche_rejetee` | Rejet RH | N+1 |
| `commission_preparatoire_ouverte` | Ouverture commission | DG + RH |
| `commission_avancement_ouverte` | Ouverture commission | DG + RH |
| `avancement_accorde` | Échelon appliqué | Agent |
| `bonification_stage` | Demande stage art. 71 | Équipe RH |
| `avancement_exceptionnel` | Proposition art. 72 | Équipe RH |

Filtrer côté FE par `meta.domaine === 'evaluation'`.

---

## 4.5. Phase 4 — Commissions (CCN art. 68–70)

### Workflow complet Phase 4

Après finalisation RH d'une fiche (statut `finalisee`) :

```
finalisee (inscrit_tableau = true par défaut)
  └─ [RH] optionnel : POST evaluations/{id}/retirer-tableau   (hors tableau, toujours notée)
  └─ [RH] optionnel : POST evaluations/{id}/inscrire-tableau  (si retirée ; 422 si commission d’avancement clôturée)
  └─ [RH/DG] POST sessions/{sessionId}/commission-preparatoire   (ouvrir — toutes les finalisee)
     └─ POST commissions-preparatoires/{id}/noter                (harmoniser note + synthèse)
     └─ GET  commissions-preparatoires/{id}/alertes              (fiches écart > 5)
     └─ POST commissions-preparatoires/{id}/cloturer
        └─ GET commissions-preparatoires/{id}/synthese-pdf       (note art. 67, RH/admin/DG)
        └─ [RH/DG] POST sessions/{sessionId}/commission-avancement
           └─ POST commissions-avancements/{id}/decider          (422 si inscrit_tableau = false)
           └─ POST commissions-avancements/{id}/cloturer
              └─ [RH] POST evaluations/{evaluationId}/avancer-echelon  (idempotent)
              └─ [RH] POST sessions/{sessionId}/cloturer               (session fermée)
```

### Endpoints Phase 4

#### Commission préparatoire (art. 68)

| Méthode | URL | Permission | Description |
|---------|-----|-----------|-------------|
| `POST` | `/avancements/sessions/{sessionId}/commission-preparatoire` | `valider-evaluations` | Ouvrir la commission |
| `GET` | `/avancements/sessions/{sessionId}/commission-preparatoire` | `consulter-evaluations` | Consulter la commission |
| `POST` | `/avancements/commissions-preparatoires/{id}/noter` | `valider-evaluations` | Harmoniser note + synthèse |
| `GET` | `/avancements/commissions-preparatoires/{id}/alertes` | `valider-evaluations` | Fiches avec écart > 5 pts |
| `POST` | `/avancements/commissions-preparatoires/{id}/cloturer` | `valider-evaluations` | Clôturer |
| **GET** | `/avancements/commissions-preparatoires/{id}/synthese-pdf` | `consulter-evaluations` | PDF note de synthèse (art. 67) — **après clôture** ; RH / `admin` / `directeur-general` seulement (**403** sinon) |

**Body noter :**
```json
{
  "evaluation_id": 42,
  "commission_note": 15.0,
  "note_synthese": "Texte de synthèse narrative (art. 67)..."
}
```

**Réponse noter :**
```json
{
  "data": {
    "alerte_ecart": false,
    "ecart": 1.0,
    "message": "Note commission enregistrée."
  }
}
```
> `alerte_ecart = true` si `|commission_note − note_globale| > 5`. Non bloquant — indicatif FE.

#### Commission d'avancement (art. 69–70)

| Méthode | URL | Permission | Description |
|---------|-----|-----------|-------------|
| `POST` | `/avancements/sessions/{sessionId}/commission-avancement` | `valider-evaluations` | Ouvrir (prérequis : commission préparatoire clôturée) |
| `GET` | `/avancements/sessions/{sessionId}/commission-avancement` | `consulter-evaluations` | Consulter |
| `POST` | `/avancements/commissions-avancements/{id}/decider` | `valider-evaluations` | Décision par fiche |
| `POST` | `/avancements/commissions-avancements/{id}/cloturer` | `valider-evaluations` | Clôturer |
| `POST` | `/avancements/evaluations/{evaluationId}/avancer-echelon` | `valider-evaluations` | Appliquer l'échelon (D6) |

**Body décider :**
```json
{
  "evaluation_id": 42,
  "decision": "favorable",
  "nombre_echelons": 1,
  "note_avancement": 15.5,
  "commentaire": "Bons résultats."
}
```

- `decision` : `favorable` | `defavorable` | `reporte`
- `nombre_echelons` : `1` ou `2` si favorable, `0` sinon (même classe — **pas** de reclassement)
- `note_avancement` : optionnel, peut différer de `note_globale` N+1

**Réponse décider** : `EvaluationResource` enrichi :
```json
{
  "data": {
    "commission_decision": "favorable",
    "commission_decision_label": "Favorable (avancement accordé)",
    "nombre_echelons": 1,
    "note_avancement": 15.5,
    "echelon_avance": false,
    "prochaine_etape": "avancer_echelon"
  }
}
```

**Réponse avancer-echelon (idempotent) :**
```json
{
  "data": {
    "avance": true,
    "echelon_precedent_id": 5,
    "echelon_nouveau_id": 6,
    "message": "Échelon appliqué avec succès."
  }
}
```
> Si déjà appliqué : `"avance": false, "message": "Échelon déjà appliqué (idempotent)."`.

### Nouveaux champs sur `EvaluationResource`

| Champ | Type | Description |
|-------|------|-------------|
| `commission_note` | `float|null` | Note harmonisée par la commission préparatoire |
| `note_synthese` | `string|null` | Appréciation narrative (art. 67) |
| `commission_decision` | `string|null` | `favorable` / `defavorable` / `reporte` |
| `commission_decision_label` | `string|null` | Libellé lisible |
| `nombre_echelons` | `int|null` | Échelons accordés (0-2) |
| `note_avancement` | `float|null` | Note définitive retenue (art. 70) |
| `echelon_avance` | `boolean` | `true` si l'échelon a déjà été appliqué en paie |

### Ressource Commission

```json
{
  "id": 1,
  "session_id": 3,
  "session": { "id": 3, "debut_session": "2026-09-01", "statut": "ouverte" },
  "statut": "en_cours",
  "statut_label": "En cours",
  "date_ouverture": "2026-09-15",
  "date_cloture": null,
  "observations": null,
  "created_at": "2026-09-15T10:00:00"
}
```

### Gestion des erreurs Phase 4

| Cas | Code | `errors.xxx` |
|-----|------|--------------|
| Commission préparatoire inexistante pour ouvrir l'avancement | 422 | `commission_preparatoire` |
| Commission préparatoire non clôturée avant clôture session | 422 | `commission_preparatoire` |
| Commission avancement non clôturée avant clôture session | 422 | `commission_avancement` |
| Décision `favorable` sans échelons | 422 | `nombre_echelons` |
| `nombre_echelons > 2` | 422 | `nombre_echelons` |
| Fiche non finalisée passée en commission | 422 | `evaluation` |
| Fiche non inscrite au tableau (`decider`) | 422 | `inscrit_tableau` |
| Inscrire / retirer après clôture commission d’avancement | 422 | `commission_avancement` |
| Inscrire / retirer si déjà une `commission_decision` | 422 | `commission_decision` |
| PDF fiche avant signature agent | 422 | `statut` |
| PDF synthèse avant clôture préparatoire | 422 | `statut` |
| PDF fiche par un tiers (ni agent, ni N+1, ni RH/DG) | 403 | — |
| Commission déjà clôturée | 422 | `statut` |
| Avancer échelon sans décision favorable | 422 | `commission_decision` |

---

## 7. Journal (mettre à jour ici)

Format : date · quoi · impact FE (1 ligne).

| Date | Implémentation | Impact FE |
|------|----------------|-----------|
| 2026-09-16 | **Vague E lot A** : essai art. 49 + délai contrat 30 j art. 52 | Recrutement externe : `necessite_contrat=true`. Badge essai + boutons renouveler/confirmer/rompre. File `GET /carriere/contrats/alertes/delai-30-jours`. Voir §4. |
| 2026-09-15 | **Formation D.4** : `/api/formations` + `POST /integration/stages/{id}/convertir-agent` | Permissions `consulter-formations` / `gerer-formations`. Reconnecter RH / DG / admin. Contrat §2g. Stages d’accueil inchangés. |
| 2026-09-15 | **Affaires sociales D.3 P1** : `/api/affaires-sociales` (organismes, affiliations, ayants droit, pièces, alerte CNSS, dossier social) | Permissions `consulter-affaires-sociales` / `gerer-affaires-sociales`. Reconnecter RH / DG / admin. Contrat §2f. `nb_enfants` dérivé des ayants droit. Prestations **pas** livrées. |
| 2026-09-15 | **Discipline vague B** : conservation 5 ans, récidive (antécédents), suspension mise à pied, archivage licenciement | Afficher `conservee_jusqu_au`, `recidive`, `antecedents_5_ans`. Historique : `data.recidive`. Statut agent mis à jour au prononcé. |
| 2026-09-15 | **Discipline CCN art. 90–91** : 4 types, circuit N+1→RH→DG, pièces, mise à pied 1–8 j, PDF | **Breaking** : DG prononce (`prononcer-discipline`) ; chefs `proposer-discipline` + `mes-rapports` ; `prochaine_etape=prononcer` ; pièce obligatoire avant instruire. Reconnecter. Contrat §2e. |
| 2026-09-15 | **Discipline D.2** : `/api/discipline` + self-service agent `/moi/…` | Permissions `consulter-discipline` / `gerer-discipline` pour RH. Agent : `GET /discipline/moi/historique` (pas de permission). Reconnecter RH / DG / admin. Contrat §2e. |
| 2026-09-15 | **Plans d’implémentation recalés** : V1–V4 close, D.2 livré, prochain = affaires sociales D.3 | Consommer `/avancements` + `/carriere/reclassements` + `/discipline`. |
| 2026-09-15 | **Carrière art. 73–75** : `/carriere/reclassements` (formation, exceptionnel, hors classe, reconversion) + `changerClasse` | Écran fiche agent, **pas** commission. DG : `consulter-salaires` + `POST …/approuver` (74/75). RH : créer / art. 73 / `appliquer`. Voir §4. |
| 2026-09-14 | **Évaluation lots A–C** : N+1 = poste dominant 24 mois (art. 62), tableau d’avancement (`inscrit_tableau`), PDF fiche + note de synthèse | Champs optionnels `affectation_notation`, `inscrit_tableau`. Nouveaux : `GET sessions/{id}/tableau`, `POST …/inscrire-tableau` / `retirer-tableau`, `GET …/fiche-pdf`, `GET …/synthese-pdf`. `prochaine_etape` : `inscrire_tableau`. |
| 2026-09-10 | **Congés — positions CCN art. 79–80** : `StatutAgent` + 2 valeurs (`disponibilite`, `sous_le_drapeau`), migration ENUM agents, suppression "Mise en disponibilité" de type_absences, `SessionEvaluationService` dynamique | Agents en disponibilité et sous le drapeau exclus de l'évaluation. Enum agents étendu. |
| 2026-09-10 | **Congés — validations métier CCN art. 77** : congé annuel bloqué avant 12 mois de service, convenances personnelles min 15 jours | API renvoie **422** avec message explicite si règle non respectée. |
| 2026-09-10 | **Congés — mise en conformité CCN ARTF art. 77** : paliers ancienneté corrigés (8 paliers 0/6/8/10/12/14/16/18j), paternité 2j (était 10j), maternité 105j (était 98j), 14 types de congé ajoutés (exceptionnels, maladie famille, convenances perso, concours, éducation syndicale) | Nouvelle table seed `GET /types-conges`. Noms exacts dans §2c. |
| 2026-09-10 | **Module Évaluation Phase 5** : stats session, bonification stage art. 71, avancement exceptionnel art. 72, notifications évaluation, connaissances complémentaires | Voir §5. 12 nouveaux endpoints. 7 tests P5, 100 tests total, 728 assertions. |
| 2026-09-10 | **Module Évaluation Phase 4** : commissions préparatoire + avancement (CCN art. 68–70), clôture session renforcée, `avancerEchelon` idempotent | Voir §4.5. 7 nouveaux endpoints commissions + 1 `avancer-echelon`. `prochaine_etape` : `commission_preparatoire` / `avancer_echelon` / `null`. 33 tests, 314 assertions. |
| 2026-09-10 | **Module Évaluation Phase 3** : avis hiérarchiques séquentiels (CCN art. 64), variante rattaché DG, envoi RH bloqué si avis manquants | 5 nouveaux endpoints `/avis-hierarchiques`. 26 tests, 173 assertions. `avis_hierarchiques` chargés sur show. |
| 2026-09-10 | **Module Évaluation Phase 2** : avis N+1 obligatoire (min 10 chars), réclamations (CCN art. 65), envoi RH, `prochaine_etape` | Voir §7b. 4 nouveaux endpoints + `/reclamations` CRUD. `prochaine_etape` sur chaque fiche. `reclamation` chargée sur show. 20 tests, 111 assertions. |
| 2026-09-10 | Module Évaluation Phase 1 : sessions, grille 24q, fiches, notation /20, mentions, signatures, validation RH | Voir §7b. 21 endpoints `/api/avancements/`. Seeder 24 questions. Éligibilité complète (cycle 24 mois, parité, semestre, exemptions CCN). |
| 2026-09-08 | Paliers ancienneté + `jours_anciennete` sur le solde | CRUD `/conges/paliers-anciennete`. Solde = 30 + bonus. ~~Seed 0/2/4/6 j.~~ → **remplacé par 8 paliers CCN** (voir journal 2026-09-10). |
| 2026-09-08 | Listes congés/absences : `agent` identité légère | Même contrat que dossiers : `{ id, matricule, nom, prenom, nom_complet }`. `agent_id` conservé. |
| 2026-09-08 | Annulation, justificatif, soldes pré-créés, absences N+1 | `POST …/annuler`, `GET …/justificatif`, soldes à la lecture, `GET /absences/a-valider` (N+1) |
| 2026-09-07 | File `GET /conges/demandes/a-valider` + chevauchement DG/absences | Brancher les files N+1/RH/DG sur cet endpoint. 422 si période déjà prise (congé accordé ou absence). |
| 2026-09-06 | Listes dossiers + affectations : bloc `agent` (identité) | Afficher le nom sans appel extra. `agent_id` conservé. `agent` = `{ id, matricule, nom, prenom, nom_complet }` ou `null`. |
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
