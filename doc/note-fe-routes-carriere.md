# Note frontend — routes Carrière

> Date : 2026-08-23  
> Branches API : `feature/affectation`, `feature/nomination`  
> Breaking : **non** (alias conservés) — changements de contrat checklist à prendre en compte.

## 1. Nouveau préfixe

Les actes de **carrière** (affectation, nomination, contrats, salaires agent) ont le préfixe canonique :

```
/api/carriere/…
```

Les anciennes URLs sous `/api/integration/…` **continuent de fonctionner** (même verbe, même body, même JSON).  
Merci de basculer progressivement vers `/carriere` ; les alias `integration` seront retirés plus tard, après accord.

Préfixe API Laravel : toutes les routes ci-dessous sont sous `/api`.

## 2. Table de correspondance

| Canonique (à utiliser) | Alias (toujours OK) |
|------------------------|---------------------|
| `POST /carriere/affectations` | `POST /integration/affectations` |
| `POST /carriere/affectations/groupee` | `POST /integration/affectations/groupee` |
| `GET /carriere/affectations/lots/{id}` | `GET /integration/affectations/lots/{id}` |
| `POST /carriere/affectations/lots/{id}/activer` | `POST /integration/affectations/lots/{id}/activer` |
| `POST /carriere/affectations/lots/{id}/rejeter` | `POST /integration/affectations/lots/{id}/rejeter` |
| `GET /carriere/affectations/lots/{id}/acte` | `GET /integration/affectations/lots/{id}/acte` |
| `GET /carriere/affectations` | `GET /integration/affectations` |
| `GET /carriere/affectations/{id}` | `GET /integration/affectations/{id}` |
| `GET /carriere/agents/{id}/affectations` | `GET /integration/agents/{id}/affectations` |
| `POST /carriere/affectations/{id}/activer` | `POST /integration/affectations/{id}/activer` |
| `POST /carriere/affectations/{id}/rejeter` | `POST /integration/affectations/{id}/rejeter` |
| `POST /carriere/affectations/{id}/terminer` | `POST /integration/affectations/{id}/terminer` |
| `GET /carriere/affectations/{id}/note-service` | `GET /integration/affectations/{id}/note-service` |
| `POST /carriere/affectations/notes-service/lot` | `POST /integration/affectations/notes-service/lot` |
| `GET/POST /carriere/nominations` | `GET/POST /integration/nominations` |
| `POST /carriere/nominations/groupee` | `POST /integration/nominations/groupee` |
| `GET /carriere/nominations/lots/{id}` | `GET /integration/nominations/lots/{id}` |
| `POST /carriere/nominations/lots/{id}/activer` | `POST /integration/nominations/lots/{id}/activer` |
| `POST /carriere/nominations/lots/{id}/rejeter` | `POST /integration/nominations/lots/{id}/rejeter` |
| `GET /carriere/nominations/lots/{id}/acte` | `GET /integration/nominations/lots/{id}/acte` |
| `PUT /carriere/nominations/{id}` | `PUT /integration/nominations/{id}` |
| `POST /carriere/nominations/{id}/activer` | `POST /integration/nominations/{id}/activer` |
| `POST /carriere/nominations/{id}/cloturer` | `POST /integration/nominations/{id}/cloturer` |
| `POST /carriere/nominations/{id}/rejeter` | `POST /integration/nominations/{id}/rejeter` |
| `GET /carriere/nominations/{id}/acte` | `GET /integration/nominations/{id}/acte` |
| `GET /carriere/nominations/postes-vacants` | `GET /integration/nominations/postes-vacants` |
| `GET /carriere/nominations/chefs/{id}/agents-sous-autorite` | `GET /integration/nominations/chefs/{id}/agents-sous-autorite` |
| `GET /carriere/agents/{id}/nominations` | `GET /integration/agents/{id}/nominations` |
| `GET /carriere/agents/{id}/nominations/historique` | `GET /integration/agents/{id}/nominations/historique` |
| `GET /carriere/agents/{id}` (synthèse) | **pas d’alias** (conflit avec `GET /integration/agents/{id}`) |
| `GET/POST /carriere/contrats` | `GET/POST /integration/contrats` |
| `POST /carriere/contrats/{id}/resilier` | `POST /integration/contrats/{id}/resilier` |
| `GET /carriere/agents/{id}/contrats` | `GET /integration/agents/{id}/contrats` |
| `GET /carriere/agents/{id}/salaires…` | `GET /integration/agents/{id}/salaires…` |

Inchangé (reste `/integration`) : dossiers, documents, circuit, actes, compte, matériel, prise de service, stages, `POST …/validations/{id}/approuver`.

Swagger : tags **Carrière — Affectations** et **Carrière — Nominations** (les tags Intégration des mêmes routes sont marqués *deprecated*).

## 3. Checklist post-intégration (`taches_post_integration`)

Les clés `etape` **14** et **15** sont **conservées** (pas de suppression).

| Champ | Avant | Maintenant |
|-------|--------|------------|
| 14 `endpoint` | `POST /integration/affectations` | `POST /carriere/affectations` |
| 14 `label` | Affecter l'agent | Affecter l'agent (module carrière) |
| 14 `obligatoire` | `true` | **`false`** |
| 15 `endpoint` | `POST /integration/nominations` | `POST /carriere/nominations` |
| 15 `label` | Nommer l'agent (…) | Nommer l'agent (module carrière) |
| 15 `obligatoire` | `false` | `false` (inchangé ; absente si stage) |

**À faire côté FE**

- Ne plus bloquer la fin d’intégration / le wizard sur l’étape 14.
- Traiter 14 et 15 comme des **liens** vers les écrans carrière (préremplir `agent_id`).
- Si vous affichez « X tâches obligatoires restantes », filtrer sur `obligatoire === true`.
- `INTEGRE` ne dépend plus d’une affectation ni d’une nomination.

## 4. Activation d’affectation / nomination

`POST /carriere/affectations/{id}/activer` et `POST /carriere/nominations/{id}/activer`

- Body inchangé : `{ "dossier_integration_id": 7 }` reste **accepté**.
- Le champ est **ignoré** : le dossier **ne passe plus** à `AFFECTE` / `NOMME`.
- L’affectation ou nomination précédente `active` est clôturée (pour la nomination : active de la **structure** et de l’**agent**).
- Une nomination ne peut être activée que depuis le statut `approuvee` (circuit terminé).
- `statut` : valeurs minuscules (`en_attente`, `approuvee`, `active`, `cloturee`, `rejetee`). Champ additionnel `statut_label`.
- Cohérence poste / structure : Chef de Bureau → Bureau, Chef de Service → Service, Directeur* → Direction. Sinon `422` sur `poste`.
- Étape checklist 15 : `fait` seulement s’il existe une nomination **active**.

Ne plus attendre un changement de statut dossier après activation.

### Lot groupé d’affectations (un circuit, un acte)

`POST /carriere/affectations/groupee` crée désormais un **lot** (plus N circuits).

- Minimum 2 agents distincts. **Plusieurs agents peuvent aller dans la même structure.**
- Champs communs : `date_affectation`, `motif`, `note_service` (fichier optionnel).
- Par ligne : `agent_id`, `structurable_*`, `superieur_hierarchique_id` (auto si absent).
- Réponse : `data.id` (lot), `data.total`, `data.affectations[]`, `data.validations[]`, `data.statut` (`en_attente_validation`).
- Circuit sur le **lot** (`LotAffectation`), pas sur chaque affectation.
- `POST …/affectations/lots/{id}/activer` active **toutes** les lignes (termine l’active de chaque agent).
- `GET …/affectations/lots/{id}/acte` : un PDF qui liste tout le monde (`NS-LOT-AFF-2026-0001.pdf`).
- Le ZIP `POST …/notes-service/lot` reste pour des notes **individuelles** (IDs unitaires).
- Interdit : activer / rejeter une ligne isolée si `lot_affectation_id` est renseigné.

## 5. Nominations — lectures et acte (phase 2)

- `GET /carriere/nominations/postes-vacants` : structures (Direction / Service / Bureau) **sans** nomination `active`, avec `postes_possibles`.
- `GET /carriere/nominations/chefs/{id}/agents-sous-autorite` : `{id}` = **agent** chef. Réponse : `chef`, `nomination_active`, `agents[]` (`agent` + `affectation` active dont il est supérieur).
- `GET /carriere/agents/{id}/nominations/historique` : toutes les nominations **sauf** `active` (ne remplace pas `GET …/nominations`).
- `PUT /carriere/nominations/{id}` : uniquement si `en_attente` — sinon `422`.
- `GET /carriere/nominations/{id}/acte` : PDF (`type_acte` : `arrete` | `decision` | `note_service`). Champ `type_acte_label` dans le JSON.

### Lot groupé (un circuit, un acte)

`POST /carriere/nominations/groupee`

```json
{
  "date_debut": "2026-09-01",
  "type_acte": "decision",
  "agents": [
    { "agent_id": 42, "poste": "Chef de Service", "structurable_type": "App\\Models\\Service", "structurable_id": 3 },
    { "agent_id": 7, "poste": "Chef de Bureau", "structurable_type": "App\\Models\\Bureau", "structurable_id": 8 }
  ]
}
```

- Minimum 2 lignes ; agents distincts ; une structure = une ligne dans le lot.
- Circuit sur le **lot** (`LotNomination`), pas sur chaque nomination.
- `POST …/lots/{id}/activer` active **toutes** les lignes (clôture les actives agent + structure).
- `GET …/lots/{id}/acte` : un PDF qui liste tout le monde.
- Interdit : activer / rejeter / PUT une ligne isolée si `lot_nomination_id` est renseigné.

## 5bis. Synthèse carrière (phase 3)

`GET /api/carriere/agents/{id}` — pas d’alias `/integration`.

Réponse `data` :

- identité : `id`, `matricule`, `nom`, `prenom`, `statut`
- `contrat_actif`, `affectation_active`, `nomination_active`, `salaire_actuel` (objets ou `null`)

Notifications (table Laravel `notifications`, canal `database`) à la création / approbation / activation / rejet : destinataires = auteur (`created_by`) et compte lié à l’agent s’il existe.

Permissions seedées (menus FE, **pas encore** de middleware sur les routes nomination) : `consulter-nominations`, `gerer-nominations`.

## 6. Validation structure

`structurable_id` doit exister pour le `structurable_type` envoyé (`Direction` / `Service` / `Bureau`).  
Sinon `422` sur `structurable_id` : « La structure indiquée n'existe pas. »

## 7. Reco de bascule

1. Pointer les appels affectation / nomination / contrats / salaires-agent vers `/api/carriere/…`.
2. Adapter la checklist (14/15 optionnelles + nouveaux endpoints).
3. Retirer toute logique « dossier → AFFECTE » après activation.
4. Prévenir l’API quand la bascule est faite : on pourra alors retirer les alias `integration`.
