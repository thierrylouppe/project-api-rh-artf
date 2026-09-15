# Organigramme — Direction des Ressources Humaines et de la Logistique (D.R.H.L)

> Date : 2026-09-15  
> Source structure seedée : `DirectionSeeder`, `ServiceSeeder`, `BureauSeeder`  
> Missions : déduites des intitulés (le champ `description` n’est pas seedé).  
> Plan d’implémentation : [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md)

## Implémentation — pas de cloisonnement pour l’instant

Les modules API sont **globaux** (rôle `rh`). On ne filtre pas par service ni par bureau.  
Le cloisonnement (menus / permissions / « ma structure seulement ») est la **Vague F**, après livraison de D.2–D.6.

Le bureau **Affaires sociales** (`B.A.S.`) est une **cible métier**. Il n’est **pas** dans les seeders tant que la Vague F n’est pas ouverte.

---

## Direction

| Nom | Sigle |
|---|---|
| Direction des Ressources Humaines et de la Logistique | `D.R.H.L` |

Pilotage des ressources humaines, de la conformité administrative du travail et de la logistique interne de l’ARFT.

```
D.R.H.L
├── S.R.H     Service des Ressources Humaines
│   ├── B.F     Bureau Formation
│   ├── B.P     Bureau Personnel
│   ├── B.S.    Bureau Solde
│   └── B.A.S.  Bureau des Affaires sociales   ← cible, pas seedé
├── S.L.T.C.A Service Législation du Travail et Conformité Adm.
│   └── B.PL    Bureau Étude et Planification
└── S.LOG     Service Logistique               ← hors périmètre API RH
    ├── B.ECON  Bureau Économat
    ├── B.EQ    Bureau Équipement
    └── B.M.B   Bureau Mobilier de Bureau
```

---

## 1. Service des Ressources Humaines (`S.R.H`)

Gestion du personnel : recrutement, dossiers, formation, paie, solde et protection sociale.

| Bureau | Sigle | Mission | Module API (vague) |
|---|---|---|---|
| Bureau Formation | `B.F` | Plans de formation, stages d’accueil, suivi des compétences | D.4 `/formations` + stages déjà livrés |
| Bureau Personnel | `B.P` | Dossiers, affectations, carrière, absences | **Livré** + D.2 discipline |
| Bureau Solde | `B.S.` | Paie, éléments de salaire, suivi des soldes | Grille / salaire **livrés** + D.5 `/paie` |
| Bureau des Affaires sociales | `B.A.S.` | Protection sociale agent + famille (CNSS, ayants droit, prestations) | D.3 `/affaires-sociales` |

---

## 2. Service Législation du Travail et Conformité Administrative (`S.L.T.C.A`)

Application du droit du travail, de la CCN et des règles administratives RH.

| Bureau | Sigle | Mission | Module API (vague) |
|---|---|---|---|
| Bureau Étude et Planification | `B.PL` | Études RH, planification des effectifs, conformité | D.6 `/reporting` |

---

## 3. Service Logistique (`S.LOG`)

Moyens matériels, fournitures et patrimoine de bureau. **Hors plan d’implémentation RH.**

| Bureau | Sigle | Mission |
|---|---|---|
| Bureau Économat | `B.ECON` | Fournitures, stocks, achats courants |
| Bureau Équipement | `B.EQ` | Équipements techniques et matériels |
| Bureau Mobilier de Bureau | `B.M.B` | Mobilier, inventaire, affectation du mobilier |

---

## Bureau des Affaires sociales — mission (cible)

Assurer l’affiliation des agents aux organismes sociaux, suivre les ayants droit, instruire les prestations et relayer vers la paie les éléments qui impactent la rémunération.

Ne gère pas : dossier de carrière (Personnel), bulletin / lot de paie (Solde), catalogue de formation (Formation).

P1 (D.3.1–D.3.3) : organismes, affiliations, ayants droit.  
Ensuite : prestations / allocations (après D.5), santé / AT-MP / retraite.
