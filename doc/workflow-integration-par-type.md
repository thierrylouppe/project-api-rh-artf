# Workflow d'intégration par type

> Dernière mise à jour : 2026-08-10  
> Références : [`integration.md`](./integration.md) · [`guide-test-integration.md`](./guide-test-integration.md) · [`guide-test-integration-stage.md`](./guide-test-integration-stage.md) · [`ma_proposition.md`](./ma_proposition.md)

Ce document décrit le **comportement API réel** selon le type d'intégration, et les **deux chemins** supportés par le frontend.

---

## 1. Flags métier (`TypeIntegration`)

| Flag | Effet runtime |
|------|----------------|
| `necessite_contrat` | Affiche / exige l'étape contrat ; tâches post-intégration 12 (signature, salaire) |
| `necessite_validation_dg` | Si `false` : niveau `directeur_general` retiré du circuit ; si circuit vide → auto `VALIDE_DG` |
| `necessite_compte_utilisateur` | Si `true` : provisionnement auto du compte à `/integrer` + tâche 16 |
| `type_acte_administratif` | Type d'acte généré par `POST …/generer-acte` |
| `prefixe_matricule` | Indication FE / métier (`ARTF`, `STG`…) |
| `estUnStage()` | Convention de stage auto à `/integrer` ; pas de nomination ni salaire grille dans les tâches |

---

## 2. Matrice par type (seed)

| Type | Contrat | DG | Compte | Acte | Préfixe | Particularité |
|------|---------|----|--------|------|---------|---------------|
| Recrutement externe | Non | Oui | Oui | `decision_recrutement` | ARTF | Parcours permanent |
| Mutation | Non | Oui | Oui | `decision_mutation` | ARTF | Pièces mutation |
| Détachement | Non | Oui | Oui | `arrete_detachement` | ARTF | Temporaire |
| Mise à disposition | Non | Oui | Oui | `note_de_service` | ARTF | Convention MAD |
| Réintégration | Non | Oui | Oui | `decision_recrutement` | ARTF | Agent déjà connu |
| Contractuel | **Oui** | Oui | Oui | `contrat` | ARTF | CDI/CDD + salaire |
| Stage professionnel | **Oui** | **Non** | **Non** | `contrat` | STG | ConventionStage |
| Stage académique | **Oui** | **Non** | **Non** | `contrat` | STG | + scolarité |
| Stage de qualification | **Oui** | **Oui** | **Non** | `contrat` | STG | DG requis |

---

## 3. Flux commun (avant intégration)

```
1. Sélection type d'intégration
2. Création fiche agent + dossier (BROUILLON)
3. Contrat (si necessite_contrat)
4. Dépôt des pièces justificatives
5. Validation documents → DOSSIER_COMPLET
6. Validation RH → circuit hiérarchique (DG filtré si necessite_validation_dg=false)
7. Fin de circuit → VALIDE_DG
```

Puis l'un des deux chemins ci-dessous.

---

## 4. Deux chemins supportés

### Chemin A — Séquentiel (statuts workflow)

```
VALIDE_DG
  → POST …/generer-acte          → ACTE_GENERE
  → [marquer-contrat-signe]      → CONTRAT_SIGNE   (si necessite_contrat)
  → assigner-matricule           → MATRICULE_CREE  (auto si pas de contrat après acte)
  → [carrière : affectation / nomination] / compte / matériel / prise de service
  → POST …/integrer              → INTEGRE
```

> Affectation et nomination sont des actes de **carrière** (`/api/carriere/…`). Elles ne font plus évoluer le statut du dossier (`AFFECTE` / `NOMME` ne sont plus ciblés).

> Peu utilisé par le FE actuel ; conservé pour compatibilité et parcours métier strict.

### Chemin B — Post-`integrer` (chemin FE actuel)

```
VALIDE_DG
  → POST …/integrer              → INTEGRE
       ├─ compte auto si necessite_compte_utilisateur
       └─ ConventionStage + statut stagiaire si stage
  → tâches post-intégration (ordre libre) :
       generer-acte | contrat signé | matricule |
       [affectation via /carriere] | [nomination via /carriere] |
       [compte] | matériel | prise de service
```

Sur le chemin B, `generer-acte`, `assigner-matricule` et `marquer-contrat-signe` **ne changent pas** le statut du dossier (reste `INTEGRE`).

---

## 5. Tâches post-intégration (filtrées)

| Étape | Tâche | Permanent | Contractuel | Stage |
|------:|-------|-----------|-------------|-------|
| 11 | Générer l'acte | ✓ obligatoire | ✓ | ✓ |
| 12 | Contrat signé | — | ✓ optionnel | ✓ optionnel |
| 12 | Salaire initial | — | ✓ optionnel | — |
| 13 | Matricule | ✓ obligatoire | ✓ | ✓ |
| 14 | Affectation (`POST /carriere/affectations`) | ✓ optionnel | ✓ optionnel | ✓ optionnel |
| 15 | Nomination (`POST /carriere/nominations`) | ✓ optionnel | ✓ optionnel | — |
| 16 | Compte utilisateur | ✓ (souvent déjà `fait`) | ✓ | — |
| 17 | Matériel | ✓ optionnel | ✓ | ✓ |
| 18 | Prise de service | ✓ optionnel | ✓ | ✓ |

Endpoint : `GET /integration/dossiers/{id}/taches-post-integration`

---

## 6. Circuit & `necessite_validation_dg`

1. Circuit configuré sur le type (`PUT /types-integrations/{id}/circuit`), sinon circuit complet (5 niveaux).
2. Si `necessite_validation_dg = false` → retrait de `directeur_general`.
3. Si aucun niveau restant → passage automatique `VALIDE_RH` → `VALIDE_DG`.
4. Sinon, à la fin du circuit → `validerDG()` (statut `VALIDE_DG`).

---

## 7. Compte utilisateur & `necessite_compte_utilisateur`

- `true` (défaut permanent / contractuel) : à `/integrer` depuis `VALIDE_DG`, provisionnement auto si absent.
- `false` (stages) : **aucun** compte créé ; tâche 16 absente de la checklist.

---

## 8. Endpoints clés

| Action | Méthode |
|--------|---------|
| Types + flags | `GET /types-integrations` |
| Circuit type | `GET\|PUT /types-integrations/{id}/circuit` |
| Valider RH | `POST /integration/dossiers/{id}/valider-rh` |
| Approuver niveau | `POST /integration/validations/{id}/approuver` |
| Intégrer | `POST /integration/dossiers/{id}/integrer` |
| Générer acte | `POST /integration/dossiers/{id}/generer-acte` |
| Tâches | `GET /integration/dossiers/{id}/taches-post-integration` |
