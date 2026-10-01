# Plan — Santé / AT-MP (Vague D.3.5)

> Branche : `feature/sante-d35`  
> Date : **2026-09-17**  
> Document **figé** (recommandations du cadrage).  
> Droit : [`convention-collective-artf.md`](./convention-collective-artf.md) art. **122–135**.  
> Architecture : [`architecture.md`](./architecture.md)  
> Contrat FE : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md) §2f-ter

**Objectif :** visites, prises en charge santé, évacuation, arrêts maladie / AT-MP / accident non pro, puis pose en paie.

**État :** **livré** (recommandations).

---

## Décisions figées

| # | Décision |
|---|----------|
| 1 | **DG** accorde **toutes** les prises en charge et arrêts (circuit identique à D.3.4). |
| 2 | Réutiliser **`decider-prestations`** (pas de nouvelle permission). |
| 3 | **Pose paie automatique** à l’accord. |
| 4 | Visite d’embauche : **alerte seulement** (pas de 422 à l’intégration). |
| 5 | Pharma : saisie `montant_facture`, l’API calcule **80 %** employeur. |
| 6 | **Un dossier d’arrêt** porte la déclaration et l’allocation 132–135. |
| 7 | Art. 135 : **deux affectations** (6 mois plein + 6 mois demi). |
| 8 | Évacuation = **type** de prise en charge + `date_debut` / `date_fin` / `lieu` / `at_mp`. |
| 9 | Dossier retraite / pension CNSS **hors V1**. |
| 10 | **PDF décision** V1 (prise en charge et arrêt). |

---

## API

Sous `/api/affaires-sociales` :

- `structures-sanitaires`
- `visites-medicales` + `alertes/visites-annuelles-manquantes`
- `prises-en-charge` (circuit + pièces + simulation + PDF)
- `arrets` (circuit + pièces + simulation + PDF)

Codes paie : `remboursement_sante`, `allocation_maladie`, `allocation_accident_non_pro`.

Permissions : `consulter-affaires-sociales` / `gerer-affaires-sociales` / **`decider-prestations`** (DG + admin).

Tests : `tests/Feature/SanteTest.php`.
