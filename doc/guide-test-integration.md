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

> Le matricule n'est pas saisi ici — il sera fourni plus tard par le système externe (étape 13).

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

## Étape 11 — Génération automatique de l'acte administratif 🆕

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
    "dossier": { "id": 7, "statut": "MATRICULE_CREE" },
    "necessite_contrat": false,
    "prochaine_etape": "matricule_cree"
  },
  "message": "Acte généré — passage direct à la création du matricule (pas de contrat requis)"
}
```

**Réponse `201` — exemple pour Contractuel (`necessite_contrat = true`)**
```json
{
  "data": {
    "acte": {
      "id": 2,
      "type_acte": "contrat",
      "numero": "ARTF-CTR-2026-0001",
      "signe": false
    },
    "dossier": { "id": 7, "statut": "ACTE_GENERE" },
    "necessite_contrat": true,
    "prochaine_etape": "contrat_signe"
  },
  "message": "Acte généré — veuillez enregistrer la signature du contrat avant de créer le matricule"
}
```

**Correspondance types → actes**

| Type d'intégration | Acte généré | `necessite_contrat` |
|--------------------|-------------|---------------------|
| Recrutement externe | `ARTF-REC-YYYY-XXXX` | `false` → passe directement à MATRICULE_CREE |
| Mutation | `ARTF-MUT-YYYY-XXXX` | `false` → passe directement à MATRICULE_CREE |
| Détachement | `ARTF-DET-YYYY-XXXX` | `false` → passe directement à MATRICULE_CREE |
| Mise à disposition | `ARTF-NDS-YYYY-XXXX` | `false` → passe directement à MATRICULE_CREE |
| Réintégration | `ARTF-REC-YYYY-XXXX` | `false` → passe directement à MATRICULE_CREE |
| Contractuel | `ARTF-CTR-YYYY-XXXX` | `true` → étape 12 requise |
| Stage professionnel | `ARTF-CTR-YYYY-XXXX` | `true` → étape 12 requise |

---

## Étape 12 — Marquer le contrat signé *(uniquement si `necessite_contrat = true`)*

> **Skip si `necessite_contrat = false` — le dossier est déjà à `MATRICULE_CREE`.**

Statut → **`CONTRAT_SIGNE`**

**Requête**
```
POST /integration/dossiers/7/marquer-contrat-signe
```
*(body vide)*

---

## Étape 13 — Assigner le matricule (fourni par le système externe) 🆕

Le matricule est saisi manuellement car il est généré par un système externe.  
Statut → **`MATRICULE_CREE`**

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
    "statut": "MATRICULE_CREE",
    "statut_label": "Matricule créé",
    "agent": {
      "id": 42,
      "matricule": "ARTF-2026-000042",
      "nom_complet": "Thierry LOUPPE"
    }
  },
  "message": "Matricule ARTF-2026-000042 assigné avec succès"
}
```

> **Contrainte** : le matricule doit être unique dans la table `agents` — une erreur `422` est retournée s'il est déjà attribué.

---

## Étape 14 — Affecter l'agent

Détermine où l'agent exerce ses fonctions. Statut affectation → **`en_attente_validation`**

**Requête**
```
POST /integration/affectations
```
```json
{
  "agent_id": 42,
  "structurable_type": "App\\Models\\Bureau",
  "structurable_id": 2,
  "date_affectation": "2026-07-01",
  "motif": "Première affectation suite à recrutement",
  "superieur_hierarchique_id": 5
}
```

Après les approbations du circuit, activer l'affectation :
```
POST /integration/affectations/1/activer
```
```json
{ "dossier_integration_id": 7 }
```

Statut dossier → **`AFFECTE`**

**Types de structures disponibles**

| structurable_type | Description |
|------------------|-------------|
| `App\Models\Direction` | Affectation à une direction |
| `App\Models\Service` | Affectation à un service |
| `App\Models\Bureau` | Affectation à un bureau |

---

## Étape 15 — Nommer l'agent *(optionnel — postes de responsabilité)*

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

Statut dossier → **`NOMME`**

---

## Étape 16 — Créer le compte utilisateur

Génère automatiquement le compte applicatif de l'agent. Statut → **`COMPTE_CREE`**

**Requête**
```
POST /integration/comptes/provisionner
```
```json
{
  "agent_id": 42,
  "dossier_integration_id": 7
}
```

**Réponse `201`**
```json
{
  "data": {
    "login": "tlouppe",
    "email_professionnel": "tlouppe@artf.cg",
    "badge_numero": "ARTF-BADGE-00042",
    "mot_de_passe_provisoire_envoye": false
  },
  "message": "Compte créé — login : tlouppe, email : tlouppe@artf.cg"
}
```

---

## Étape 17 — Remettre le matériel

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

## Étape 18 — Confirmer la prise de service

Le responsable hiérarchique confirme l'installation de l'agent. Statut → **`PRISE_DE_SERVICE`**

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

## Étape 19 — Finaliser l'intégration

Clôture définitive du processus. Statut → **`INTEGRE`** ✓

**Requête**
```
POST /integration/dossiers/7/integrer
```

**Réponse `200`**
```json
{
  "data": {
    "reference": "ARTF-INT-2026-000007",
    "statut": "INTEGRE",
    "statut_label": "Intégré",
    "agent": {
      "matricule": "ARTF-2026-000042",
      "nom_complet": "Thierry LOUPPE"
    }
  },
  "message": "Intégration administrative finalisée avec succès"
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
      "ancienne_valeur": { "statut": "PRISE_DE_SERVICE" },
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
                                            └─► ACTE_GENERE  POST /dossiers/7/generer-acte (auto)
                                                  │
                                    ┌─────────────┴──────────────┐
                              sans contrat                  avec contrat
                                    │                            │
                              MATRICULE_CREE         CONTRAT_SIGNE (marquer-contrat-signe)
                                    │                            │
                                    └────────────────────────────┘
                                              MATRICULE_CREE  POST /dossiers/7/assigner-matricule
                                                    └─► AFFECTE   POST /affectations + /activer
                                                          └─► NOMME (optionnel)
                                                                └─► COMPTE_CREE  POST /comptes/provisionner
                                                                      └─► PRISE_DE_SERVICE
                                                                            └─► INTEGRE ✓

À tout moment :
    └─► REJETE    POST /dossiers/7/rejeter-rh  ou  /validations/x/rejeter
    └─► SUSPENDU  POST /dossiers/7/suspendre
    └─► ANNULE    POST /dossiers/7/annuler
```

---

## Cas d'erreurs courants

| Symptôme | Cause | Solution |
|----------|-------|----------|
| Redirigé vers page HTML | Header `Accept` manquant | Ajouter `Accept: application/json` |
| `401 Unauthorized` | Token absent ou expiré | Refaire `/login` et mettre à jour le token |
| `422 Unprocessable` sur transition | Transition de statut invalide | Respecter l'ordre des étapes ci-dessus |
| `422` sur `/generer-acte` | Dossier pas à `VALIDE_DG` | Compléter le circuit hiérarchique d'abord |
| `422` sur `/generer-acte` | Aucun acte configuré sur le type | Relancer le seeder `TypeIntegrationSeeder` |
| `422` sur `/assigner-matricule` | Matricule déjà attribué | Vérifier via `GET /integration/agents?matricule=...` |
| `422` sur `/assigner-matricule` | Pas d'agent lié au dossier | Créer l'agent à l'étape 2 et lier via `agent_id` à l'étape 2-bis |
| `422` sur création dossier | `type_integration_id` inexistant | Vérifier via `GET /types-integrations` |
| `422` sur affectation | `structurable_id` inexistant | Vérifier via `GET /bureaux` ou `GET /services` |
| `diplome_id` fourni mais grade non rempli | Diplôme sans classe grille liée | Relancer `php artisan db:seed --class=DiplomeSeeder` |
