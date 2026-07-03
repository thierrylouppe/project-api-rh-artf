# Guide de test — Module Intégration Administrative

> Base URL : `http://localhost:8000/api`  
> Outil recommandé : Insomnia ou Postman  
> Headers obligatoires sur **toutes** les requêtes :
> ```
> Accept: application/json
> Authorization: Bearer {token}
> ```

---

## 0. Authentification

Obtenir un token Sanctum avant toute chose.

**Requête**
```
POST /login
```
```json
{
  "email": "admin@artf.cg",
  "password": "password"
}
```

**Réponse**
```json
{
  "token": "1|abc123xyz...",
  "user": { "id": 1, "name": "Admin" }
}
```

> Copie le `token` et place-le dans le header `Authorization: Bearer 1|abc123xyz...` pour toutes les étapes suivantes.

---

## Vue d'ensemble — flux simplifié

```
Étapes 1 → 10  (obligatoires, inchangées)
      │
      └─► Étape 11 — Intégration + Création du compte  POST /dossiers/7/integrer
                     Statut : INTEGRE ✓
                     → réponse inclut la liste des tâches post-intégration restantes
```

**Les étapes 12 à 18 sont post-intégration : elles peuvent être réalisées dans n'importe quel ordre, après l'intégration.**

> **Tâche 14 — Affectation :** supporte l'affectation unitaire et groupée (plusieurs agents → chacun vers sa propre structure, note de service commune), la résolution automatique du supérieur hiérarchique par structure, l'upload de la note de service, la génération PDF à la demande et la génération en lot (ZIP).


Un endpoint dédié permet de consulter leur avancement à tout moment :
```
GET /integration/dossiers/7/taches-post-integration
```

---

## Étape 1 — Sélectionner le type d'intégration

Consulter les types disponibles. Le champ `necessite_contrat` conditionne l'affichage de l'étape 3 (contrat).

**Requête**
```
GET /types-integrations
```

**Réponse**
```json
{
  "data": [
    { "id": 1, "nom": "Recrutement externe",  "type_acte_administratif": "decision_recrutement", "necessite_contrat": false },
    { "id": 2, "nom": "Mutation",             "type_acte_administratif": "decision_mutation",     "necessite_contrat": false },
    { "id": 3, "nom": "Détachement",          "type_acte_administratif": "arrete_detachement",    "necessite_contrat": false },
    { "id": 4, "nom": "Mise à disposition",   "type_acte_administratif": "note_de_service",       "necessite_contrat": false },
    { "id": 5, "nom": "Réintégration",        "type_acte_administratif": "decision_recrutement",  "necessite_contrat": false },
    { "id": 6, "nom": "Contractuel",          "type_acte_administratif": "contrat",               "necessite_contrat": true  },
    { "id": 7, "nom": "Stage professionnel",  "type_acte_administratif": "contrat",               "necessite_contrat": true  }
  ]
}
```

> Retenir l'`id` du type choisi — il sera utilisé aux étapes 2 et 2-bis.

---

## Étape 1-bis — Consulter les diplômes (optionnel mais recommandé)

Permet au frontend de proposer la sélection du diplôme pour pré-remplir automatiquement la classe, le grade et l'échelon.

**Requête**
```
GET /diplomes
```

**Réponse (extrait)**
```json
{
  "data": [
    {
      "id": 23,
      "nom": "Master",
      "sigle": "MST",
      "classegrillesalariale_id": 8,
      "classe_grille": {
        "id": 8,
        "coefficient": 120,
        "categorie": "Classe VIII",
        "categorie_id": 8,
        "grade": "Inspecteur",
        "grade_id": 8
      }
    },
    {
      "id": 24,
      "nom": "Licence",
      "sigle": "LIC",
      "classegrillesalariale_id": 7,
      "classe_grille": {
        "id": 7,
        "coefficient": 105,
        "categorie": "Classe VII",
        "categorie_id": 7,
        "grade": "Vérificateur",
        "grade_id": 7
      }
    }
  ]
}
```

> Le frontend peut afficher le diplôme + sa classe pour guider l'utilisateur.

---

## Étape 2 — Créer la fiche agent (le dossier est créé automatiquement)

Un seul appel suffit. Le dossier d'intégration est créé **en arrière-plan** avec `type_integration_id`, `agent_id` et `date_demande` (= aujourd'hui). Statut initial du dossier : **`BROUILLON`**

> Le matricule n'est pas saisi ici — il sera fourni plus tard par le système externe (tâche post-intégration 13).

### Cas 1 — Avec `diplome_id` *(recommandé)*

Si `diplome_id` est fourni, le système remplit automatiquement `categorie_id`, `grade_id` et `echelon_id = 1` depuis la classe grille du diplôme. Inutile de les saisir manuellement.

**Requête**
```
POST /integration/agents
```
```json
{
  "nom": "LOUPPE",
  "prenom": "Thierry",
  "date_naissance": "1990-03-15",
  "lieu_naissance": "Brazzaville",
  "nationalite": "Congolaise",
  "genre": "M",
  "telephone": "+242 06 123 45 67",
  "email_personnel": "t.louppe@gmail.com",
  "type_integration_id": 1,
  "diplome_id": 23,
  "fonction_id": 3,
  "numero_cnss": "101234567",
  "rib_bancaire": "CG001 00001 00000123456 78"
}
```

> `diplome_id: 23` (Master) → le système applique automatiquement `Classe VIII / Inspecteur / Échelon 1`.

### Cas 2 — Sans `diplome_id` (saisie manuelle)

```json
{
  "nom": "LOUPPE",
  "prenom": "Thierry",
  "date_naissance": "1990-03-15",
  "lieu_naissance": "Brazzaville",
  "nationalite": "Congolaise",
  "genre": "M",
  "telephone": "+242 06 123 45 67",
  "email_personnel": "t.louppe@gmail.com",
  "type_integration_id": 1,
  "grade_id": 8,
  "categorie_id": 8,
  "echelon_id": 1,
  "fonction_id": 3,
  "numero_cnss": "101234567",
  "rib_bancaire": "CG001 00001 00000123456 78"
}
```

**Champs obligatoires**

| Champ | Obligatoire | Remarque |
|-------|-------------|----------|
| `nom`, `prenom`, `date_naissance`, `genre` | Oui | Infos de base |
| `type_integration_id` | **Oui** | Conditionne le type d'acte et la nécessité d'un contrat |
| `diplome_id` | Non | Si fourni → `categorie_id`, `grade_id`, `echelon_id` remplis automatiquement |
| `grade_id`, `categorie_id`, `echelon_id` | Non | Requis uniquement si `diplome_id` absent |
| `fonction_id` | Non | Poste occupé |

**Réponse `201`**
```json
{
  "data": {
    "agent": {
      "id": 42,
      "matricule": null,
      "nom": "LOUPPE",
      "prenom": "Thierry",
      "nom_complet": "Thierry LOUPPE",
      "categorie_id": 8,
      "grade_id": 8,
      "echelon_id": 1,
      "statut": "actif"
    },
    "dossier": {
      "id": 7,
      "reference": "ARTF-INT-2026-000007",
      "statut": "BROUILLON",
      "statut_label": "Brouillon",
      "type_integration_id": 1,
      "agent_id": 42
    }
  },
  "message": "Fiche agent créée — dossier d'intégration initialisé automatiquement (réf. ARTF-INT-2026-000007)"
}
```

> Retenir l'`id` de l'agent (`42`) et l'`id` du dossier (`7`).

---

## Étape 3 — Créer le contrat *(uniquement si `necessite_contrat = true`)*

> **Cette étape s'applique uniquement aux types : Contractuel et Stage professionnel.**  
> Pour Recrutement externe, Mutation, Détachement, Mise à disposition, Réintégration → **passer directement à l'étape 4.**

**Requête**
```
POST /integration/contrats
```
```json
{
  "agent_id": 42,
  "dossier_integration_id": 7,
  "type_contrat_id": 2,
  "fonction_id": 3,
  "date_debut": "2026-07-01",
  "remuneration": 850000
}
```

**Réponse `201`**
```json
{
  "data": {
    "id": 3,
    "statut": "actif",
    "date_debut": "2026-07-01",
    "remuneration": 850000
  }
}
```

---

## Étape 4 — Soumettre le dossier

Lance le processus RH. Statut → **`SOUMIS`**

**Requête**
```
POST /integration/dossiers/7/soumettre
```
*(body vide)*

**Réponse `200`**
```json
{
  "data": { "statut": "SOUMIS", "statut_label": "Soumis" },
  "message": "Dossier soumis pour étude RH"
}
```

---

## Étape 5 — Prise en charge par les RH

Un agent RH confirme qu'il prend le dossier en charge. Statut → **`EN_ETUDE_RH`**

**Requête**
```
POST /integration/dossiers/7/passer-en-etude-rh
```
*(body vide)*

---

## Étape 6 — Déposer les pièces justificatives

Chaque pièce est envoyée individuellement en `multipart/form-data`.

**Requête**
```
POST /integration/dossiers/7/documents
Content-Type: multipart/form-data
```
| Champ | Valeur |
|-------|--------|
| `type_document_id` | `1` (ex. Acte de naissance) |
| `est_obligatoire` | `true` |
| `fichier` | fichier PDF/image (max 10 Mo) |

**Réponse `201`**
```json
{
  "data": {
    "id": 15,
    "nom_original": "acte_naissance.pdf",
    "est_obligatoire": true,
    "est_valide": false
  },
  "message": "Document ajouté au dossier"
}
```

Répéter pour chaque pièce. Consulter les documents déposés :
```
GET /integration/dossiers/7/documents
```

**Pièces obligatoires typiques**

| type_document_id | Libellé |
|-----------------|---------|
| 1 | Acte de naissance |
| 2 | Certificat de nationalité |
| 3 | Pièce d'identité |
| 4 | Casier judiciaire |
| 5 | Certificat médical |
| 6 | Curriculum vitae |
| 7 | Diplômes |
| 8 | RIB bancaire |
| 9 | Numéro CNSS |

---

## Étape 7 — Valider chaque document

L'agent RH valide chaque pièce une par une.

**Requête**
```
POST /integration/documents/15/valider
```
```json
{
  "commentaire": "Document conforme et lisible"
}
```

**Réponse `200`**
```json
{
  "data": {
    "id": 15,
    "est_valide": true,
    "date_validation": "2026-06-19T10:30:00"
  },
  "message": "Document validé"
}
```

---

## Étape 8 — Marquer le dossier complet ou incomplet

### Si toutes les pièces obligatoires sont validées
Statut → **`DOSSIER_COMPLET`**
```
POST /integration/dossiers/7/marquer-complet
```

### Si une pièce manque
Statut → **`DOSSIER_INCOMPLET`**
```
POST /integration/dossiers/7/marquer-incomplet
```
```json
{
  "commentaire": "Il manque le casier judiciaire datant de moins de 3 mois"
}
```
> Le dossier peut repasser en `EN_ETUDE_RH` après correction.

---

## Étape 9 — Validation RH + initialisation du circuit hiérarchique

L'agent RH valide formellement le dossier complet. Statut → **`VALIDE_RH`**  
**Le circuit de validation à 5 niveaux est automatiquement créé.**

**Requête**
```
POST /integration/dossiers/7/valider-rh
```

Consulter le circuit créé :
```
GET /integration/dossiers/7/circuit
```

**Réponse**
```json
{
  "data": [
    { "id": 11, "niveau": "chef_bureau",       "ordre": 1, "statut": "en_attente" },
    { "id": 12, "niveau": "chef_service",      "ordre": 2, "statut": "en_attente" },
    { "id": 13, "niveau": "directeur",         "ordre": 3, "statut": "en_attente" },
    { "id": 14, "niveau": "drh",               "ordre": 4, "statut": "en_attente" },
    { "id": 15, "niveau": "directeur_general", "ordre": 5, "statut": "en_attente" }
  ]
}
```

---

## Étape 10 — Circuit de validation hiérarchique (5 niveaux)

Chaque validateur approuve son niveau dans l'ordre.

### Approuver (passage au niveau suivant)
```
POST /integration/validations/11/approuver
```
```json
{ "commentaire": "Dossier conforme, approuvé" }
```

Répéter pour les validations 12, 13, 14, 15.

### Rejeter (fin du processus)
```
POST /integration/validations/11/rejeter
```
```json
{ "commentaire": "Profil ne correspond pas au poste requis" }
```

### Renvoyer pour correction
```
POST /integration/validations/11/renvoyer
```
```json
{ "commentaire": "Merci de compléter la rubrique expérience professionnelle" }
```

> Quand la validation DG (niveau 5) est approuvée, le statut passe automatiquement à **`VALIDE_DG`**.

---

## Étape 11 — Intégration + Création du compte utilisateur ✅

> **Étape finale du flux obligatoire.**  
> Le dossier doit être en statut **`VALIDE_DG`**.

Un seul appel suffit pour :
1. Clôturer le dossier → statut **`INTEGRE`**
2. Créer automatiquement le compte applicatif de l'agent (login, email professionnel, badge)
3. Retourner la liste des **tâches post-intégration** restantes à réaliser

**Requête**
```
POST /integration/dossiers/7/integrer
```
*(body vide)*

**Réponse `200`**
```json
{
  "data": {
    "dossier": {
      "reference": "ARTF-INT-2026-000007",
      "statut": "INTEGRE",
      "statut_label": "Intégré",
      "agent": {
        "id": 42,
        "matricule": null,
        "nom_complet": "Thierry LOUPPE"
      }
    },
    "compte": {
      "login": "tlouppe",
      "email_professionnel": "tlouppe@artf.cg",
      "badge_numero": "ARTF-BADGE-00042"
    },
    "taches_post_integration": [
      { "etape": 11, "label": "Générer l'acte administratif",        "endpoint": "POST /integration/dossiers/7/generer-acte",    "statut": "non_fait", "obligatoire": true  },
      { "etape": 13, "label": "Assigner le matricule",               "endpoint": "POST /integration/dossiers/7/assigner-matricule","statut": "non_fait", "obligatoire": true  },
      { "etape": 14, "label": "Affecter l'agent",                    "endpoint": "POST /integration/affectations",               "statut": "non_fait", "obligatoire": true  },
      { "etape": 15, "label": "Nommer l'agent (responsabilité)",     "endpoint": "POST /integration/nominations",                "statut": "non_fait", "obligatoire": false },
      { "etape": 17, "label": "Remettre le matériel",                "endpoint": "POST /integration/remises-materiel",           "statut": "non_fait", "obligatoire": false },
      { "etape": 18, "label": "Confirmer la prise de service",       "endpoint": "POST /integration/prises-de-service",          "statut": "non_fait", "obligatoire": false }
    ],
    "rappel": "6 tâche(s) post-intégration en attente — consultez taches_post_integration."
  },
  "message": "Intégration administrative finalisée avec succès"
}
```

> Pour les types **Contractuel** et **Stage professionnel** (`necessite_contrat = true`), la tâche 12 (signature du contrat) apparaît également dans la liste.

---

## Consulter les tâches post-intégration à tout moment

Après l'intégration, ce point d'accès retourne l'état d'avancement de chaque tâche restante.

**Requête**
```
GET /integration/dossiers/7/taches-post-integration
```

**Réponse**
```json
{
  "data": [
    { "etape": 11, "label": "Générer l'acte administratif",    "endpoint": "POST /integration/dossiers/7/generer-acte",     "statut": "fait",     "obligatoire": true  },
    { "etape": 13, "label": "Assigner le matricule",           "endpoint": "POST /integration/dossiers/7/assigner-matricule","statut": "non_fait", "obligatoire": true  },
    { "etape": 14, "label": "Affecter l'agent",                "endpoint": "POST /integration/affectations",                "statut": "non_fait", "obligatoire": true  },
    { "etape": 15, "label": "Nommer l'agent (responsabilité)", "endpoint": "POST /integration/nominations",                 "statut": "non_fait", "obligatoire": false },
    { "etape": 17, "label": "Remettre le matériel",            "endpoint": "POST /integration/remises-materiel",            "statut": "non_fait", "obligatoire": false },
    { "etape": 18, "label": "Confirmer la prise de service",   "endpoint": "POST /integration/prises-de-service",           "statut": "non_fait", "obligatoire": false }
  ],
  "rappel": "5 tâche(s) post-intégration en attente."
}
```

> Le champ `statut` passe automatiquement à `"fait"` dès que l'action correspondante est réalisée.

---

## Tâches post-intégration (différées)

Ces actions peuvent être réalisées dans n'importe quel ordre, après que le dossier est `INTEGRE`.

---

### Tâche 11 — Générer l'acte administratif

Le type d'acte est **déterminé automatiquement** depuis le type d'intégration du dossier — aucun champ à saisir.

**Requête**
```
POST /integration/dossiers/7/generer-acte
```
*(body vide)*

**Réponse `201` — exemple pour Recrutement externe (`necessite_contrat = false`)**
```json
{
  "data": {
    "acte": {
      "id": 1,
      "type_acte": "decision_recrutement",
      "numero": "ARTF-REC-2026-0001",
      "signe": false
    },
    "dossier": { "id": 7, "statut": "INTEGRE" },
    "necessite_contrat": false
  },
  "message": "Acte généré"
}
```

**Correspondance types → actes**

| Type d'intégration | Acte généré |
|--------------------|-------------|
| Recrutement externe | `ARTF-REC-YYYY-XXXX` |
| Mutation | `ARTF-MUT-YYYY-XXXX` |
| Détachement | `ARTF-DET-YYYY-XXXX` |
| Mise à disposition | `ARTF-NDS-YYYY-XXXX` |
| Réintégration | `ARTF-REC-YYYY-XXXX` |
| Contractuel | `ARTF-CTR-YYYY-XXXX` |
| Stage professionnel | `ARTF-CTR-YYYY-XXXX` |

---

### Tâche 12 — Marquer le contrat signé *(uniquement si `necessite_contrat = true`)*

> **Applicable uniquement aux types : Contractuel et Stage professionnel.**

**Requête**
```
POST /integration/dossiers/7/marquer-contrat-signe
```
*(body vide)*

---

### Tâche 13 — Assigner le matricule (fourni par le système externe)

Le matricule est saisi manuellement car il est généré par un système externe.

**Requête**
```
POST /integration/dossiers/7/assigner-matricule
```
```json
{
  "matricule": "ARTF-2026-000042"
}
```

**Réponse `200`**
```json
{
  "data": {
    "id": 7,
    "statut": "INTEGRE",
    "statut_label": "Intégré",
    "agent": {
      "id": 42,
      "matricule": "ARTF-2026-000042",
      "nom_complet": "Thierry LOUPPE"
    }
  },
  "message": "Matricule ARTF-2026-000042 assigné avec succès"
}
```

> **Contrainte** : le matricule doit être unique — une erreur `422` est retournée s'il est déjà attribué.

---

### Tâche 14 — Affecter l'agent

Détermine où l'agent exerce ses fonctions.  
La requête utilise `multipart/form-data` car la note de service est un fichier.

**Requête**
```
POST /integration/affectations
Content-Type: multipart/form-data
```

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `agent_id` | integer | Oui | ID de l'agent à affecter |
| `structurable_type` | string | Oui | Classe du lieu d'affectation (voir tableau ci-dessous) |
| `structurable_id` | integer | Oui | ID du lieu d'affectation |
| `date_affectation` | date | Oui | Date de prise d'effet |
| `motif` | string | Non | Motif textuel de l'affectation |
| `note_service` | file | Non | Fichier PDF/JPG/PNG, max 10 Mo |
| `superieur_hierarchique_id` | integer | Non | Résolu automatiquement si absent |

**Exemple Insomnia / Postman (form-data)**
```
agent_id              = 42
structurable_type     = App\Models\Bureau
structurable_id       = 2
date_affectation      = 2026-07-01
motif                 = Première affectation suite à recrutement
note_service          = [fichier] note_affectation.pdf
```
> `superieur_hierarchique_id` est **optionnel** : si non fourni, l'API remonte automatiquement la hiérarchie Bureau → Service → Direction pour trouver le responsable porteur d'une nomination active. Si aucun n'est trouvé, le champ reste `null` et le message de réponse le signale.

**Réponse `201`**
```json
{
  "data": {
    "id": 1,
    "agent_id": 42,
    "structurable_type": "App\\Models\\Bureau",
    "structurable_id": 2,
    "motif": "Première affectation suite à recrutement",
    "note_service": "affectations/42/notes-service/note_affectation.pdf",
    "note_service_nom_original": "note_affectation.pdf",
    "date_affectation": "2026-07-01",
    "date_fin": null,
    "statut": "en_attente_validation",
    "statut_label": "En attente de validation",
    "superieur_hierarchique_id": 5,
    "superieur_hierarchique": null,
    "validations": []
  },
  "message": "Affectation créée — circuit de validation initialisé"
}
```

**Types de structures disponibles**

| structurable_type | Description |
|------------------|-------------|
| `App\Models\Direction` | Affectation à une direction |
| `App\Models\Service` | Affectation à un service |
| `App\Models\Bureau` | Affectation à un bureau |

---

#### Cycle de vie de l'affectation

```
en_attente_validation
      │
      ├─► POST /validations/{id}/approuver  (×5 niveaux)
      │         └─► approuvee  (automatique quand le dernier niveau est validé)
      │                  │
      │                  └─► POST /affectations/1/activer  → active
      │                                                          │
      │                                                          └─► POST /affectations/1/terminer  → terminee
      │
      └─► POST /validations/{id}/rejeter  → rejetee  (à n'importe quel niveau)
          POST /affectations/1/rejeter    → rejetee  (rejet manuel, commentaire obligatoire)
```

---

#### Étape 14a — Valider le circuit de l'affectation

Les validations suivent le même circuit que les dossiers (5 niveaux hiérarchiques).  
Consulter les validations de l'affectation (le tableau `validations` est inclus dans la réponse `show`) :

```
GET /integration/affectations/1
```

Récupérer les IDs de validation depuis le champ `validations[]` de la réponse, puis approuver chaque niveau dans l'ordre :

```
POST /integration/validations/{validation_id}/approuver
```
```json
{ "commentaire": "Approuvé au niveau chef de bureau" }
```

**Comportement automatique** : dès que le 5ᵉ et dernier niveau est approuvé, le statut de l'affectation passe automatiquement à `approuvee` sans action supplémentaire.

Si un niveau est rejeté :
```
POST /integration/validations/{validation_id}/rejeter
```
```json
{ "commentaire": "Motif du rejet" }
```
→ Le statut passe immédiatement à `rejetee`.

---

#### Étape 14b — Activer l'affectation

Une fois le statut `approuvee`, activer l'affectation et mettre à jour le dossier :

```
POST /integration/affectations/1/activer
```
```json
{ "dossier_integration_id": 7 }
```

> - Clôture automatiquement l'affectation active précédente de l'agent si elle existe.
> - Met à jour le statut du dossier d'intégration à `AFFECTE` si `dossier_integration_id` est fourni.
> - **Erreur `422`** si l'affectation n'est pas en statut `approuvee`.

---

#### Autres actions sur l'affectation

**Rejeter manuellement** (commentaire obligatoire) :
```
POST /integration/affectations/1/rejeter
```
```json
{ "commentaire": "Structure inexistante à la date indiquée" }
```

**Terminer une affectation active** :
```
POST /integration/affectations/1/terminer
```
```json
{ "date_fin": "2026-12-31" }
```
> `date_fin` est optionnelle — si absente, la date du jour est utilisée.

**Consulter toutes les affectations d'un agent** :
```
GET /integration/agents/42/affectations
```

---

#### Affectation groupée — plusieurs agents, chacun vers sa propre structure

Crée une affectation distincte par agent. Chaque agent est affecté à **sa propre structure** avec **son propre supérieur hiérarchique** (résolu automatiquement si absent). Seuls `date_affectation`, `motif` et `note_service` sont communs à l'ensemble du lot.

```
POST /integration/affectations/groupee
Content-Type: multipart/form-data
```

**Champs communs (racine)**

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `date_affectation` | date | Oui | Date de prise d'effet commune à tous |
| `motif` | string | Non | Motif commun (ex : réorganisation) |
| `note_service` | file | Non | Document partagé — PDF/JPG/PNG, max 10 Mo |

**Par agent — `agents[i].*`**

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `agents[i][agent_id]` | integer | Oui | ID de l'agent (doit exister, distinct) |
| `agents[i][structurable_type]` | string | Oui | Classe de la structure de destination |
| `agents[i][structurable_id]` | integer | Oui | ID de la structure de destination |
| `agents[i][superieur_hierarchique_id]` | integer | Non | Résolu automatiquement par structure si absent |

**Exemple Insomnia / Postman (form-data)**
```
date_affectation                    = 2026-07-01
motif                               = Réorganisation trimestrielle
note_service                        = [fichier] note-service-lot.pdf

agents[0][agent_id]                 = 42
agents[0][structurable_type]        = App\Models\Bureau
agents[0][structurable_id]          = 2
agents[0][superieur_hierarchique_id]= (vide — résolution automatique)

agents[1][agent_id]                 = 43
agents[1][structurable_type]        = App\Models\Service
agents[1][structurable_id]          = 7
agents[1][superieur_hierarchique_id]= 15

agents[2][agent_id]                 = 44
agents[2][structurable_type]        = App\Models\Direction
agents[2][structurable_id]          = 3
agents[2][superieur_hierarchique_id]= (vide — résolution automatique)
```

> Le supérieur hiérarchique est résolu **individuellement** par agent, en fonction de la structure qui lui est propre.

**Réponse `201`**
```json
{
  "data": {
    "total": 3,
    "affectations": [
      { "id": 10, "agent_id": 42, "statut": "en_attente_validation", "statut_label": "En attente de validation", "superieur_hierarchique_id": 8 },
      { "id": 11, "agent_id": 43, "statut": "en_attente_validation", "statut_label": "En attente de validation", "superieur_hierarchique_id": 15 },
      { "id": 12, "agent_id": 44, "statut": "en_attente_validation", "statut_label": "En attente de validation", "superieur_hierarchique_id": 2 }
    ]
  },
  "message": "3 affectation(s) créée(s) — circuits de validation initialisés"
}
```

---

#### Générer la note de service PDF (unitaire)

Génère le PDF officiel de la note de service pour une affectation, le stocke sur le serveur et le retourne en téléchargement direct. La référence est automatique : `NS-AFF-{année}-{id:04d}`.

```
GET /integration/affectations/1/note-service
```
*(body vide — retourne le fichier PDF)*

> - Le fichier généré est stocké dans `affectations/{agent_id}/notes-service/generated/`.
> - L'affectation est mise à jour avec le nouveau `note_service` (chemin) et `note_service_nom_original`.
> - Peut être appelé plusieurs fois — régénère et écrase la version précédente.

**Contenu du PDF**
- En-tête ARTF avec référence et date
- Nom, prénom, matricule, grade, catégorie et échelon de l'agent
- Structure d'affectation avec hiérarchie complète (Bureau → Service → Direction)
- Date d'effet, motif, supérieur hiérarchique
- Blocs de signature (supérieur + Directeur Général)

---

#### Générer les notes de service en lot (ZIP)

Génère les PDFs pour une liste d'affectations et les archive dans un fichier ZIP retourné en téléchargement. Maximum 50 affectations par lot.

```
POST /integration/affectations/notes-service/lot
Content-Type: application/json
```
```json
{
  "affectation_ids": [10, 11, 12]
}
```
*(retourne un fichier ZIP)*

> Le ZIP est nommé `notes-service-affectations-{date}.zip` et contient un PDF par affectation nommé `NS-AFF-{année}-{id:04d}.pdf`.

**Erreurs possibles**
- `422` si `affectation_ids` est vide ou dépasse 50 éléments
- Les affectations introuvables sont silencieusement ignorées (les autres PDFs sont inclus)

---

#### Récapitulatif — tous les endpoints affectation

| Méthode | Endpoint | Action | Body |
|---------|----------|--------|------|
| `POST` | `/integration/affectations` | Créer une affectation (unitaire) | `multipart/form-data` |
| `POST` | `/integration/affectations/groupee` | Créer plusieurs affectations (structure propre par agent, note de service commune) | `multipart/form-data` |
| `GET` | `/integration/affectations` | Lister toutes les affectations | — |
| `GET` | `/integration/affectations/{id}` | Détail + validations d'une affectation | — |
| `GET` | `/integration/agents/{id}/affectations` | Affectations d'un agent | — |
| `POST` | `/integration/affectations/{id}/activer` | Activer (requiert statut `approuvee`) | `{ dossier_integration_id? }` |
| `POST` | `/integration/affectations/{id}/rejeter` | Rejeter manuellement | `{ commentaire }` |
| `POST` | `/integration/affectations/{id}/terminer` | Terminer une affectation active | `{ date_fin? }` |
| `GET` | `/integration/affectations/{id}/note-service` | Générer et télécharger le PDF | — |
| `POST` | `/integration/affectations/notes-service/lot` | Générer un ZIP de PDFs | `{ affectation_ids[] }` |
| `POST` | `/integration/validations/{id}/approuver` | Approuver un niveau de validation | `{ commentaire? }` |
| `POST` | `/integration/validations/{id}/rejeter` | Rejeter un niveau (→ affectation `rejetee`) | `{ commentaire }` |

---

### Tâche 15 — Nommer l'agent *(optionnel — postes de responsabilité)*

Uniquement pour les agents nommés à un poste de responsabilité.  
**Clôture automatiquement** l'ancienne nomination active sur la même structure.

**Requête**
```
POST /integration/nominations
```
```json
{
  "agent_id": 42,
  "poste": "Chef de Bureau",
  "structurable_type": "App\\Models\\Bureau",
  "structurable_id": 2,
  "date_debut": "2026-07-01",
  "type_acte": "decision"
}
```

Après circuit de validation, activer :
```
POST /integration/nominations/1/activer
```
```json
{ "dossier_integration_id": 7 }
```

---

### Tâche 17 — Remettre le matériel

Enregistre la remise des équipements de travail.

**Requête**
```
POST /integration/remises-materiel
```
```json
{
  "agent_id": 42,
  "affectation_id": 1,
  "date_remise": "2026-07-01",
  "materiel": [
    "ordinateur portable Dell",
    "téléphone professionnel",
    "badge d'accès bâtiment A",
    "clés bureau 214"
  ]
}
```

---

### Tâche 18 — Confirmer la prise de service

Le responsable hiérarchique confirme l'installation de l'agent.

**Requête**
```
POST /integration/prises-de-service
```
```json
{
  "agent_id": 42,
  "dossier_integration_id": 7,
  "responsable_id": 5,
  "date_prise_service": "2026-07-01",
  "confirmation_presence": true,
  "confirmation_installation": true,
  "confirmation_equipements": true,
  "observations": "Agent opérationnel, poste de travail configuré"
}
```

---

## Consultation de l'historique complet

```
GET /integration/dossiers/7/historique
```

```json
{
  "data": [
    {
      "action": "transition_statut",
      "ancienne_valeur": { "statut": "VALIDE_DG" },
      "nouvelle_valeur": { "statut": "INTEGRE" },
      "commentaire": "Intégration administrative finalisée",
      "utilisateur": { "name": "Admin" },
      "created_at": "2026-06-19T11:45:00"
    }
  ]
}
```

---

## Récapitulatif des transitions et statuts

```
BROUILLON
    └─► SOUMIS                         POST /dossiers/7/soumettre
          └─► EN_ETUDE_RH              POST /dossiers/7/passer-en-etude-rh
                ├─► DOSSIER_INCOMPLET  POST /dossiers/7/marquer-incomplet
                │       └─► EN_ETUDE_RH (correction)
                └─► DOSSIER_COMPLET    POST /dossiers/7/marquer-complet
                          └─► VALIDE_RH  POST /dossiers/7/valider-rh
                                └─► (circuit 5 validations)
                                      └─► VALIDE_DG  (auto après DG)
                                            │
                                            └─► INTEGRE ✓  POST /dossiers/7/integrer
                                                            (compte créé automatiquement)
                                                            └─► taches_post_integration:
                                                                  ├─ [11] Générer l'acte
                                                                  ├─ [12] Contrat signé (si necessite_contrat)
                                                                  ├─ [13] Assigner le matricule
                                                                  ├─ [14] Affecter l'agent
                                                                  ├─ [15] Nomination (optionnel)
                                                                  ├─ [17] Remise matériel (optionnel)
                                                                  └─ [18] Prise de service (optionnel)

À tout moment :
    └─► REJETE    POST /dossiers/7/rejeter-rh  ou  /validations/x/rejeter
    └─► SUSPENDU  POST /dossiers/7/suspendre
    └─► ANNULE    POST /dossiers/7/annuler
```

### Cycle de vie d'une affectation (StatutAffectation)

```
                                   POST /affectations          POST /affectations/groupee
                                         │                              │
                                         └──────────────────────────────┘
                                                        │
                                              en_attente_validation
                                                        │
                          POST /validations/{id}/approuver  (×5 niveaux, dans l'ordre)
                                                        │
                                              approuvee  ◄── automatique au 5ᵉ niveau
                                                        │
                          POST /affectations/{id}/activer  (+ dossier_integration_id optionnel)
                                                        │
                                                      active
                                                        │
                          POST /affectations/{id}/terminer  (+ date_fin optionnelle)
                                                        │
                                                    terminee  ✓

À tout moment avant activation :
    └─► rejetee   POST /affectations/{id}/rejeter  (commentaire obligatoire)
                  POST /validations/{id}/rejeter   (n'importe quel niveau du circuit)

Génération PDF (disponible sur n'importe quel statut) :
    GET  /affectations/{id}/note-service         → PDF unitaire (NS-AFF-{année}-{id}.pdf)
    POST /affectations/notes-service/lot         → ZIP de PDFs (max 50)
```

---

## Cas d'erreurs courants

| Symptôme | Cause | Solution |
|----------|-------|----------|
| Redirigé vers page HTML | Header `Accept` manquant | Ajouter `Accept: application/json` |
| `401 Unauthorized` | Token absent ou expiré | Refaire `/login` et mettre à jour le token |
| `422 Unprocessable` sur transition | Transition de statut invalide | Respecter l'ordre des étapes ci-dessus |
| `422` sur `/integrer` | Dossier pas à `VALIDE_DG` | Compléter le circuit hiérarchique d'abord (étapes 1-10) |
| `422` sur `/assigner-matricule` | Matricule déjà attribué | Vérifier via `GET /integration/agents?matricule=...` |
| `422` sur `/assigner-matricule` | Pas d'agent lié au dossier | Créer l'agent à l'étape 2 |
| `422` sur création dossier | `type_integration_id` inexistant | Vérifier via `GET /types-integrations` |
| `422` sur affectation | `structurable_id` inexistant | Vérifier via `GET /bureaux` ou `GET /services` |
| `422` sur `/affectations/{id}/activer` | Statut ≠ `approuvee` | Compléter les 5 niveaux du circuit de validation d'abord |
| `422` sur `/affectations/{id}/rejeter` | Statut terminal (`active`, `terminee`) | Le rejet n'est possible qu'avant activation |
| `422` sur `/affectations/{id}/terminer` | Statut ≠ `active` | Activer l'affectation avant de la terminer |
| `422` sur `/validations/{id}/rejeter` (affectation) | `commentaire` absent | Le commentaire est obligatoire pour un rejet |
| `superieur_hierarchique_id` est `null` | Aucune nomination active dans la structure ou ses parents | Créer une nomination active (tâche 15) ou fournir `superieur_hierarchique_id` manuellement |
| Upload `note_service` échoue | Format non supporté ou taille > 10 Mo | Utiliser PDF, JPG ou PNG, taille ≤ 10 Mo |
| `422` sur affectation groupée | Même `agent_id` présent deux fois dans `agents` | Chaque agent doit apparaître une seule fois (`distinct`) |
| `422` sur affectation groupée | `structurable_type` ou `structurable_id` absent pour un agent | Chaque entrée `agents[i]` doit avoir sa propre structure |
| `422` sur lot notes de service | Plus de 50 IDs fournis | Diviser en plusieurs requêtes de 50 max |
| PDF vide ou mal rendu | Agent sans grade/matricule | Compléter le profil agent avant de générer le PDF |
| `diplome_id` fourni mais grade non rempli | Diplôme sans classe grille liée | Relancer `php artisan db:seed --class=DiplomeSeeder` |
| Compte non créé après `/integrer` | Agent sans `agent_id` sur le dossier | Vérifier que l'agent est bien lié à l'étape 2 |
