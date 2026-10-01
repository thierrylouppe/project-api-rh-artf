# Plan — Mise en conformité CCN (modules 3, 4, 5)

> Date : **2026-09-16**  
> Droit : [`convention-collective-artf.md`](./convention-collective-artf.md) art. **46–57**, **73–82**, annexes 1–2  
> Architecture : [`architecture.md`](./architecture.md)  
> Suivi vagues : [`plan-prochaines-fonctionnalites.md`](./plan-prochaines-fonctionnalites.md)  
> Contrat FE : [`note-fe-etat-implementations.md`](./note-fe-etat-implementations.md)

**Objectif :** aligner l’**intégration**, la **carrière** et la **grille / salaire de base** sur les délais, plafonds et éligibilités CCN — **sans** ouvrir la paie (primes / lots = Vague **D.5**).

**État :** lots **A–E** livrés. D.5 (paie) ensuite — [`plan-module-paie.md`](./plan-module-paie.md). Ne pas relancer art. 73–75 (déjà livrés). Ne pas recoder la formule de grille (annexe 2 déjà conforme).

---

## 1. Cadre

Chaîne : Route → FormRequest → Controller → **Service** → **Interface** → Repository → Model.

- Controller : Services uniquement. Service : Interfaces uniquement. Barèmes CCN dans le **Service**.
- Montants « fixés par le comité de direction » : **paramétrables**, pas en dur. Formules CCN (1/2/3 mois, 5 ans, 40 %, etc.) : **métier**, pas « à peu près ».
- Extension, pas rupture : champs optionnels, nouveaux endpoints. Pas de rename de route existante.
- Permissions : réutiliser `creer-recrutement` / `creer-contrats` / `gerer-salaires` / `consulter-salaires` autant que possible. Nouvelle permission seulement si le FE en a besoin.
- Notifications : canal `database` via `NotificationService`. Mail / SMS hors scope.
- Art. **73–75** : **déjà livrés** (`/carriere/reclassements`) — hors lots ci-dessous.

### Frontière avec D.5 Paie

| Dans **cette** vague (E) | Dans **D.5** (ne pas coder ici) |
|--------------------------|----------------------------------|
| Essai, contrat 30 j, pièces, CNSS à l’entrée | Primes art. 56 (responsabilité, ancienneté, 13ᵉ, etc.) |
| Positions art. 76–80 (dates, éligibilité, effet **arrêt** du salaire indiciaire) | Indemnités art. 57 (transport, missions, intérim, formation) |
| Hors grille DG/DC/DD (pas de ligne indiciaire) | Lot mensuel, bulletin enrichi, net |
| Bonifications d’échelon **à l’accès** (annexe 1) | Prime de transport stagiaires (art. 54) si elle devient un **élément de paie** |

---

## 2. Constat (2026-09-16)

| Sujet | Code aujourd’hui | Écart CCN |
|--------|------------------|-----------|
| Pièces art. 46 | Seed embauche : liste + ACE ; conditionnels mariage / déjà salarié | Livré (lot B) |
| Art. 47 CNSS | Immatriculation à `integrer` + affiliation CNSS | Livré (lot B) |
| Art. 48 réembauche | Flag `prioritaire_reembauche_jusquau` + meta à la création | Livré (lot E) |
| Art. 49 essai | Table `contrats` : dates + 1/2/3 mois | Livré (lot A) |
| Art. 50 essai poste supérieur | Nomination `soumis_a_essai` + confirmer/rompre | Livré (lot E) |
| Art. 52 contrat 30 j | Recrutement externe : `necessite_contrat = false` | Lettre + contrat sous **30 jours ouvrables** ; mentions essai / emploi / rémunération |
| Art. 54 stage | Convention + `gratification` libre | Prime transport non cadrée (→ D.5) |
| Art. 73–75 | `ReclassementService` | **Conforme** |
| Art. 76–80 | `StatutAgent` + `PUT` libre | Aucune règle 5 ans / 3 ans / 2 ans × 2 / préavis 3 mois ; doc FE **fausse** sur le détachement (rémunération) |
| Art. 81–82 rapprochement | Motif + 4 pièces, pas d’accord auto | Livré (lot E) |
| Annexe 2 grille | `SalaireService::generateGrille` | **Conforme** (point 300, 10 × 12) |
| Art. 55 hors grille | `creerSalaireInitial` pour tout CDI/CDD | DG / DC / DD peuvent recevoir un salaire indiciaire |
| Annexe 1 accès | `DiplomeSeeder` préremplit classe | Pas de règle ; classes I–II seedées comme « diplômes » ; bonifs d’échelon absentes |

---

## 3. Lots et ordre

```
Lot A  Art. 49–52 — essai + contrat d’engagement
    → Lot B  Art. 46–47 — pièces + CNSS à l’entrée
        → Lot C  Art. 76–80 — positions conventionnelles
            → Lot D  Art. 55 + annexe 1 — hors grille DG/DC/DD + accès / bonifs
                → Lot E  Art. 48, 50, 81–82 — réembauche, essai supérieur, rapprochement
```

| Lot | Livrable | Priorité | Statut |
|-----|----------|----------|--------|
| **A** | Essai 1/2/3 mois, renouvellement ×1, rupture sans préavis, contrat sous 30 j, mentions | Haute (légal embauche) | ✅ |
| **B** | Pièces CCN + CNSS obligatoire à l’intégration CDI/CDD | Haute | ✅ |
| **C** | Dossier de position (détachement / dispo / exceptionnelle / drapeau) + effets | Haute (carrière vivante) | ✅ |
| **D** | Pas de grille pour DG/DC/DD ; bonifs d’échelon d’accès | Moyenne | ✅ |
| **E** | Réembauche 2 ans, essai poste supérieur, mutation rapprochement | Moyenne / plus tard | ✅ |

Coder **A → B → C** avant D.5. **D** avant D.5 si possible (sinon le lot paie recréera des salaires indiciaires pour le DG). **E** peut suivre D.5.

---

## 4. Lot A — Essai et contrat d’engagement (art. 49–52)

### 4.1 Règles métier (Service)

| Règle | Valeur |
|-------|--------|
| Durée d’essai | Classes **1–4** → **1 mois** ; **5–6** → **2 mois** ; **7–10** → **3 mois** |
| Renouvellement | **Une fois** seulement (même durée). 422 si déjà renouvelé |
| Pendant l’essai | Rupture **sans préavis ni indemnité** (les deux parties) |
| Salaire pendant l’essai | **Minimum de la classe** = ligne grille **échelon 1** (même si l’agent a un échelon cible plus élevé) |
| Après essai concluant | Engagement définitif : signature / confirmation ; salaire passe à l’échelon prévu |
| Délai art. 52 | Contrat écrit **≤ 30 jours ouvrables** après `date_prise_service` (week-ends + fériés exclus, comme les congés) |
| Sans écrit | Le contrat est **réputé CDI** dès la prise de service — **alerte RH**, pas d’auto-création silencieuse en V1 |
| Recrutement externe / Contractuel | `necessite_contrat = true` (changement de seed **uniquement** ces deux types) |
| Mutation / détachement / MAD / réintégration | Pas de CDI ARTF obligatoire (reste `false`) |
| Types concernés par l’essai | **CDI** et **CDD** seulement (pas STG / CONS) |

Mentions minimales du contrat (art. 52) — déjà sur l’agent ou à exposer sur `ContratResource` :

`noms, nationalité, date/lieu de naissance, sexe, situation matrimoniale, date/lieu de recrutement, essai (durée + dates), emploi (fonction), rémunération, lieu de travail (affectation si connue)`.

Ne pas dupliquer la fiche agent en colonnes contrat : **composer** la Resource. Colonnes nouvelles = seulement l’essai.

### 4.2 Données

Migration `contrats` :

| Colonne | Type | Notes |
|---------|------|--------|
| `date_debut_essai` | date nullable | = `date_debut` à la création CDI/CDD |
| `date_fin_essai` | date nullable | calculée |
| `duree_essai_mois` | tinyint nullable | 1, 2 ou 3 |
| `essai_renouvele` | boolean default false | |
| `statut_essai` | string nullable | `en_cours` \| `renouvele` \| `concluant` \| `rompu` \| `non_applicable` |
| `date_confirmation_essai` | date nullable | |
| `lieu_recrutement` | string nullable | art. 52 |

Enum PHP `StatutEssai`. STG / CONS : `statut_essai = non_applicable`, dates null.

### 4.3 Service

Étendre **`ContratService`** (pas de nouveau domaine) :

- `dureeEssaiMois(Agent $agent): int` — via classe / grade `niveau` (1–4 / 5–6 / 7–10). 422 si classe inconnue.
- `afterCreate` : en plus du salaire initial, poser l’essai si CDI/CDD.
- `renouvelerEssai(int $id)` — une fois ; décale `date_fin_essai` ; notifie RH + agent.
- `confirmerEssai(int $id)` — statut `concluant` ; `SalaireAgentService` bascule vers l’échelon cible (si > 1).
- `rompreEssai(int $id, array $data)` — statut contrat terminé, essai `rompu`, **pas** d’indemnité. Voyage retour art. 49 : champ `cout_voyage_retour` optionnel **plus tard** (D.5 / indemnités).

Job (comme les stages) : `ContratEssaiEnFinDateJob` — J-7 et J-0, weekdays 08:00.  
Job / check : `ContratDelai30JoursJob` — dossiers `INTEGRE` + PDS sans contrat CDI/CDD après 30 j ouvrables → notif RH `domaine: integration`, `action: contrat_delai_depasse`.

### 4.4 Routes (`/carriere/contrats`)

| Méthode | URI | Permission |
|---------|-----|------------|
| `POST` | `/carriere/contrats/{id}/renouveler-essai` | `modifier-contrats` |
| `POST` | `/carriere/contrats/{id}/confirmer-essai` | `modifier-contrats` |
| `POST` | `/carriere/contrats/{id}/rompre-essai` | `modifier-contrats` |
| `GET` | `/carriere/contrats/alertes/delai-30-jours` | `consulter-contrats` |

Alias `/integration/contrats/…` **uniquement** si les alias contrats existent encore.

### 4.5 Tests Feature

- Classe I → 1 mois ; classe V → 2 ; classe VIII → 3.
- 2ᵉ renouvellement → 422.
- `rompre-essai` sans préavis (contrat `terminé`, pas d’indemnité).
- Salaire pendant essai = échelon 1 ; après confirmation = échelon cible.
- Recrutement externe : `necessite_contrat === true` après seed.
- Alerte 30 j : PDS + 31 j ouvrables sans contrat.

### 4.6 FE

- Wizard / checklist : étape contrat **obligatoire** pour recrutement externe et contractuel.
- Badge essai + dates + bouton renouveler / confirmer / rompre selon `statut_essai`.
- `prochaine_etape` : `renouveler-essai` \| `confirmer-essai` \| `null`.

---

## 5. Lot B — Pièces art. 46 et CNSS art. 47

### 5.1 Référentiel documents

Ajouter dans `TypeDocumentSeeder` (noms stables) :

- Acte de mariage  
- Récépissé de l’agence congolaise pour l’emploi  
- Carte de travail  
- Certificat de travail (précédent employeur)  
- Numéro d’immatriculation CNSS (pièce **ou** champ agent — voir 5.3)

`TypeIntegrationSeeder` — **Recrutement externe** et **Contractuel** :

Toujours : liste actuelle **+** récépissé ACE.

Conditionnels (circuit documents, pas forcément `sync` obligatoire pour tous) :

| Condition | Pièces |
|-----------|--------|
| Situation familiale `marie` | Acte de mariage |
| Flag dossier `deja_salarie = true` (nouveau booléen, défaut false) | Carte de travail, certificat de travail, n° CNSS |

Ne pas alourdir Mutation / Détachement / stages (listes actuelles conservées).

### 5.2 Dossier

Colonne `dossiers_integration.deja_salarie` (boolean default false).  
`DossierIntegrationService` : au `soumettre` / `valider-rh`, si `deja_salarie` et pièces manquantes → 422 (même mécanique que `documents_obligatoires`).

Acte de mariage : 422 seulement si `situation_familiale.statut_matrimonial = marie` **et** pièce absente.

### 5.3 Art. 47 — immatriculation

À **`integrer`** (CDI/CDD / recrutement externe / contractuel, pas stage) :

1. Si `agent.numero_cnss` vide → 422 *« Immatriculation CNSS obligatoire (art. 47) »* **ou** création d’une affiliation CNSS `active` si un organisme `code=CNSS` existe et qu’un numéro est fourni dans le payload d’intégration.
2. Si numéro présent et pas d’affiliation CNSS active → **créer** l’affiliation (réutiliser `AffiliationSocialeService`, pas d’Eloquent dans le controller).

Self-service agent : hors scope. Alerte `sans-affiliation-cnss` déjà livrée : elle devient le filet, plus la voie normale.

Carte de travail (art. 47 « faire délivrer ») : **hors V1** (pas de génération PDF carte). Traçage : document type « Carte de travail » uploadable.

### 5.4 Tests + FE

- Soumission recrutement externe sans ACE → 422.  
- `deja_salarie=true` sans certificat de travail → 422.  
- `integrer` sans `numero_cnss` sur CDI → 422.  
- FE : checkbox « déjà salarié » + champs conditionnels ; CNSS sur la fiche avant `integrer`.

---

## 6. Lot C — Positions conventionnelles (art. 76–80)

**Ne plus** changer `detachement` / `disponibilite` / `position_exceptionnelle` / `sous_le_drapeau` par un `PUT` agent nu.

### 6.1 Domaine

Nouveau domaine **`PositionConventionnelle`** (chaîne complète).

Table `positions_conventionnelles` :

| Colonne | Notes |
|---------|--------|
| `agent_id`, `type` | `detachement` \| `disponibilite` \| `position_exceptionnelle` \| `sous_le_drapeau` |
| `statut` | `soumise` \| `active` \| `cloturee` \| `rejetee` |
| `date_debut`, `date_fin` | |
| `organisme_accueil` | détachement |
| `consentement_agent` | bool, **obligatoire** si détachement (sauf `detachement_office`) |
| `detachement_office` | bool, art. 78 al. 8 |
| `nb_renouvellements` | dispo : max **2** |
| `decision_dg_user_id`, `commentaire` | |
| `piece_path` | optionnel |

À l’activation : `agent.statut` = type. À la clôture : retour `actif` (sauf autre motif).

### 6.2 Règles Service (422 métier)

**Détachement (art. 78)**

- Ancienneté ARTF ≥ **5 ans** (`date_prise_service`).
- Consentement **sauf** d’office.
- Durée max **5 ans** par période ; renouvellement = nouvelle période ≤ 5 ans.
- Préavis de **fin** : **3 mois** (champ `date_fin` ≥ aujourd’hui + 3 mois, sauf d’office documenté).
- Décision : rôle `directeur-general` ou `admin`.
- **Effet paie :** clôturer le `salaire_agent` actif à `date_debut` (la CCN **coupe la rémunération**). L’agent **reste éligible** à l’avancement / notation selon art. 65 : aujourd’hui le code **exempte** le détachement de notation — **conserver** (art. 65 « exempté : stage, détachement, position exceptionnelle »). L’« avance dans son cadre » art. 78 se traduit par : **pas** de blocage de `avancerEchelon` manuel RH, mais **pas** de fiche de notation. Corriger la note FE : rémunération **non** maintenue.
- Réintégration : réaffecter un emploi de **sa classe** (422 informative si aucune affectation active — ne pas auto-créer).

**Disponibilité (art. 79)**

- Ancienneté ≥ **3 ans**.
- Durée ≤ **2 ans**, renouvelable **2 fois** (`nb_renouvellements` < 2).
- Préavis **3 mois** à la demande et au retour.
- Décision DG.
- Effet : clôturer salaire ; **exclure** notation (déjà `exempteDeNotation`) ; **ne pas** avancer l’échelon auto (422 sur `avancerEchelon` si statut `disponibilite`).
- Au retour : classement d’origine ; **pas** le même poste (ne pas réactiver l’ancienne nomination — clôturer la nomination active à la mise en dispo).

**Position exceptionnelle (art. 80)**

- Motif : cabinets / institutions.
- Rémunération **et** avancement **maintenus** (ne pas clôturer le salaire). Exempté de **notation** (art. 65).

**Sous le drapeau (art. 80)**

- Statut + dates. Régime « congés administratifs » : **pas** de moteur congé spécifique en V1 (le statut suffit pour exclure notation). Ne pas clôturer le salaire (régime congé, pas disponibilité).

### 6.3 `PUT /personnel/agents/{id}` `statut`

Si le statut cible est une position CCN (76–80) → **422** *« Utiliser POST /carriere/positions »*.  
`actif` / `inactif` / `suspendu` / `retraite` restent sur le PUT (suspendu = discipline déjà géré).

### 6.4 Routes

Préfixe `/carriere/positions`. Permissions : lecture `consulter-contrats` (ou `consulter-salaires`) ; écriture `gerer-salaires` pour RH **soumettre** ; `POST …/approuver` = rôle `directeur-general` (comme art. 74).

| Méthode | URI |
|---------|-----|
| `GET/POST` | `/carriere/positions` |
| `GET` | `/carriere/positions/{id}` |
| `POST` | `…/{id}/approuver` · `…/{id}/rejeter` · `…/{id}/cloturer` · `…/{id}/renouveler` |
| `GET` | `/carriere/agents/{id}/positions` |

### 6.5 Tests + FE

- Détachement < 5 ans → 422 `anciennete`.  
- Dispo 3ᵉ renouvellement → 422.  
- Détachement actif → plus de `salaire_agent` `actif`.  
- PUT statut `detachement` → 422.  
- FE : écran « Positions » ; corriger le libellé « rémunération maintenue » du détachement.

---

## 7. Lot D — Hors grille et accès (art. 55, annexe 1)

### 7.1 DG / DC / DD hors grille (art. 55 + annexe 1 art. 2)

Fonctions seedées : `DG`, `DC`, `DD` (`FonctionSeeder`).

`SalaireAgentService::creerSalaireInitial` / `changerClasse` / `avancerEchelon` :

- Si nomination active (sinon `agent.fonction`) ∈ {Directeur Général, Directeur Central, Directeur Départemental} → **ne pas** créer de ligne indiciaire. Retour `null` + message Resource `salaire_fonctionnel: true`.
- Flag agent ou lecture dérivée : `hors_grille: true`.
- Montant fonctionnel : **pas** de barème CCN (comité de direction) → hors lot ; D.5 pourra poser un élément « salaire fonctionnel » paramétrable.

`POST /salaires/generation` : inchangé (la grille des classes 1–10 reste).

### 7.2 Annexe 1 — diplômes vs emplois

- Classes **I–II** : ce sont des **emplois**, pas des diplômes. Ne plus les seed dans `diplomes`. Les laisser dans `fonctions` ou un référentiel emplois existant. Mapping classe I/II : à la **fonction** / grade, pas au diplôme.
- Classes **III–IX** : diplômes CEPE, CAP, BEPC/BET/BEP, Bac, DUT/BTS/BENAM/Licence, Maîtrise/DEA/Master, Doctorat — garder `DiplomeSeeder` + `classegrillesalariale_id`.

Ajouter `diplomes.bonification_echelons` (tinyint default 0) :

| Diplôme | Classe | Bonif. échelon à l’entrée |
|---------|--------|---------------------------|
| BEP | V | **+1** |
| Licence / Bachelor | VII | **+1** |
| Master II / DESS / DSENAM / MBA | VIII | **+1** |
| Doctorat | IX | **+2** |
| Autres | — | 0 |

`creerSalaireInitial` : `echelon_effectif = min(12, echelon_depart + bonification)`.  
Ne **pas** bloquer un classement manuel différent (la CCN donne des conditions d’accès, l’employeur peut justifier) : 422 **souple** = warning dans `meta.annexe1` plutôt qu’un refus, **sauf** si on active plus tard un flag paramètre `strict_annexe1`. V1 = **meta + défaut automatique**, pas de 422.

Ajouter le diplôme **Maîtrise** (classe VIII, bonif 0) — aujourd’hui absent ; Master II reste +1.

### 7.3 Tests + FE

- Création salaire DG → `data: null`, `salaire_fonctionnel: true`.  
- Licence → échelon initial 2 si départ 1.  
- FE : masque bulletin indiciaire pour DG/DC/DD ; badge « salaire comité de direction ».

---

## 8. Lot E — Compléments (art. 48, 50, 81–82)

À coder **après A–D** (ou après D.5). Moins bloquant pour l’embauche courante.

### 8.1 Réembauche (art. 48) ✅

- Sur archivage / licenciement : si motif `diminution_activite` \| `reorganisation` (champ motif à ajouter au flux rupture **quand** il existera). En V1 E : flag manuel RH `prioritaire_reembauche_jusquau` (date = licenciement + **2 ans**).
- À la création d’un dossier recrutement externe : si homonyme / ancien `numero_cnss` dans la fenêtre → `meta.priorite_reembauche: true` (pas de blocage des autres candidats).
- Passé 2 ans : encore **1 an** sous réserve d’un **nouvel essai** (lot A déjà là).

Rupture complète art. 105–117 : **hors E** (préavis, indemnités = autre plan).

### 8.2 Essai emploi supérieur (art. 50) ✅

- Sur **nomination** vers une fonction de classe supérieure : option `soumis_a_essai` ; durée = essai de la **classe cible** (lot A).
- Si `rompre-essai` dans ce contexte : **rétablir** fonction + salaire **précédents** (pas une rétrogradation). Historique nomination : clôturer la nouvelle, réactiver l’ancienne.

### 8.3 Rapprochement de conjoints (art. 81–82) ✅

- Motif d’affectation `rapprochement_conjoints`.
- Pièces : demande manuscrite, acte de mariage, note d’affectation du conjoint, attestation de résidence.
- L’API **n’accorde pas** automatiquement : circuit affectation existant + 422 si pièces manquantes. Commentaire RH « opportunité de service » (art. 82 al. 2).

---

## 9. Hors scope (ne pas glisser dans E)

- Primes art. 56, indemnités art. 57, intérim 6 mois, missions → **D.5**
- Prime transport stagiaires art. 54 → D.5 (laisser `gratification` libre)
- PDF actes d’intégration, carte de travail générée
- Recrutement amont (concours)
- Rupture / retraite art. 105–120 (préavis 1/2/3 mois, âges 57/60/65, bonif 24 mois avant départ)
- Commission de réclamation art. 146–149
- Cloisonnement bureaux (Vague F)
- Mail / SMS

---

## 10. Impacts transverses

| Fichier / zone | Lots |
|----------------|------|
| `TypeIntegrationSeeder` `necessite_contrat` | A |
| `TypeDocumentSeeder` + documents par type | B |
| `ContratService` + migration `contrats` | A |
| `SalaireAgentService` (min essai, hors grille, bonifs) | A, D |
| `DossierIntegrationService::integrer` | A (alerte 30 j), B (CNSS) |
| `AgentService` / `UpdateRequest` statut | C |
| Nouveau `PositionConventionnelle*` | C |
| `DiplomeSeeder` + colonne `bonification_echelons` | D |
| `Fonction` DG/DC/DD | D (lecture, pas nouveau seed) |
| `note-fe-etat-implementations.md` journal + §2c-bis détachement | A, C |
| `plan-prochaines-fonctionnalites.md` Vague E | ce document |
| Jobs `routes/console.php` | A, C (échéances positions) |
| Swagger | tous |

Permissions : **pas** de nouvelle permission en A/B/D. Lot C : réutiliser `consulter-salaires` / `gerer-salaires` + rôle DG pour approuver (même schéma que reclassement 74).

---

## 11. Ordre de développement recommandé (sessions de code)

1. Migration essai + `ContratService` (durée, afterCreate) + tests classes  
2. Endpoints renouveler / confirmer / rompre + salaire échelon 1  
3. Seed `necessite_contrat` + job / alerte 30 j  
4. Documents + `deja_salarie` + CNSS à `integrer`  
5. Table positions + Service règles 78–79 + blocage PUT  
6. Effets paie / notation + correction note FE détachement  
7. Hors grille DG/DC/DD + bonifs diplômes  
8. Lot E si le métier le demande  

Après **A–D** : Vague **D.5** Paie — [`plan-module-paie.md`](./plan-module-paie.md) (les primes s’appuient sur un salaire de base et des positions déjà justes).

---

## 12. Checklist de clôture d’un lot

- [ ] `php artisan migrate`  
- [ ] Bindings IoC si nouveau domaine (lot C)  
- [ ] Tests Feature du lot verts  
- [ ] Swagger régénéré  
- [ ] Journal FE (`note-fe-etat-implementations.md`)  
- [ ] Case cochée ici + ligne dans `plan-prochaines-fonctionnalites.md`

---

*Document de travail — à cocher au fil du code. La convention collective prime sur ce plan en cas de conflit.*
