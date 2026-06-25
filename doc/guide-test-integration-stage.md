# Guide de test — Intégration & Module Stage

> Base URL : `http://localhost:8000/api`  
> Outil recommandé : Insomnia ou Postman  
> Documents liés : [`guide-test-integration.md`](./guide-test-integration.md) · [`module-stage.md`](./module-stage.md)

> Ce guide couvre le **parcours complet stagiaire** : entrée via le dossier d'intégration, puis suivi et clôture via `ConventionStage`.  
> Les étapes communes au circuit d'intégration (soumission, RH, documents…) reprennent la même logique que le guide agent permanent.

Headers obligatoires sur **toutes** les requêtes :
```
Accept: application/json
Authorization: Bearer {token}
```

---

## 0. Authentification

```
POST /login
```
```json
{
  "email": "admin@artf.cg",
  "password": "password"
}
```

> Copier le `token` retourné pour toutes les requêtes suivantes.

---

## Prérequis base de données

Après migration, exécuter les seeders utiles au test stage :

```bash
php artisan migrate
php artisan db:seed --class=TypeIntegrationSeeder
php artisan db:seed --class=TypeDocumentSeeder
php artisan db:seed --class=TypeContratSeeder
php artisan db:seed --class=FonctionSeeder
```

---

## Référentiels stage

### Types d'intégration stage

```
GET /types-integrations
```

| id | Nom | `necessite_contrat` | `necessite_validation_dg` | `prefixe_matricule` | `type_stage` recopié |
|----|-----|---------------------|---------------------------|---------------------|----------------------|
| 7 | Stage professionnel | `true` | `false` | `STG` | `professionnel` |
| 8 | Stage académique | `true` | `false` | `STG` | `academique` |
| 9 | Stage de qualification | `true` | `true` | `STG` | `qualification` |

> Les `id` peuvent varier selon l'environnement — toujours vérifier via `GET /types-integrations`.

### Autres référentiels utiles

| Ressource | Endpoint | Valeur attendue (seed frais) |
|-----------|----------|------------------------------|
| Type contrat Stage | `GET /types-contrats` | `type_contrat_id` = **3** (`Stage` / `STG`) |
| Fonction Stagiaire | `GET /fonctions` | `fonction_id` = **8** (`Stagiaire` / `STG`) |

### Documents d'entrée

Consulter les types disponibles :
```
GET /types-documents
```

**Communs à tous les types de stage**

| Libellé | Obligatoire |
|---------|-------------|
| Curriculum vitae | Oui |
| Demande de stage adressée au Directeur Général | Oui |
| Lettre de recommandation de l'établissement | Oui |
| Convention de stage | Oui |

**Complémentaires selon le type**

| Type | Pièces additionnelles |
|------|------------------------|
| Stage académique | Certificat de scolarité, Attestation d'inscription |
| Stage professionnel | — |
| Stage de qualification | Décision de mise en stage |

> Retenir les `type_document_id` retournés par l'API (les numéros ci-dessous sont indicatifs après seed frais : 1, 10, 11, 12…).

---

# Partie A — Intégration du stagiaire

Le dossier d'intégration est le point d'entrée unique. Aucune entité « demande de stage » séparée.

---

## A1 — Créer la fiche stagiaire + dossier

Un seul appel crée l'agent et le dossier (`BROUILLON`).

```
POST /integration/agents
```
```json
{
  "nom": "MOBILA",
  "prenom": "Jean",
  "date_naissance": "2001-05-12",
  "lieu_naissance": "Brazzaville",
  "nationalite": "Congolaise",
  "genre": "M",
  "telephone": "+242 06 555 12 34",
  "email_personnel": "j.mobila@email.cg",
  "type_integration_id": 7,
  "fonction_id": 8,
  "notes": "Université Marien Ngouabi — Licence Informatique"
}
```

| Champ | Remarque |
|-------|----------|
| `type_integration_id` | `7` = professionnel, `8` = académique, `9` = qualification |
| `fonction_id` | `8` = Stagiaire |
| `diplome_id` / `grade_id` | Optionnels pour un stagiaire |
| `notes` | Sera recopié dans `ConventionStage.etablissement` à l'intégration |

**Réponse `201` (extrait)**
```json
{
  "data": {
    "agent": { "id": 43, "matricule": null, "statut": "actif" },
    "dossier": {
      "id": 8,
      "reference": "ARTF-INT-2026-000008",
      "statut": "BROUILLON",
      "type_integration_id": 7,
      "agent_id": 43
    }
  }
}
```

> Retenir `agent_id` (**43**) et `dossier_id` (**8**).

---

## A2 — Créer la convention de stage (contrat STG)

```
POST /integration/contrats
```
```json
{
  "agent_id": 43,
  "dossier_integration_id": 8,
  "type_contrat_id": 3,
  "fonction_id": 8,
  "date_debut": "2026-07-01",
  "date_fin": "2026-12-31",
  "remuneration": 150000
}
```

| Champ | Remarque |
|-------|----------|
| `type_contrat_id` | `3` = Stage |
| `remuneration` | Gratification mensuelle (peut être `0`) |
| `date_debut` / `date_fin` | Recopiées sur `ConventionStage` à l'intégration |

---

## A3 — Circuit d'intégration (étapes RH)

Suivre le même enchaînement que [`guide-test-integration.md`](./guide-test-integration.md) :

| Étape | Endpoint | Statut obtenu |
|-------|----------|---------------|
| Soumettre | `POST /integration/dossiers/8/soumettre` | `SOUMIS` |
| Prise en charge RH | `POST /integration/dossiers/8/passer-en-etude-rh` | `EN_ETUDE_RH` |
| Déposer les pièces | `POST /integration/dossiers/8/documents` | — |
| Valider chaque pièce | `POST /integration/documents/{id}/valider` | — |
| Dossier complet | `POST /integration/dossiers/8/marquer-complet` | `DOSSIER_COMPLET` |
| Validation RH | `POST /integration/dossiers/8/valider-rh` | `VALIDE_RH` |
| Circuit hiérarchique | `POST /integration/validations/{id}/approuver` | → `VALIDE_DG` |
| Générer l'acte | `POST /integration/dossiers/8/generer-acte` | `ACTE_GENERE` |
| Contrat signé | `POST /integration/dossiers/8/marquer-contrat-signe` | `CONTRAT_SIGNE` |
| Matricule STG | `POST /integration/dossiers/8/assigner-matricule` | `MATRICULE_CREE` |
| Affectation | `POST /integration/affectations` + `/activer` | `AFFECTE` |
| Prise de service | `POST /integration/prises-de-service` | `PRISE_DE_SERVICE` |
| Intégrer | `POST /integration/dossiers/8/integrer` | `INTEGRE` |

**Matricule stagiaire**
```json
{ "matricule": "STG-2026-000001" }
```

**Pièces à déposer (exemple upload)**

```
POST /integration/dossiers/8/documents
Content-Type: multipart/form-data
```

| Champ | Valeur |
|-------|--------|
| `type_document_id` | id du type « Demande de stage adressée au Directeur Général » |
| `est_obligatoire` | `true` |
| `fichier` | PDF |

Répéter pour : CV, Lettre de recommandation, Convention de stage (+ pièces complémentaires selon le type).

### Particularités stage

| Sujet | Comportement MVP |
|-------|------------------|
| Validation DG | Désactivée en config pour pro / académique ; **le circuit actuel exige tout de même `VALIDE_DG`** avant `generer-acte` |
| Compte utilisateur | `necessite_compte_utilisateur = false` → **étape compte optionnelle / à sauter** |
| Nomination | Optionnelle (fonction Stagiaire déjà sur le contrat) |
| Remise matériel | Optionnelle selon le service d'accueil |

---

## A4 — Finaliser l'intégration (déclencheur module Stage)

```
POST /integration/dossiers/8/integrer
```

**Automatismes déclenchés** (types dont le nom commence par « Stage ») :

1. `Agent.statut` → `stagiaire`
2. Création automatique d'une `ConventionStage` :
   - `type_stage` déduit du nom du `TypeIntegration`
   - `date_debut` / `date_fin` depuis le contrat actif
   - `etablissement` depuis `dossier.notes`
   - `statut_stage` → `EN_COURS`

**Réponse `200` (extrait)**
```json
{
  "data": {
    "statut": "INTEGRE",
    "agent": {
      "matricule": "STG-2026-000001",
      "statut": "stagiaire",
      "nom_complet": "Jean MOBILA"
    }
  },
  "message": "Intégration administrative finalisée avec succès"
}
```

> Passer à la **Partie B** pour vérifier et gérer la convention.

---

# Partie B — Module Stage (suivi & clôture)

Base : `/integration/stages`

---

## B1 — Consulter la convention créée

```
GET /integration/stages/{id}
```

Si l'id est inconnu, retrouver la convention par agent :
```
GET /integration/stages?agent_id=43
```

**Réponse `200`**
```json
{
  "data": {
    "id": 1,
    "type_stage": "professionnel",
    "type_stage_label": "Stage professionnel",
    "etablissement": "Université Marien Ngouabi — Licence Informatique",
    "date_debut": "2026-07-01",
    "date_fin": "2026-12-31",
    "statut_stage": "EN_COURS",
    "statut_stage_label": "En cours",
    "jours_avant_fin": 192,
    "note_finale": null,
    "appreciation": null,
    "agent": {
      "id": 43,
      "matricule": "STG-2026-000001",
      "statut": "stagiaire",
      "nom_complet": "Jean MOBILA"
    },
    "tuteur_interne": null,
    "dossier_integration_id": 8
  }
}
```

---

## B2 — Lister les stages (filtres)

```
GET /integration/stages
```

| Paramètre | Valeurs | Exemple |
|-----------|---------|---------|
| `statut_stage` | `EN_COURS`, `TERMINE`, `ROMPU` | `?statut_stage=EN_COURS` |
| `type_stage` | `professionnel`, `academique`, `qualification` | `?type_stage=academique` |
| `agent_id` | id numérique | `?agent_id=43` |

Combinable : `GET /integration/stages?statut_stage=EN_COURS&type_stage=professionnel`

---

## B3 — Prolonger un stage

```
PATCH /integration/stages/1/prolonger
```
```json
{
  "date_fin": "2027-03-31"
}
```

| Règle | Détail |
|-------|--------|
| Statut requis | `EN_COURS` uniquement |
| Date | Strictement postérieure à `date_fin` actuelle et dans le futur |

**Réponse `200`**
```json
{
  "data": {
    "id": 1,
    "date_fin": "2027-03-31",
    "statut_stage": "EN_COURS"
  },
  "message": "Convention prolongée jusqu'au 2027-03-31"
}
```

> L'upload d'un avenant convention est prévu en version complète ; le MVP met à jour la date uniquement.

---

## B4 — Clôturer un stage

Lance la clôture administrative complète.

```
POST /integration/stages/1/cloturer
```
```json
{
  "note": 16.5,
  "appreciation": "Stagiaire sérieux et impliqué. Bonne capacité d'adaptation et contribution positive aux projets du bureau."
}
```

| Champ | Règle |
|-------|-------|
| `note` | Numérique, entre `0` et `20` |
| `appreciation` | Texte, minimum 10 caractères |

**Automatismes en arrière-plan**

| # | Action |
|---|--------|
| 1 | `Contrat` actif → `resilie` |
| 2 | `Nomination` active → `cloturee` |
| 3 | `Affectation` active → `terminee` |
| 4 | `Agent.statut` → `inactif` |
| 5 | `ConventionStage.statut_stage` → `TERMINE` + évaluation enregistrée |

**Réponse `200` (extrait)**
```json
{
  "data": {
    "statut_stage": "TERMINE",
    "note_finale": 16.50,
    "agent": { "statut": "inactif", "matricule": "STG-2026-000001" }
  },
  "message": "Stage clôturé avec succès"
}
```

---

## B5 — Télécharger l'attestation (PDF)

```
GET /integration/stages/1/attestation
```

**Réponse** : flux PDF (`Content-Type: application/pdf`)  
Nom suggéré : `attestation-stage-STG-2026-000001.pdf`

Contenu :
- En-tête ARTF
- Identité du stagiaire (nom, matricule, établissement, type)
- Période du stage
- Note et appréciation
- Emplacements signatures (tuteur + DG)

---

## Récapitulatif du parcours complet

```
[Partie A — Intégration]
Création agent (type stage) + dossier BROUILLON
        ↓
Contrat STG (dates + gratification)
        ↓
Circuit RH → documents stage → validations → acte → contrat signé
        ↓
Matricule STG-YYYY-XXXXXX → affectation → prise de service
        ↓
POST /dossiers/{id}/integrer  →  INTEGRE
        │
        ├─ Agent.statut = stagiaire
        └─ ConventionStage créée (EN_COURS)

[Partie B — Module Stage]
GET  /stages              → consultation / filtres
PATCH /stages/{id}/prolonger  → prolongation
POST /stages/{id}/cloturer    → clôture + automatismes
GET  /stages/{id}/attestation → PDF
```

---

## Scénarios de test recommandés

| # | Scénario | Type | Vérification clé |
|---|----------|------|------------------|
| 1 | Stage professionnel bout en bout | id 7 | `type_stage=professionnel`, matricule `STG-` |
| 2 | Stage académique + pièces scolarité | id 8 | Documents académiques déposés |
| 3 | Stage qualification + validation DG | id 9 | Circuit DG complet avant acte |
| 4 | Prolongation | tout | `date_fin` mise à jour, statut reste `EN_COURS` |
| 5 | Clôture | tout | Agent `inactif`, contrat `resilie`, convention `TERMINE` |
| 6 | Attestation PDF | après clôture | PDF téléchargeable avec note / appréciation |

---

## Cas d'erreurs courants — Stage

| Symptôme | Cause | Solution |
|----------|-------|----------|
| Pas de `ConventionStage` après `INTEGRE` | Type d'intégration non stage | Vérifier que le nom commence par « Stage » |
| `statut` agent reste `actif` | Idem | `GET /types-integrations` |
| `422` sur `/prolonger` | Convention pas `EN_COURS` | `GET /integration/stages/{id}` |
| `422` sur `/prolonger` | Date invalide | Date future > `date_fin` actuelle |
| `422` sur `/cloturer` | Déjà `TERMINE` ou `ROMPU` | Impossible de reclôturer |
| `422` sur `/cloturer` | `note` ou `appreciation` invalide | Respecter les règles de validation |
| Erreur 500 sur attestation | Template PDF absent | Vérifier `resources/views/pdf/attestation-stage.blade.php` |
| Matricule non `STG-` | Seeder non à jour | `php artisan db:seed --class=TypeIntegrationSeeder` |
| Documents stage introuvables | Seeder documents | `php artisan db:seed --class=TypeDocumentSeeder` |

Pour les erreurs du circuit d'intégration générique, voir [`guide-test-integration.md`](./guide-test-integration.md#cas-derreurs-courants).

---

*Dernière mise à jour : Juin 2026*
