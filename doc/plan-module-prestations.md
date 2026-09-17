# Plan — Prestations sociales (Vague D.3.4)

> Branche : `feature/prestations-d34`  
> Date : **2026-09-17**  
> Document **figé** (recommandations du cadrage).  
> Droit : [`convention-collective-artf.md`](./convention-collective-artf.md) art. **119–121** (retraite / décès). Art. **58–59** déjà versés en paie.  
> Architecture : [`architecture.md`](./architecture.md)  
> P1 déjà livré : `/affaires-sociales` (organismes, affiliations, ayants droit)  
> Contrat FE : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md) §2f-bis

**Objectif :** instruire et décider les **prestations CCN ponctuelles** (décès, retraite), puis les **poser en paie** sur le mois choisi — sans recoder les allocations mensuelles / calendaires déjà automatiques.

**État :** **livré** (recommandations).

---

## 1. Cadre (non négociable)

Chaîne : Route → FormRequest → Controller → **Service** → **Interface** → Repository → Model.

- Préfixe existant : **`/api/affaires-sociales`**. Extension, pas de nouveau préfixe.
- Permissions : `consulter-affaires-sociales` / `gerer-affaires-sociales` + **`decider-prestations`** (DG + admin).
- Barèmes CCN dans le **Service**, pas en seeder « pratique ».
- Traitement de référence art. 119 / 121 : **salaire de base + prime d’ancienneté**. Détachement / dispo : **déduire** ces périodes (art. 119).
- Lien paie : à l’**accord**, affectation ponctuelle (montant figé). **Ne pas** écrire `salaires_agents.montant_net`.
- Notifications : canal `database`. Mail / SMS hors scope.
- Cloisonnement bureau Affaires sociales : Vague **F**.

---

## 2. Frontière avec l’existant — **ne pas recoder**

| Déjà livré | D.3.4 |
|------------|--------|
| Organismes, affiliations, ayants droit, dossier social | Demandes + circuit + pièces |
| `AyantDroit::estACharge()` / `nb_enfants` dérivé | Prime 100 000 F × enfants **à charge** au décès |
| Auto paie art. 58–59 (AF, SFT, arbre, rentrée) | **Pas** de demande pour ces versements calendaires |
| Codes `capital_deces` / `indemnite_retraite` | **Activés** + 3 codes (prime enfants, funérailles, allocation retraité) |
| Discipline (instruire / prononcer) | Circuit **demande sociale**, pas un dossier disciplinaire |
| D.3.5 visites, AT-MP, évacuation sanitaire | **Hors** cette vague |

---

## 3. Décisions figées

| # | Décision |
|---|----------|
| 1 | **DG** accorde **toutes** les prestations (un seul circuit, y compris funérailles). |
| 2 | Permission **`decider-prestations`** (`directeur-general` + `admin`). Comme `prononcer-discipline`. |
| 3 | **Indemnité retraite art. 119** : type de prestation D.3.4 (pas seulement un changement de statut). |
| 4 | **Transport du corps** : flag `transport_corps` sur `frais_funeraires`, **même plafond 2 000 000 F**. |
| 5 | `agent_id` obligatoire. `ayant_droit_id` optionnel. Pour un type décès : `beneficiaire_libelle` **requis** s’il n’y a pas d’ayant droit. |
| 6 | **Pose paie automatique** à l’accord (`PaieAffectationService::creerDepuisPrestation`, y compris agent archivé / retraité). |
| 7 | **`GET …/simulation`** avant / pendant l’instruction. |
| 8 | Agent **retraité** ou **archivé** : `allocation_deces_retraite` **autorisée**. |
| 9 | Art. **132–135** (maladie / AT) restent **D.3.5**. |
| 10 | **PDF décision** V1 (accord / refus), même gabarit que la discipline. |

---

## 4. Types de prestation (V1)

| Code | Article | Calcul | Bénéficiaire |
|------|---------|--------|--------------|
| `capital_deces` | 121 | Mois de (base + ancienneté) : 5 / 9 / 12 / 15 / 18 | Ayants droit d’un **salarié** décédé (pas en essai) |
| `prime_enfants_deces` | 121 | **100 000 F ×** enfants à charge au décès | Idem |
| `frais_funeraires` | 121 | Saisie justifiée, **plafond 2 000 000 F** (+ flag transport) | Salarié, conjoint ou enfant à charge |
| `allocation_deces_retraite` | 120 | **500 000 F** forfait | Ayants droit d’un **retraité** (ou archivé) |
| `indemnite_retraite` | 119 | Mois de (base + ancienneté) palier 5–35 ans (3 à 24 mois) | Agent admis à la retraite |

**Hors V1 (D.3.5) :** frais médicaux / hospitalisation art. 122–127, allocations maladie / AT art. 132–135, évacuation sanitaire, police mission, aides hors CCN.

---

## 5. Circuit

```
brouillon → soumise → instruite → accordee | refusee
                              ↘ classee
```

| Étape | Qui | Permission |
|-------|-----|------------|
| Créer / modifier brouillon, soumettre, pièces | RH | `gerer-affaires-sociales` |
| Instruire | RH | `gerer-affaires-sociales` |
| Accorder / refuser | **DG** | `decider-prestations` |
| Classer (sans suite) | RH | `gerer-affaires-sociales` |
| PDF décision | RH / DG | `consulter-affaires-sociales` ou `decider-prestations` |

À l’**accord** : snapshot CCN + affectation ponctuelle (mois `paie_annee` / `paie_mois`, défaut = mois courant) + notification RH.

**Essai :** `capital_deces` et `indemnite_retraite` → **422** si contrat en essai ouvert (art. 121 / 119).

---

## 6. API (V1)

Sous `/api/affaires-sociales`. OpenAPI tag `Affaires sociales`. `operationId` préfixe `prestation`.

```
GET    /prestations
POST   /prestations
GET    /prestations/{id}
PUT    /prestations/{id}
DELETE /prestations/{id}
POST   /prestations/{id}/soumettre
POST   /prestations/{id}/instruire
POST   /prestations/{id}/accorder
POST   /prestations/{id}/refuser
POST   /prestations/{id}/classer
GET/POST /prestations/{id}/pieces
GET    /prestations/{id}/pieces/{pieceId}
DELETE /prestations/{id}/pieces/{pieceId}
GET    /agents/{id}/prestations
GET    /prestations/{id}/simulation
GET    /prestations/{id}/pdf-decision
```

Pièces : pdf/jpg/png/doc/docx, 10 Mo.

---

## 7. Couches

```
PrestationInterface → PrestationRepository → binding
PrestationPieceInterface → PrestationPieceRepository → binding
  → PrestationService + PrestationCalculService (barèmes CCN)
  → PaieAffectationService::creerDepuisPrestation
  → FormRequests / Resource / Controller
```

---

## 8. Hors scope V1

- Santé, visites, hospitalisation, AT-MP, allocations maladie (D.3.5)
- Inventer des aides hors CCN
- Recalcul des AF / arbre / rentrée (déjà D.5)
- Indemnité licenciement art. 111–114
- Self-service agent
- Cloisonnement bureau, mail / SMS
- Liquidation pension CNSS (hors ARTF)

---

## 9. Tests Feature

| Test | Scénario |
|------|----------|
| Circuit | brouillon → soumise → instruite → accordée ; 422 si saut d’étape |
| Art. 121 | palier ancienneté ; + 100 000 × enfants à charge ; essai → 422 |
| Funérailles | montant > 2 000 000 → 422 |
| Art. 120 | 500 000 F si agent `retraite` / `archive` |
| Art. 119 | paliers 3–24 mois ; base + ancienneté ; déduction détachement/dispo ; < 5 ans → 422 |
| Paie | accord → affectation ponctuelle ; codes activés |
| Permissions | RH vs DG vs 401/403 |
| PDF | décision après accord |
