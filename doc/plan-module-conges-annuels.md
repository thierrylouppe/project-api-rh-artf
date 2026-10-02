# Plan — Congé annuel (campagne, attribution, hors campagne)

> Date : **2026-10-02**  
> Document **vivant** : cocher au fil du code. **Lots 1 à 6 livrés.**  
> Architecture : [`architecture.md`](./architecture.md)  
> Droit : [`convention-collective-artf.md`](./convention-collective-artf.md) — art. 77 a)  
> Suivi : [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md) — complément de la Vague C  
> Contrat FE actuel : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md) §2c

**Objectif :** isoler le congé annuel sur son préfixe, l’encadrer par une campagne de propositions, puis traiter deux cas hors campagne : le droit acquis après la clôture, et le report pour nécessité de service.

**État :** lots **1 à 6 livrés** (2026-10-02). `/api/absences` inchangé.

---

## 1. Cadre

Chaîne : Route → FormRequest → Controller → **Service** → **Interface** → Repository → Model.

- Le controller n’injecte que des services.
- `/api/absences` ne bouge pas. Les permissions d’absence gardent leur circuit N+1.
- `/api/conges` reste le module commun (maternité, exceptionnels, convenances personnelles, maladie). Aucune route existante n’est retirée.
- Préfixe nouveau : **`/api/conges-annuels`**. Il ne traite que le type « Congé annuel ». Un autre type sur ce préfixe répond **422**.
- Pas de nouvelle table de demandes. On s’appuie sur `demande_conges` et `conge_soldes`.
- Une table nouvelle pour la campagne, et une pour le report.
- Permissions inchangées : `consulter-conges`, `creer-conges`, `valider-conges`.
- Le signataire reste métier : N+1 = supérieur de l’affectation active, RH = rôle `rh`. Pas de visa DG sur le congé annuel.

---

## 2. Décisions figées

| # | Décision |
|---|----------|
| 1 | Une seule campagne **ouverte** par année civile. |
| 2 | La clôture fige les propositions. Elle ne valide rien et ne débite pas le solde. |
| 3 | Après clôture : visa N+1, puis attribution RH. Le débit a lieu seulement au visa RH. |
| 4 | L’agent déjà éligible (12 mois atteints à la clôture) qui n’a pas déposé pendant la fenêtre **ne** passe **pas** par le circuit hors campagne. |
| 5 | L’agent qui avait moins de 12 mois à la clôture dépose plus tard, sur le circuit « droit acquis après clôture ». La campagne n’est pas rouverte. |
| 6 | Le report pour nécessité de service est proposé par le N+1 et décidé par la RH. L’agent ne le demande pas. |
| 7 | Plafond de cumul : **60 jours ouvrables** (deux mois). Au-delà, la RH ne peut pas accorder. |
| 8 | Le report transfère un reliquat. Il ne fixe pas les dates. L’utilisation suit ensuite le circuit normal. |
| 9 | Les permissions, les congés exceptionnels et les autres types restent hors de ce module. |

---

## 3. Campagne

Statuts : `brouillon` → `ouverte` → `cloturee`.

| Action | Qui | Route |
|--------|-----|--------|
| Créer | RH | `POST /conges-annuels/campagnes` `{ annee, date_ouverture, date_cloture }` |
| Ouvrir | RH | `POST /conges-annuels/campagnes/{id}/ouvrir` |
| Consulter | lecture congés | `GET /conges-annuels/campagnes`, `GET /conges-annuels/campagnes/{id}` |
| Agents sans proposition | RH | `GET /conges-annuels/campagnes/{id}/sans-proposition` |
| Clôturer | RH | `POST /conges-annuels/campagnes/{id}/cloturer` |

Pendant `ouverte`, l’agent propose début et fin. Contrôles déjà en place : 12 mois à la date de début, solde suffisant, au moins un jour ouvrable, pas de chevauchement.

À `cloturee` : plus de création ni de modification de proposition de campagne. Les dossiers déjà `soumise` restent traitables.

---

## 4. Planification et attribution (dans la campagne)

| Étape | Qui | Route | Effet |
|-------|-----|--------|--------|
| Proposer | Agent | `POST /conges-annuels/demandes` | `soumise`. Seule la date de départ est saisie : le système pose tout le solde de l'année et calcule la fin et la reprise |
| Avis | N+1 | `POST …/valider-n1` · `…/rejeter-n1` | Après clôture. Rejet : commentaire obligatoire |
| Attribution | RH | `POST …/valider-rh` · `…/rejeter-rh` | Accord → débit du solde, attestation |
| Annuler | Demandeur | `POST …/annuler` | Seulement tant que `soumise` et campagne encore `ouverte` |

Autres lectures : liste, détail, file `a-valider`, fiche PDF dès la soumission, attestation seulement si `validee_rh`.

Solde inchangé dans son calcul : 2,5 × 12 plafonné à 30, plus le palier d’ancienneté au 1er janvier. Ouverture à la première lecture de l’année.

---

## 5. Circuit — droit acquis après la clôture

Réservé à l’agent qui, **à la date de clôture**, avait moins de 12 mois de service. La campagne de l’année doit être `cloturee`.

| Étape | Qui | Effet |
|-------|-----|--------|
| Dépôt | Agent | `POST /conges-annuels/demandes` avec `origine=apres_cloture` |
| Avis | N+1 | Accepte ou refuse |
| Attribution | RH | Accorde, débite le solde de l’année |

Contrôles au dépôt :

- campagne de l’année clôturée ;
- moins de 12 mois de service à la date de clôture ;
- date de début au plus tôt le jour des 12 mois ;
- solde suffisant, au moins un jour ouvrable, pas de chevauchement.

Mêmes statuts que la campagne : `soumise` → `validee_n1` → `validee_rh`. Pas de visa DG. Pas de réouverture de la campagne.

---

## 6. Circuit — report pour nécessité de service

Le cumul est interdit, sauf cet acte. L’agent n’est pas demandeur.

| Étape | Qui | Route | Effet |
|-------|-----|--------|--------|
| Proposition | N+1 | `POST /conges-annuels/reports` | Agent, année N−1, motif obligatoire |
| Accord | RH | `POST /conges-annuels/reports/{id}/accorder` | Reliquat de N−1 versé sur N |
| Refus | RH | `POST /conges-annuels/reports/{id}/refuser` | Commentaire obligatoire. Le reliquat reste sur N−1 |

Plafond : jours reportés tels que le total reporté ne dépasse pas **60 jours ouvrables**. Au-delà, l’accord répond **422**.

Le report ne crée pas de dates. Pour prendre ces jours, l’agent passe ensuite par une demande (campagne si elle est ouverte, sinon circuit adapté à sa situation), puis N+1 et RH.

---

## 7. Lots

| Lot | Contenu | Statut |
|-----|---------|--------|
| 1 | Préfixe `/conges-annuels`, demandes filtrées sur le type annuel, réutilisation du circuit N+1 → RH | ✅ |
| 2 | Solde, 12 mois, jours ouvrables, chevauchement, débit au visa RH | ✅ |
| 3 | Campagne : créer, ouvrir, proposer, clôturer, file sans proposition | ✅ |
| 4 | Circuit droit acquis après clôture | ✅ |
| 5 | Circuit report nécessité de service, plafond 60 jours | ✅ |
| 6 | PDF, statistiques, note FE, tests Feature | ✅ |

Les lots 1 et 2 s’appuient sur le métier déjà livré dans `/api/conges`. Les lots 3 à 5 sont le complément.

---

## 8. Hors de ce plan

- Permissions et tout le préfixe `/api/absences`.
- Congés exceptionnels, maternité, convenances personnelles, sans solde, sabbatique.
- Campagne collective qui attribuerait les dates à la place de l’agent.
- Rattrapage d’un agent déjà éligible qui n’a pas proposé pendant la fenêtre.
