# Note frontend — routes Carrière

> Date : 2026-08-15  
> Branche API : `feature/affectation`  
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
| `GET /carriere/affectations` | `GET /integration/affectations` |
| `GET /carriere/affectations/{id}` | `GET /integration/affectations/{id}` |
| `GET /carriere/agents/{id}/affectations` | `GET /integration/agents/{id}/affectations` |
| `POST /carriere/affectations/{id}/activer` | `POST /integration/affectations/{id}/activer` |
| `POST /carriere/affectations/{id}/rejeter` | `POST /integration/affectations/{id}/rejeter` |
| `POST /carriere/affectations/{id}/terminer` | `POST /integration/affectations/{id}/terminer` |
| `GET /carriere/affectations/{id}/note-service` | `GET /integration/affectations/{id}/note-service` |
| `POST /carriere/affectations/notes-service/lot` | `POST /integration/affectations/notes-service/lot` |
| `GET/POST /carriere/nominations` | `GET/POST /integration/nominations` |
| `POST /carriere/nominations/{id}/activer` | `POST /integration/nominations/{id}/activer` |
| `POST /carriere/nominations/{id}/cloturer` | `POST /integration/nominations/{id}/cloturer` |
| `POST /carriere/nominations/{id}/rejeter` | `POST /integration/nominations/{id}/rejeter` |
| `GET /carriere/agents/{id}/nominations` | `GET /integration/agents/{id}/nominations` |
| `GET/POST /carriere/contrats` | `GET/POST /integration/contrats` |
| `POST /carriere/contrats/{id}/resilier` | `POST /integration/contrats/{id}/resilier` |
| `GET /carriere/agents/{id}/contrats` | `GET /integration/agents/{id}/contrats` |
| `GET /carriere/agents/{id}/salaires…` | `GET /integration/agents/{id}/salaires…` |

Inchangé (reste `/integration`) : dossiers, documents, circuit, actes, compte, matériel, prise de service, stages, `POST …/validations/{id}/approuver`.

Swagger : tags **Carrière — Affectations** (les tags Intégration des mêmes routes sont marqués *deprecated*).

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

## 4. Activation d’affectation

`POST /carriere/affectations/{id}/activer`

- Body inchangé : `{ "dossier_integration_id": 7 }` reste **accepté**.
- Le champ est **ignoré** : le dossier **ne passe plus** à `AFFECTE`.
- L’affectation précédente `active` est toujours clôturée.

Ne plus attendre un changement de statut dossier après activation.

## 5. Validation structure

`structurable_id` doit exister pour le `structurable_type` envoyé (`Direction` / `Service` / `Bureau`).  
Sinon `422` sur `structurable_id` : « La structure indiquée n'existe pas. »

## 6. Reco de bascule

1. Pointer les appels affectation / nomination / contrats / salaires-agent vers `/api/carriere/…`.
2. Adapter la checklist (14/15 optionnelles + nouveaux endpoints).
3. Retirer toute logique « dossier → AFFECTE » après activation.
4. Prévenir l’API quand la bascule est faite : on pourra alors retirer les alias `integration`.
