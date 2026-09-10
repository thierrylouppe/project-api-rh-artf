# Convention collective ARTF — Avancement (extrait)

> Source : Convention collective de l’ARTF, **chapitre 4** (art. 60–72) et **chapitre 5** (art. 73–75), pp. 20–21/44.  
> Saisie : 2026-09-10, d’après scan fourni.  
> Usage : **source juridique ARTF** pour le module évaluation / notation / avancement.  
> Logiciel / barème /20 : [`REFERENTIELS-EVALUATION-NOTATION.md`](./REFERENTIELS-EVALUATION-NOTATION.md)  
> Phasage : [`plan-module-evaluation-notation.md`](./plan-module-evaluation-notation.md)

En cas de conflit : **la convention prime** sur le référentiel logiciel (autre dépôt). Le plan d’implémentation suit la convention.

---

## Chapitre 4 — Avancement

**Art. 60.** L’avancement est le passage d’un **échelon inférieur à un échelon supérieur au sein d’une même classe**.

**Art. 61.** L’avancement récompense le **mérite** et l’**expérience** (ancienneté et formation).

Le processus des salariés inscrits au tableau d’avancement à l’ARTF connaît **trois étapes concomitantes** :

1. l’évaluation des salariés par la **notation** ;
2. la **commission préparatoire** à l’avancement ;
3. la **commission d’avancement**.

**Art. 62 — Évaluation / notation.** Tout salarié **en activité et affecté** à un poste est noté **tous les 24 mois**, selon la manière de servir, d’être et les performances.

Le salarié **muté ou affecté en cours d’année** est noté au poste où il a **servi le plus longtemps**.

**Art. 63.** Une **fiche individuelle d’évaluation** matérialise la notation. Elle est signée :

- par les **notateurs** visés à l’art. 64 ;
- **et** par le **salarié**.

**Art. 64.** Pouvoir de notation = **chefs hiérarchiques** :

- chefs de bureau ;
- chefs de service ;
- directeurs départementaux ;
- directeurs centraux ;
- directeur général.

**Art. 65.** **Exemptés** de notation : salarié en **stage**, en **détachement**, en **position exceptionnelle**.

La note **doit être portée à la connaissance du salarié** pour **réclamations** éventuelles.

**Art. 66 — Commission préparatoire.** Instituée par **note de service du DG**. Composition : représentants de l’administration, délégués du personnel, représentants syndicaux, représentants de la direction départementale du travail.

**Art. 67.** Mission de la préparatoire :

- améliorer les procédures de décision de la commission d’avancement ;
- apprécier les **motivations des notateurs** ;
- veiller à la **cohérence entre appréciations et notes** ;
- adresser à la commission d’avancement une **note de synthèse** sur les points débattus.

**Art. 68.** La préparatoire est **présidée par le directeur général**.

**Art. 69 — Commission d’avancement.** Présidée par le DG. Composition : administration, partenaires sociaux, direction départementale du travail. Structuration par **note de service du DG**.

**Art. 70.** La **note à l’avancement** est **déterminée à chaque session** de la commission d’avancement.

**Art. 71 — Avancement automatique.** Bonification de **deux (2) échelons** pour tout salarié ayant suivi un **stage d’au moins neuf (9) mois** autorisé par l’employeur, sur **certificat ou attestation de fin de stage**.

**Art. 72 — Avancement exceptionnel.** La commission d’avancement, **sur proposition du DG**, peut accorder un avancement exceptionnel, **dans la limite de deux (2) échelons**.

---

## Chapitre 5 — Reclassement, hors classe, reconversion

**Hors périmètre du module notation / tableau d’avancement** (changement de **classe** ou d’emploi, pas d’échelon dans la même classe). À traiter plus tard (carrière / formation).

**Art. 73.** Reclassement à une **classe supérieure** après formation autorisée et diplôme reconnu par l’État.

**Art. 74.** Reclassement **exceptionnel** : 50 ans d’âge + 15 ans d’ancienneté + 3 ans dans la même classe.  
Reclassement **hors classe** : ancienneté ≥ 25 ans, grade d’inspecteur principal, 8ᵉ échelon.

**Art. 75.** **Reconversion** (autre emploi) : baisse d’activité, réorganisation interne, maladie constatée par médecin agréé.

---

## Mapping convention → API (rappel)

| Convention | Traduction produit |
|------------|-------------------|
| Même classe, échelon supérieur (art. 60) | `avancerEchelon` (grille salariale) |
| 24 mois + en activité + affecté (art. 62) | Session / éligibilité |
| Poste le plus long si mutation (art. 62) | N+1 = structure de l’affectation **dominante** sur la période, pas seulement l’affectation active du jour |
| Signatures notateurs + agent (art. 63–64) | Circuit avis + `signe_par_evalue` |
| Stage / détachement / position exceptionnelle (art. 65) | **Non éligibles** (le DG **n’est pas** exempté par la convention : il est notateur) |
| Réclamation (art. 65) | Dès que la note est connue de l’agent (après signature / communication) |
| Préparatoire (art. 66–68) | Cohérence notes/avis + **note de synthèse** — pas un simple « écart > 5 » |
| Note à l’avancement (art. 70) | Note **fixée par la commission d’avancement**, distincte de la note N+1 |
| +2 échelons stage 9 mois (art. 71) | Parcours **séparé** (pas le cycle 24 mois) |
| +2 échelons max exceptionnel (art. 72) | Décision commission, proposition DG |
| Art. 73–75 | **Pas** dans `/avancements` V1 |
