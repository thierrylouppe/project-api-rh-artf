# Plan — Module Paie (Vague D.5)

> Branche : `feature/paie-d5`  
> Date : **2026-09-16**  
> Document **vivant** : cocher au fil du code.  
> Droit : [`convention-collective-artf.md`](./convention-collective-artf.md) art. **54–59** (retraite art. 119 **hors V1**)  
> Architecture : [`architecture.md`](./architecture.md)  
> Suivi vagues : [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md)  
> Grille / salaire de base **déjà livrés** : [`SPEC-GRILLE-SALARIALE.md`](./SPEC-GRILLE-SALARIALE.md) · `/salaires` · `/salaires-agents`  
> Ayants droit (art. 58–59, déjà nominatifs) : `/affaires-sociales`  
> Contrat FE : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md) — **ne pas concevoir d’écrans avant l’API**

**Objectif :** produire un **lot de paie mensuel** conforme CCN (base + éléments + net) **sans casser** grille, salaire indiciaire, bulletin simplifié.

**État :** D.5.1–D.5.2 **livrés**. D.5.3–D.5.5 ⬜.

---

## 1. Cadre (non négociable)

Chaîne : Route → FormRequest → Controller → **Service** → **Interface** → Repository → Model.

- Controller : Services uniquement. Mince (3–8 lignes).
- Service : Interfaces (+ autres Services). **Barèmes et plafonds CCN ici.** Montants « comité de direction » : **paramétrables**, jamais en dur.
- Repository : Eloquent uniquement. Pas de JSON métier, pas de notifications.
- Bindings `Interface → Repository` dans `AppServiceProvider::register()`.
- Auth : `auth:sanctum` + `permission:` (pas de Policies).
- Préfixe API : **`/api/paie`**. Extension, pas rupture : **aucune** route `/salaires*` renommée ou retirée.
- Notifications : canal `database` via `NotificationService`. Mail / SMS hors scope.
- Cloisonnement bureau Solde : Vague **F**, pas maintenant.

**Ordre de création d’un domaine :** Interface → Repository → binding → Service → FormRequests → Resource → Controller → routes (section commentée dans `api.php`).

---

## 2. Frontière avec l’existant

| Déjà livré — **ne pas recoder** | D.5 — **à coder** |
|--------------------------------|-------------------|
| Grille annexe 2 (`SalaireService`) | Référentiel d’éléments (primes / indemnités / retenues / allocations) |
| `salaires_agents` (période de carrière, échelon, `montant_base`) | Affectation agent × élément (période, montant) |
| Bulletin PDF **simplifié** (`GET /salaires-agents/{id}/bulletin`) | Lot mensuel + bulletin **enrichi** (nouveau endpoint) |
| Hors grille DG/DC/DD (`hors_grille`, pas de ligne indiciaire) | Élément `salaire_fonctionnel` paramétrable |
| Positions art. 76–80 : détachement / dispo **coupent** le salaire indiciaire | Le lot **n’inclut pas** ces agents (pas de base) |
| Ayants droit + `nb_enfants_arbre_noel` (P1, **pas de versement**) | Versement art. 58–59 sur le bulletin du mois concerné |
| `gratification` de stage (libre) | Prime transport stagiaires art. 54 **si** élément affecté |
| Mise à pied 1–8 j (`/discipline`) | Anomalie de contrôle + prorata / retenue (V1 : anomalie obligatoire, prorata optionnel) |

**`salaires_agents.montant_net` :** aujourd’hui égal au salaire de **grille**. Ne **pas** y écrire le net mensuel du lot. Le net du mois vit sur `paie_lot_lignes.montant_net`. Le bulletin carrière simplifié reste ce qu’il est.

---

## 3. Décisions figées

1. **Trois agrégats HTTP**, un Service chacun : éléments, affectations, lots. Le calcul CCN n’a **pas** de route : `PaieCalculService` (Service de domaine, sans repository).
2. **Permissions V1 :** réutiliser `consulter-salaires` / `gerer-salaires`. Pas de `gerer-paie` tant que le FE n’en a pas besoin (même schéma que Vague E).
3. **Snapshot :** à la génération, chaque ligne et chaque détail sont **figés** (montant, libellé, code). Modifier un élément après clôture ne réécrit pas l’historique.
4. **Un seul lot ouvert** par `(annee, mois)` — unique en base.
5. **Génération automatique** seulement pour les codes `formule_ccn` / `bareme_ccn` éligibles. Tout montant « comité de direction » exige une **affectation** (sauf mention contraire ci-dessous pour art. 58 en mois calendaire).
6. **Missions art. 57** : barèmes CCN en **constantes métier** dans `PaieCalculService` (pas en seeder). Saisie = affectation ponctuelle (nb de jours × taux). Durée locale > 15 j → 422 sauf flag `prolongation_dg`.
7. **Retenues légales (CNSS, IRPP, etc.) :** pas de barème inventé. Codes `retenue_*` paramétrables, montant / taux saisi par le RH. D.5 n’est pas un moteur fiscal.
8. **Art. 119 (indemnité retraite) et art. 121 (capital décès) :** hors V1 (carrière / D.3.4). Prévoir le **code** d’élément inactif dans le seeder, pas le calcul.
9. **D.5.5 export** masse salariale : après D.5.4 ; peut glisser en D.6 si le calendrier serre.

---

## 4. Modèle de données

```
paie_elements 1───* paie_element_affectations *───1 agents
       │
       │  (référencé en snapshot, pas de FK obligatoire sur le détail clôturé)
       ▼
paie_lots 1───* paie_lot_lignes *───1 agents
                    │
                    ├── salaire_agent_id?  (ligne indiciaire du mois, nullable si hors grille)
                    └── 1───* paie_lot_ligne_details
```

### 4.1 `paie_elements`

Référentiel. Seeder des **codes CCN** (voir § 5). CRUD RH pour activer / montant par défaut.

| Colonne | Type | Notes |
|---------|------|--------|
| `id` | bigint | |
| `code` | string unique | Enum `CodePaieElement` |
| `libelle` | string | |
| `nature` | enum | `prime`, `indemnite`, `allocation`, `retenue`, `salaire_fonctionnel` |
| `sens` | enum | `gain`, `retenue` — dérivé de `nature` au `beforeCreate`, pas saisi librement |
| `periodicite` | enum | `mensuel`, `semestriel`, `annuel`, `ponctuel`, `journalier` |
| `mode_calcul` | enum | `montant_fixe`, `pourcentage_base`, `formule_ccn`, `bareme_ccn` |
| `montant_defaut` | decimal(14,2) nullable | Comité de direction ; **null** tant que non paramétré |
| `taux_defaut` | decimal(8,4) nullable | Retenues % / exception |
| `article_ccn` | string nullable | `'56'`, `'57'`, `'54'`, `'58'`… |
| `fonction_sigles` | json nullable | Éligibilité par `fonctions.sigle` (`["CB","CS","CSR","DD","DC","DG"]`) |
| `mois_declenchement` | json nullable | Ex. `[6,12]` vestimentaire ; `[12]` fin d’année / arbre ; `[9]` rentrée |
| `actif` | boolean | default true |
| `systeme` | boolean | Codes CCN : non supprimables ; `libelle` / montants modifiables |
| timestamps | | |

Index : `code`, `actif`, `nature`.

### 4.2 `paie_element_affectations`

| Colonne | Type | Notes |
|---------|------|--------|
| `id` | | |
| `paie_element_id` | FK restrict | |
| `agent_id` | FK cascade | |
| `montant` | decimal(14,2) nullable | Override ; sinon `montant_defaut` à la génération |
| `taux` | decimal(8,4) nullable | |
| `quantite` | decimal(8,2) nullable | Jours de mission / panier / astreinte |
| `date_debut` | date | |
| `date_fin` | date nullable | Null = ouvert |
| `motif` | text nullable | Note de service DG (primes exceptionnelles) |
| `prolongation_dg` | boolean | Missions locales > 15 j |
| `created_by` | FK users nullable | |
| timestamps | | |

Index : `(agent_id, paie_element_id, date_debut)`.  
Règle Service : **pas de chevauchement** même agent + même élément (422).

### 4.3 `paie_lots`

| Colonne | Type | Notes |
|---------|------|--------|
| `id` | | |
| `annee` | unsignedSmallInteger | |
| `mois` | unsignedTinyInteger | 1–12 |
| `statut` | enum | `brouillon`, `genere`, `controle`, `valide`, `cloture` |
| `generated_at` / `generated_by` | | |
| `controle_at` / `controle_par` | | |
| `valide_at` / `valide_par` | | |
| `cloture_at` / `cloture_par` | | |
| `commentaire` | text nullable | |
| timestamps | | |

Unique : `(annee, mois)`.

### 4.4 `paie_lot_lignes`

Une ligne = un agent dans un lot.

| Colonne | Type | Notes |
|---------|------|--------|
| `lot_id` | FK cascade | |
| `agent_id` | FK restrict | |
| `salaire_agent_id` | FK nullable restrict | Ligne indiciaire **active** au 1er du mois |
| `hors_grille` | boolean | |
| `montant_base` | decimal(14,2) | 0 si hors grille sans indiciaire |
| `total_gains` | decimal(14,2) | Base + éléments `gain` (la base est un gain) |
| `total_retenues` | decimal(14,2) | |
| `montant_net` | decimal(14,2) | `total_gains - total_retenues` |
| `nb_anomalies` | unsignedInteger | default 0 |
| `snapshot_agent` | json | matricule, nom, fonction, classe, échelon — figé |
| unique | `(lot_id, agent_id)` | |

### 4.5 `paie_lot_ligne_details`

| Colonne | Type | Notes |
|---------|------|--------|
| `ligne_id` | FK cascade | |
| `paie_element_id` | FK nullable | Null si l’élément a été archivé plus tard |
| `code` | string | Copie |
| `libelle` | string | Copie |
| `nature` / `sens` | | Copie |
| `montant` | decimal(14,2) | Toujours positif ; le `sens` dit l’écriture |
| `source` | enum | `base`, `affectation`, `calcul_auto` |
| `meta` | json nullable | Ex. `{ "taux_anciennete": 0.12, "annees": 12 }` |

---

## 5. Codes CCN (`CodePaieElement`)

Seeder **système**. `montant_defaut = null` pour tout montant comité de direction.

| Code | Nature | Périodicité | Mode | Auto à la génération ? | Éligibilité |
|------|--------|-------------|------|------------------------|-------------|
| `salaire_base` | *(virtuel, pas une ligne d’élément)* | — | — | Toujours, source `base` | Salaire indiciaire actif |
| `salaire_fonctionnel` | salaire_fonctionnel | mensuel | montant_fixe | Non (affectation RH) | DG / DC / DD |
| `prime_responsabilite` | prime | mensuel | montant_fixe | Non | Sigles CB, CS, CSR, DD, DC, DG |
| `prime_anciennete` | prime | mensuel | formule_ccn | **Oui** si ≥ 2 ans | En activité, a un `montant_base` > 0 **ou** fonctionnel |
| `prime_representation` | prime | mensuel | montant_fixe | Non | DD uniquement |
| `prime_fin_annee` | prime | annuel | formule_ccn | **Oui** si `mois = 12` | Art. 56 (voir § 8) |
| `prime_risque` | prime | mensuel | montant_fixe | Non | Affectation RH (fonctions chauffeur / planton / veilleur / convoyeur **absentes** du seeder actuel → pas d’auto) |
| `prime_vestimentaire` | prime | semestriel | montant_fixe | Non, **si** affectation + mois ∈ `{6,12}` | Tous en service |
| `prime_caisse` | prime | mensuel | montant_fixe | Non | Affectation (pas de fonction « caissier » seedée) |
| `prime_panier` | prime | journalier | montant_fixe | Non | `quantite` × montant |
| `prime_astreinte` | prime | ponctuel | montant_fixe | Non | 422 si HS déjà payées **dès qu’un élément HS existe** (V1 : pas d’élément HS → pas de contrôle) |
| `prime_logement` | prime | mensuel | montant_fixe | Non | Affectation (nécessités de service) |
| `prime_exceptionnelle` | prime | ponctuel | montant_fixe | Non | `motif` obligatoire (note DG) |
| `prime_transport_stagiaire` | prime | mensuel | montant_fixe | Non | `statut = stagiaire` (art. 54) |
| `indemnite_transport` | indemnite | mensuel | montant_fixe | Non | En service |
| `indemnite_deplacement_affectation` | indemnite | ponctuel | montant_fixe | Non | |
| `indemnite_deplacement_conges` | indemnite | ponctuel | montant_fixe | Non | |
| `indemnite_interim` | indemnite | mensuel | montant_fixe | Non | 422 si durée d’intérim > 6 mois sauf maladie/AT (contrôle sur `date_debut` affectation) |
| `indemnite_formation` | indemnite | mensuel | formule_ccn | Non (affectation + zone) | Afrique = 85 % base ; autre = montant saisi (SMIG pays, pas de constante) |
| `indemnite_mission_locale` | indemnite | journalier | bareme_ccn | Non | `quantite` jours × taux art. 57 ; max 15 j |
| `indemnite_mission_etranger` | indemnite | journalier | bareme_ccn | Non | Barème art. 57 |
| `allocation_rentree_scolaire` | allocation | annuel | montant_fixe | **Oui** si `mois = 9` **et** `montant_defaut` renseigné | Tout salarié en activité |
| `allocation_consommation` | allocation | mensuel | montant_fixe | Non | DC, DD |
| `allocation_arbre_noel` | allocation | annuel | montant_fixe | **Oui** si `mois = 12` **et** montant renseigné | `nb` = min(3, enfants 0–16) ; 1 part forfaitaire si 0 enfant (art. 58) |
| `allocations_familiales` | allocation | mensuel | montant_fixe | Non | Taux « textes en vigueur » → paramétrable, pas inventé |
| `supplement_familial` | allocation | mensuel | montant_fixe | Non | Idem |
| `retenue_cnss` | retenue | mensuel | pourcentage_base ou montant_fixe | Non | Paramétrable |
| `retenue_autre` | retenue | ponctuel | montant_fixe | Non | Acompte, prêt… |
| `indemnite_retraite` | indemnite | ponctuel | formule_ccn | **Inactif** V1 | Art. 119 |
| `capital_deces` | indemnite | ponctuel | formule_ccn | **Inactif** V1 | Art. 121 → D.3.4 |

Fonctions seedées aujourd’hui : `DG`, `DC`, `DD`, `CSR`, `CS`, `CB`, `AGT`, `STG`. D’où l’affectation **manuelle** pour risque / caisse / logement.

---

## 6. Couches PHP

### 6.1 Enums (`app/Enums/`)

- `CodePaieElement`
- `NaturePaieElement`
- `SensPaieElement` (`GAIN`, `RETENUE`)
- `PeriodicitePaieElement`
- `ModeCalculPaieElement`
- `StatutPaieLot`
- `SourceDetailPaie` (`BASE`, `AFFECTATION`, `CALCUL_AUTO`)

### 6.2 Interfaces → Repositories → binding

| Interface | Repository | Tables |
|-----------|------------|--------|
| `PaieElementInterface` | `PaieElementRepository` | `paie_elements` |
| `PaieElementAffectationInterface` | `PaieElementAffectationRepository` | `paie_element_affectations` |
| `PaieLotInterface` | `PaieLotRepository` | `paie_lots` + lignes + détails (même repo, comme `PlanFormation`) |

Pas de 4ᵉ repo pour les détails.

### 6.3 Services

| Service | Injecte | Rôle |
|---------|---------|------|
| `PaieElementService` | `PaieElementInterface` | CRUD. Interdit `delete` si `systeme`. Interdit changer `code` / `mode_calcul` d’un code système. |
| `PaieAffectationService` | Affectation + Element + `AgentInterface` | CRUD, éligibilité fonction / statut, chevauchement, 422 montant manquant si `montant_fixe` sans défaut ni override. |
| `PaieCalculService` | **aucun repo** (reçoit des DTO / modèles déjà chargés) | Formules art. 56–57, barèmes mission, ancienneté, fin d’année, arbre de Noël. |
| `PaieLotService` | Lot + Affectation + Element + `SalaireAgentInterface` + `AgentInterface` + `PaieCalculService` + `NotificationService` (+ lecture ayants droit via interface existante) | State machine, génération, contrôle, clôture. |
| `PaieBulletinService` | Lot (lecture) | PDF enrichi DomPDF. **Ne pas** modifier `bulletin-salaire-agent.blade.php` du bulletin simplifié. |

Controllers : `PaieElementController`, `PaieAffectationController`, `PaieLotController` (lignes + transitions + bulletin + export).

Requests : sous-dossiers `PaieElement/`, `PaieAffectation/`, `PaieLot/`.

Resources : `PaieElementResource`, `PaieAffectationResource`, `PaieLotResource` (résumé), `PaieLotLigneResource` (détail).

---

## 7. API — `/api/paie`

Toutes les routes : `auth:sanctum`.  
Lecture : `permission:consulter-salaires`. Écriture / transitions : `permission:gerer-salaires`.

### 7.1 Éléments — D.5.1

| Méthode | URI | Action |
|---------|-----|--------|
| `GET` | `/paie/elements` | Liste (`nature`, `actif`, `code`) |
| `POST` | `/paie/elements` | Créer (éléments **non système** : retenues maison, etc.) |
| `GET` | `/paie/elements/{id}` | Détail |
| `PUT` | `/paie/elements/{id}` | Maj libellé, `montant_defaut`, `taux_defaut`, `actif`, `fonction_sigles` |
| `DELETE` | `/paie/elements/{id}` | 422 si `systeme` ou déjà snapshoté dans un lot clôturé |

### 7.2 Affectations — D.5.2

| Méthode | URI | Action |
|---------|-----|--------|
| `GET` | `/paie/affectations` | Filtres `agent_id`, `element_id`, `actives=1` |
| `POST` | `/paie/affectations` | Créer |
| `GET` | `/paie/affectations/{id}` | |
| `PUT` | `/paie/affectations/{id}` | Si aucune ligne de lot **validé/clôturé** ne s’y appuie |
| `DELETE` | `/paie/affectations/{id}` | Idem |
| `GET` | `/paie/agents/{agent}/affectations` | Par agent |

### 7.3 Lots — D.5.3

| Méthode | URI | Action |
|---------|-----|--------|
| `GET` | `/paie/lots` | Filtres `annee`, `mois`, `statut` |
| `POST` | `/paie/lots` | Créer **brouillon** `{ annee, mois }` — 422 si période déjà existante |
| `GET` | `/paie/lots/{id}` | Lot + totaux + `nb_anomalies` |
| `DELETE` | `/paie/lots/{id}` | Uniquement `brouillon` (ou `genere` sans validation) |
| `POST` | `/paie/lots/{id}/generer` | Calcule / recalcule les lignes. Autorisé : `brouillon`, `genere`, `controle`. **422** si `valide` / `cloture`. → `genere` |
| `POST` | `/paie/lots/{id}/controler` | Anomalies (ne bloque pas). → `controle` |
| `POST` | `/paie/lots/{id}/valider` | 422 si anomalies **bloquantes** (voir § 9.3). → `valide` |
| `POST` | `/paie/lots/{id}/cloturer` | Figé. → `cloture` |
| `GET` | `/paie/lots/{id}/lignes` | Pagination |
| `GET` | `/paie/lots/{id}/lignes/{ligneId}` | Ligne + détails |
| `GET` | `/paie/agents/{agent}/bulletins` | Historique des lignes clôturées |

### 7.4 Bulletin enrichi — D.5.4

| Méthode | URI | Action |
|---------|-----|--------|
| `GET` | `/paie/lots/{id}/lignes/{ligneId}/bulletin` | PDF (`application/pdf`) |

Le bulletin **simplifié** `GET /salaires-agents/{id}/bulletin` **reste**.

### 7.5 Export — D.5.5 (après 5.4)

| Méthode | URI | Action |
|---------|-----|--------|
| `GET` | `/paie/lots/{id}/export` | Query `format=csv\|pdf`. 422 si pas `valide` / `cloture`. |

---

## 8. Règles métier (`PaieCalculService`)

Toute formule ci-dessous est du **code**, pas un commentaire. Tests Feature dédiés.

### 8.1 Prime d’ancienneté (art. 56) — auto

Référence : `date_prise_service` → années révolues à la **date de fin du mois** du lot.

- < 2 ans → 0 (pas de détail).
- ≥ 2 ans : `2 % + 1 % × (annees - 2)` du **salaire de base** du mois.
- Plafond **40 %**.
- Base = `montant_base` indiciaire ; si hors grille, base = montant de `salaire_fonctionnel` de l’affectation (sinon 0 et anomalie).

Arrondi : **franc CFA entier**, `round half up`.

### 8.2 Prime de fin d’année (art. 56) — auto en décembre

Montant = salaire de base du **dernier mois** + prime d’ancienneté du même mois.

| Situation | Traitement |
|-----------|------------|
| Présent au 31/12 et ancienneté ≥ 1 an | Totalité |
| Retraite en cours d’année (`statut = retraite` dans l’année) | Totalité |
| Détachement / dispo / position spéciale / rupture dans l’année | **Prorata temporis** (mois de présence / 12) |
| Licenciement pour faute lourde dans l’année | **0** (lire sanctions prononcées `licenciement` + `avec_indemnite = false` ou équivalent faute lourde si le champ le permet ; sinon anomalie « à confirmer RH ») |
| Mise à pied dans l’année | **Anomalie non bloquante** « refus possible art. 56 » — le RH retire le détail ou laisse |

### 8.3 Indemnité de formation (art. 57)

Affectation avec meta `zone` : `afrique` → 85 % du traitement de base du mois ; `autre` → `montant` obligatoire (SMIG pays, pas de table).

### 8.4 Missions (art. 57) — barème dans le Service

**Local** (FCFA / jour) :

| Catégorie | Bénéficiaires | Centres urbains / chefs-lieux | Localités secondaires |
|-----------|---------------|-------------------------------|------------------------|
| 1 | DG | 250 000 | 200 000 |
| 2 | DC, DD | 200 000 | 150 000 |
| 3 | CS, CB, ou indice ≥ 1 150 | 150 000 | 100 000 |
| 4 | indice < 1 150 | 100 000 | 80 000 |

Indice : `salaires.indice` de la ligne grille liée au `salaire_agent` (vérifier le champ réel en implémentant ; fallback catégorie 4 si hors grille non DG/DC/DD).

`quantite` = nb de jours. Si > 15 et `prolongation_dg !== true` → 422.

**Étranger** :

| Catégorie | Afrique | Autres continents |
|-----------|---------|-------------------|
| 1 DG | 400 000 | 500 000 |
| 2 DC, DD | 350 000 | 450 000 |
| 3 autres | 300 000 | 400 000 |

### 8.5 Arbre de Noël (art. 58) — auto décembre si montant paramétré

Réutiliser `AyantDroit::estEligibleArbreNoel` : `n = min(3, count)`.  
Si `n = 0` → 1 × `montant_defaut` (part forfaitaire).  
Sinon `n × montant_defaut`.

### 8.6 Rentrée scolaire (art. 58) — auto septembre si montant paramétré

1 × `montant_defaut` par agent en activité (la CCN ne multiplie pas par enfant).

### 8.7 Intérim (art. 57)

À partir du **premier mois**. Si `date_debut` de l’affectation + 6 mois dépassés au mois du lot → 422, sauf `meta.cause` ∈ `{maladie, accident_travail}`.

### 8.8 Art. 55 — absence / positions

- Agent `detachement` ou `disponibilite` : **exclu** du lot (rémunération déjà coupée).
- `position_exceptionnelle` / `sous_le_drapeau` : **inclus** si un `salaire_agent` actif existe (la Vague E ne coupe pas ces deux-là).
- `archive`, `inactif`, `retraite` : exclus, sauf cas fin d’année déjà traité en 8.2 (retraite : pas de salaire mensuel, seulement la prime décembre si le lot est celui de décembre — **décision V1** : agent retraite **exclu** des lots mensuels ; la prime de fin d’année des agents partis dans l’année est une **anomalie de contrôle** listant les départs, traitement manuel via affectation ponctuelle).
- `stagiaire` : inclus **seulement** s’il a au moins une affectation active (transport).

---

## 9. Lot mensuel — génération et circuit

```
brouillon ──generer──► genere ──controler──► controle ──valider──► valide ──cloturer──► cloture
                ▲                     │
                └────── regenerer ────┘     (POST generer à nouveau)
```

Interdit : revenir en arrière après `valide`. Supprimer : `brouillon` uniquement (et `genere` / `controle` si besoin métier — **autoriser** delete tant que pas `valide`).

### 9.1 Population à `generer`

Agents candidats au **dernier jour du mois** :

1. `statut = actif` (et positions qui ne coupent pas, § 8.8) **avec** `salaire_agent` `actif` dont `date_debut ≤ fin_mois` et (`date_fin` null ou ≥ début mois)  
   **ou** `estHorsGrille()`  
   **ou** stagiaire avec affectation.
2. Pour chacun, transaction : upsert ligne, **replace** des détails (idempotent).
3. Détail `source=base` : `montant_base` (0 si hors grille).
4. Détails `calcul_auto` : ancienneté ; + fin d’année / arbre / rentrée selon le mois.
5. Détails `affectation` : affectations dont la période **chevauche** le mois, et dont `periodicite` / `mois_declenchement` matche.
6. Totaux.

### 9.2 Recalcul

`POST generer` sur `genere` / `controle` : wipe détails + relance. Message : `Lot recalculé`.

### 9.3 Contrôle — anomalies

Chaque anomalie : `{ code, severite: bloquante|info, agent_id, message }` dans `meta` du lot (json `anomalies`) **et** `nb_anomalies` sur la ligne.

| Code | Sévérité | Cas |
|------|----------|-----|
| `hors_grille_sans_fonctionnel` | bloquante | DG/DC/DD sans affectation `salaire_fonctionnel` couvrant le mois |
| `sans_base` | bloquante | Agent actif non hors-grille sans `salaire_agent` actif |
| `montant_element_manquant` | bloquante | Affectation / auto sans montant |
| `chevauchement` | bloquante | Ne devrait pas arriver (422 à l’affectation) |
| `mise_a_pied` | info | Sanction `mise_a_pied` dont les jours tombent dans le mois |
| `fin_annee_mise_a_pied` | info | Décembre + mise à pied dans l’année |
| `fin_annee_depart` | info | Décembre + rupture / position coupant dans l’année |
| `interim_6_mois` | bloquante | Si non catché en 422 affectation |
| `prime_sans_parametre` | info | Auto art. 58 ignoré faute de `montant_defaut` |

`valider` : 422 s’il reste une anomalie **bloquante**. Les `info` passent.

### 9.4 Notifications

Via `NotificationService`, rôle `rh` (+ `directeur-general` en info à `cloture` seulement) :

| Événement | Message type |
|-----------|----------------|
| `genere` | Lot {mois}/{année} généré ({n} bulletins). |
| `valide` | Lot {mois}/{année} validé. |
| `cloture` | Lot {mois}/{année} clôturé. |

---

## 10. Bulletin enrichi

Nouvelle vue `resources/views/pdf/bulletin-paie.blade.php`.

Blocs : identité (snapshot) · période du lot · tableau des gains (base + primes + indemnités + allocations) · tableau des retenues · net à payer.

Ne **pas** modifier le bulletin indiciaire existant (contrat FE actuel).

---

## 11. Tests Feature (`tests/Feature/`)

| Fichier | Couverture minimale | Statut |
|---------|---------------------|--------|
| `PaieElementTest` | CRUD, 422 delete système, seeder codes présents | ✅ |
| `PaieAffectationTest` | Éligibilité DD vs CB (représentation), chevauchement 422, stagiaire transport | ✅ |
| `PaieCalculAncienneteTest` | 1 an → 0 ; 2 ans → 2 % ; 5 ans → 5 % ; 50 ans → 40 % |
| `PaieLotTest` | Unique période, circuit statuts, hors grille sans fonctionnel → bloquante, regen, 422 valider si bloquante, 422 generer si clôturé |
| `PaieFinAnneeTest` | Décembre : présence / prorata position / licenciement |
| `PaieBulletinTest` | PDF 200 sur ligne générée ; bulletin `/salaires-agents/{id}/bulletin` **inchangé** (régression) |

Permission : un user `rh` (comme les autres Feature). 401 / 403 smoke léger.

---

## 12. Ordre de code (sessions)

Ne pas ouvrir D.5.4 avant qu’un lot `genere` existe en test.

```
1. Migrations + enums + models
    → 2. Interface/Repo/binding éléments + Service + CRUD + seeder + tests
        → 3. Affectations + éligibilité + tests
            → 4. PaieCalculService + tests ancienneté (sans HTTP)
                → 5. Lots : brouillon + generer (base + ancienneté + affectations) + circuit
                    → 6. Contrôle anomalies + art. 56 fin d’année + art. 58 auto
                        → 7. Barèmes mission + intérim + formation (sur affectation)
                            → 8. Bulletin PDF enrichi
                                → 9. Export CSV (D.5.5, reportable)
                                    → 10. Swagger + note FE § paie + cases cochées
```

| Session | Livrable | Vague | Statut |
|---------|----------|-------|--------|
| 1–2 | Référentiel `/paie/elements` + seeder CCN | D.5.1 | ✅ |
| 3 | `/paie/affectations` | D.5.2 | ✅ |
| 4–7 | Lots + calculs CCN | D.5.3 | ⬜ |
| 8 | Bulletin enrichi | D.5.4 | ⬜ |
| 9 | Export | D.5.5 | ⬜ reportable |
| 10 | Contrat FE + Swagger | — | ⬜ |

Checklist d’un domaine à chaque session : voir [`architecture.md`](./architecture.md) § 5.

---

## 13. Hors scope (ne pas glisser)

- Prestations / circuit demandes (D.3.4)
- Indemnité licenciement art. 111–114
- Indemnité retraite art. 119 (code inactif seulement)
- Capital décès art. 121
- Heures supplémentaires (élément absent ; astreinte non cumulable **quand** HS existera)
- Prorata mise à pied automatique (V1 = anomalie info)
- Mail / SMS, cloisonnement bureau (F)
- Recalcul d’un lot `cloture` (storno / lot complémentaire = plus tard)
- Modification de `montant_net` sur `salaires_agents`

---

## 14. Impacts transverses

| Zone | Action |
|------|--------|
| `AppServiceProvider::register()` | 3 bindings |
| `routes/api.php` | Section `// ── Paie D.5 ──` avant reporting |
| `database/seeders/DatabaseSeeder.php` | `PaieElementSeeder` après fonctions |
| `PermissionSeeder` / `RoleSeeder` | **inchangés** V1 |
| `resources/views/pdf/bulletin-paie.blade.php` | **nouveau** |
| `note-fe-etat-implementations.md` | § contrat `/paie` **après** D.5.1 minimum (liste + un POST) |
| `AyantDroitInterface` | Lecture seulement depuis `PaieLotService` (pas d’écriture affaires sociales) |
| `SanctionInterface` | Lecture mises à pied / licenciement pour anomalies et fin d’année |

OpenAPI : tag `Paie`. `operationId` préfixe `paie`.

---

## 15. Contrat FE (cible, à coller dans la note après le 1er lot de code)

D.5.1 : voir [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md) §2h.

- Écran **éléments** OK dès `GET /paie/elements` 200.
- Ne **pas** ouvrir lots / affectations / bulletin enrichi avant D.5.2+.
- Menu : même permission `consulter-salaires` que la grille.
- DG/DC/DD : continuer à masquer le bulletin **indiciaire** ; le bulletin **enrichi** est celui du lot (`hors_grille: true`, base 0, ligne salaire fonctionnel).
- Lot : 4 boutons d’action selon `statut` (`generer` / `controler` / `valider` / `cloturer`). Désactiver si 403.
- Anomalies : badge sur la ligne ; bloquantes = interdire valider côté UI **et** 422 API.
- `montant_defaut` null / `a_parametrer: true` : afficher « à paramétrer (comité de direction) » — ne pas inventer un montant.

---

## 16. Checklist de clôture d’un sous-lot

- [x] Cases cochées dans ce fichier (D.5.1)
- [x] Ligne journal dans [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md)
- [x] Tests Feature verts
- [x] Swagger à jour (tag `Paie`)
- [x] Note FE à jour (à partir de D.5.1)

---

## 17. Journal

| Date | Fait |
|------|------|
| 2026-09-16 | Découpage technique (tables, enums, API, calculs, ordre de code). Branche `feature/paie-d5`. |
| 2026-09-16 | **D.5.1** : `/api/paie/elements` + seeder 29 codes CCN. Tests `PaieElementTest`. |
| 2026-09-16 | **D.5.2** : `/api/paie/affectations` (éligibilité fonction / stagiaire, chevauchement). Tests `PaieAffectationTest`. |

*La convention collective prime sur ce plan en cas de conflit.*
