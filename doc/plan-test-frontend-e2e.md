# Plan de test Frontend — bout en bout (E2E)
> **API RH ARTF** — Version 2026-09  
> **Périmètre :** Tous les modules livrés (Auth → Intégration → Carrière → Paie → Congés → Évaluation → Discipline → Affaires sociales)  
> **Base URL API :** `http://localhost:8000/api`  
> **Prérequis :** `php artisan migrate:fresh --seed` sans erreur

---

## 🚀 Mise en place

### Environnement
| # | Action | Critère OK |
|---|---|---|
| S0 | Lancer l'API | `GET /health` → `{ "status": "ok" }` |
| S1 | Lancer le front | App accessible sur `http://localhost:3000` (ou port configuré) |
| S2 | Ouvrir le Swagger | `http://localhost:8000/api/documentation` accessible |
| S3 | Ouvrir les DevTools réseau | Pour inspecter les requêtes/réponses en parallèle |

### Comptes de test (produits par les seeders)

| Profil | Email | Mot de passe | Rôle |
|---|---|---|---|
| 🔧 Admin système | `admin@artf.cg` | `Admin@2026` | `admin` |
| 🔧 Admin (compte réel) | `jean.pierre.moukala@artf.cg` | `Moukala@2026` | `admin` + `directeur-general` |
| 👔 DG | `jean.pierre.moukala@artf.cg` | `Moukala@2026` | `directeur-general` + `admin` |
| 🏢 DRH (DRHL) | `lydiane.gambou@artf.cg` | `Gambou@2026` | `directeur` + `rh` |
| 👥 RH générique | `rh@artf.cg` | `Rh@2026` | `rh` |
| 📋 CS (DRHL) | `herve.milandou@artf.cg` | `Milandou@2026` | `chef-service` |
| 📂 CB (DRHL) | `rosette.mounanga@artf.cg` | `Mounanga@2026` | `rh-formation` |
| 👨 Directeur DF | `pierre.biyoudi@artf.cg` | `Biyoudi@2026` | `directeur` |
| 🧑 Agent simple | `yves.loubaki@artf.cg` | `Loubaki@2026` | `agent` |
| 🧑 Agent sanctionné | `rodrigue.nkaya@artf.cg` | `Nkaya@2026` | `agent` |

---

## Parcours 1 — Authentification & Navigation *(~20 min)*

> **Objectif :** Vérifier le login, les menus conditionnels et la déconnexion pour chaque profil

### P1.1 — Login & menus Admin

1. Accéder à la page de connexion
2. Saisir `admin@artf.cg` / `Admin@2026` → **Connexion**
3. ✅ Redirection vers le tableau de bord
4. ✅ Menu latéral affiche **tous** les modules : Structure, Référentiels, Intégration, Personnel, Carrière, Grille, Paie, Congés, Évaluations, Discipline, Affaires sociales, Formations, Reporting
5. ✅ Aucun module masqué
6. Cliquer **Déconnexion** → retour page login · token invalidé

### P1.2 — Login & menus RH

1. Se connecter avec `rh@artf.cg` / `Rh@2026`
2. ✅ Menu visible : Intégration, Personnel, Carrière, Paie, Congés, Affaires sociales, Formations, Reporting
3. ❌ **Absent** : Gestion utilisateurs/rôles, Structure org. (écriture), Discipline > prononcer
4. ✅ Permissions dans le token : `consulter-salaires`, `gerer-salaires`, `valider-conges`, etc.

### P1.3 — Login & menus DG

1. Se connecter avec `jean.pierre.moukala@artf.cg` / `Moukala@2026`
2. ✅ Menu visible : Agents (lecture), Congés (validation), Évaluations (validation), Reporting, Reclassements (lecture + approuver 74/75), Discipline (prononcer), Affaires sociales (décider)
3. ❌ **Absent** : Paie (saisie), Recrutement (pilotage), Référentiels (écriture), Salaires (détail grille)

### P1.4 — Login & menus Directeur

1. Se connecter avec `pierre.biyoudi@artf.cg` / `Biyoudi@2026`
2. ✅ Visible : Agents de la DF (lecture), Congés (valider N+1), Discipline (proposer), Évaluations (noter)
3. ❌ **Absent** : Paie, Grille, Recrutement, Reporting
4. Vérifier que la liste agents n'affiche que les agents de la D.F (**cloisonnement** si implémenté)

### P1.5 — Login & menus Agent

1. Se connecter avec `yves.loubaki@artf.cg` / `Loubaki@2026`
2. ✅ Visible : Mon profil, Mes congés, Mes absences, Ma fiche d'évaluation (lecture)
3. ❌ **Absent** : Tout le reste
4. ✅ Nom affiché en haut : « Yves LOUBAKI »

### P1.6 — Token expiré / session invalide

1. Se connecter, puis invalider manuellement le token en base (ou attendre l'expiration)
2. Faire une navigation vers un écran protégé
3. ✅ Redirection automatique vers le login avec message « Session expirée »

---

## Parcours 2 — Consultation du personnel *(~30 min)*

> **Compte :** `rh@artf.cg` | **Données seedées :** 51 agents actifs

### P2.1 — Liste des agents

1. Ouvrir le module **Personnel / Agents**
2. ✅ 51 agents affichés (paginer si nécessaire)
3. ✅ Chaque ligne : matricule, nom/prénom, direction, fonction, statut `actif`
4. Rechercher « MOUKALA » → ✅ Jean-Pierre MOUKALA remonte en 1er résultat
5. Filtrer par direction « D.F » → ✅ 6 agents (1 Directeur + 1 CS + 1 CB + 2 Agents + 1 Stagiaire)
6. Filtrer statut « stagiaire » → ✅ 10 stagiaires (STG-0001 à STG-0010)

### P2.2 — Fiche agent complète

1. Cliquer sur **Nadège MOUAMBA** (ARFT-00010 · 3 enfants · Marié · D.F)
2. ✅ Onglet **Identité** : adresse « 40 Rue des Flamboyants, Talangaï, Brazzaville »
3. ✅ Onglet **Situation familiale** : Marié(e) · 3 enfants
4. ✅ Onglet **Ayants droit** : Conjoint Guy MOUAMBA + 3 enfants (Inès 2014, Malo 2016, Lucie 2019)
5. ✅ Onglet **Carrière** : Contrat CDI actif · Affectation Bureau de la Recette (B.RCT) · Salaire 502 500 FCFA
6. ✅ Onglet **Documents** : Acte de naissance + Diplôme + CV listés avec chemin fictif
7. ✅ Onglet **Évaluation** : Fiche 2025 `finalisee` avec note globale visible
8. ✅ Contact d'urgence : Guy MOUAMBA · Époux

### P2.3 — Fiche agent hors grille (DG)

1. Ouvrir la fiche de **Jean-Pierre MOUKALA** (ARFT-00001)
2. ✅ Badge « Hors grille — Salaire fonctionnel (art. 55) »
3. ✅ Aucun montant de grille affiché
4. ✅ Bouton « Bulletin PDF » absent ou grisé

---

## Parcours 3 — Module Congés & Absences *(~45 min)*

> Simuler un circuit complet avec 4 acteurs

### P3.1 — Agent pose une demande de congé

1. Se connecter en tant que **Bertrand TSIBA** (`bertrand.tsiba@artf.cg` / `Tsiba@2026`)
2. Aller dans **Mes congés** → **Nouvelle demande**
3. Choisir type : « Congé annuel »
4. Renseigner dates : du 1er au 18 octobre 2026 (18 jours ouvrés)
5. ✅ Solde disponible affiché avant soumission (doit être ≥ 18)
6. Soumettre → ✅ Statut « Soumise » · Notification envoyée au N+1

### P3.2 — Validation N+1 (Chef de bureau)

1. Se connecter en tant que **Carmélie NGOUBILI** (`carmelie.ngoubili@artf.cg` / `Ngoubili@2026`) — CB de D.R
2. Ouvrir **Congés à valider**
3. ✅ La demande de TSIBA apparaît dans la liste
4. Cliquer **Valider** → ajouter un commentaire → confirmer
5. ✅ Statut → « Validée N+1 » · Notification au RH

### P3.3 — Validation RH

1. Se connecter en tant que **RH** (`rh@artf.cg` / `Rh@2026`)
2. Congés à valider → ✅ Demande de TSIBA visible (statut `validee_n1`)
3. Vérifier solde automatiquement dans la fiche → « Solde suffisant »
4. Valider → ✅ Statut → « Validée RH »

### P3.4 — Validation DG

1. Se connecter en tant que **Jean-Pierre MOUKALA** (`jean.pierre.moukala@artf.cg` / `Moukala@2026`)
2. Congés → ✅ Demande visible avec historique des validations précédentes
3. Valider → ✅ Statut → « Validée DG » · Congé accordé

### P3.5 — Vérifications post-accord

1. Reconnexion en tant que **TSIBA**
2. ✅ Demande affiche « Validée DG » avec noms des validateurs
3. ✅ Solde congé annuel réduit de 18 jours
4. Télécharger PDF → ✅ PDF s'ouvre avec les informations du congé

### P3.6 — Permission d'absence (1 jour)

1. En tant que **TSIBA**, créer une **Permission d'absence** pour demain (1 jour)
2. ✅ Formulaire simplifié (pas de workflow DG)
3. Se connecter en tant que **NGOUBILI** → valider l'absence
4. ✅ Statut → « Validée »

### P3.7 — Cas erreur — solde insuffisant

1. En tant qu'un agent ayant un solde de 3 jours, demander 20 jours
2. ✅ Message d'erreur clair : « Solde insuffisant (3 j disponibles) »

---

## Parcours 4 — Module Paie *(~45 min)*

> **Compte :** `rh@artf.cg` | **Données :** 4 lots (Juin clôturé → Septembre généré)

### P4.1 — Consultation des lots existants

1. Ouvrir le module **Paie > Lots**
2. ✅ 4 lots listés avec statuts : Juin 2026 `Clôturé` · Juillet `Validé` · Août `Contrôlé` · Septembre `Généré`
3. ✅ Badges colorés différents pour chaque statut
4. ✅ Total net affiché : 43 012 600 FCFA pour chaque lot

### P4.2 — Consulter les bulletins du lot Septembre

1. Cliquer sur le lot **Septembre 2026** (statut `Généré`)
2. ✅ 50 lignes d'agents listées
3. Cliquer sur la ligne de **Lydiane GAMBOU** (Directrice, salaire élevé)
4. ✅ Détail : Salaire base 1 419 000 + Prime représentation 200 000 + Prime logement 150 000 + Transport 14 000 − CNSS 8%
5. ✅ Montant net calculé correctement
6. Cliquer sur la ligne **Jean-Pierre MOUKALA** (DG hors grille)
7. ✅ Badge « Hors grille · Salaire fonctionnel »

### P4.3 — Créer et générer un nouveau lot (Octobre 2026)

1. Cliquer **Nouveau lot**
2. Choisir Octobre 2026 → ✅ Lot créé en statut `Brouillon`
3. Cliquer **Générer** → ✅ Barre de progression · Résultat : 50 bulletins
4. ✅ Total affiché · Anomalies : 0

### P4.4 — Workflow lot : Contrôler → Valider → Clôturer

1. Sur le lot Octobre, cliquer **Contrôler**
2. ✅ Statut → `Contrôlé` · Date de contrôle affichée
3. Cliquer **Valider** → ✅ Statut → `Validé`
4. ✅ Bouton **Modifier** grisé (lot verrouillé)
5. Cliquer **Clôturer** → ✅ Statut → `Clôturé`

### P4.5 — Bulletin PDF individuel

1. Ouvrir la fiche de **Rémy MAMPOUYA** (CS, échelon 10 après bonif Doctorat)
2. Onglet Carrière → Bouton **Bulletin PDF**
3. ✅ PDF s'ouvre : salaire base 1 045 500 FCFA · Classe IX · Échelon 10

### P4.6 — Affectation d'une prime exceptionnelle

1. Ouvrir la fiche d'un agent → Onglet Paie → **Ajouter un élément**
2. Choisir « Prime exceptionnelle » · Saisir 50 000 FCFA · Date début : aujourd'hui
3. ✅ `201` · Prime visible dans la liste des affectations

---

## Parcours 5 — Module Évaluation *(~1h)*

> **Session 2025 (clôturée) déjà en base · Session 2026 (ouverte) à utiliser**

### P5.1 — Consulter les évaluations 2025 (RH)

1. Se connecter `rh@artf.cg`
2. Aller dans **Évaluations** → Sélectionner session 2025
3. ✅ 51 fiches `Finalisée` listées
4. Ouvrir la fiche de **Nadège MOUAMBA** → ✅ Note globale visible · Avis supérieur · Signé par les deux parties

### P5.2 — Créer une évaluation (Directeur note un agent)

1. Se connecter `pierre.biyoudi@artf.cg` (Directeur DF)
2. **Évaluations** → **Nouvelle évaluation** · Session 2026
3. Sélectionner l'agent : **Nadège MOUAMBA** (D.F)
4. ✅ Formulaire de notation par critères (24 questions groupées par compétences, assiduité, relation)
5. Saisir toutes les notes → ✅ Note /20 calculée en temps réel
6. ✅ Bouton « Signer comme notateur » actif uniquement si toutes les notes saisies

### P5.3 — Workflow complet d'une évaluation

| Étape | Acteur | Action | Statut attendu |
|---|---|---|---|
| 1 | Directeur DF | Saisir notes | `en_cours` → `notee` |
| 2 | Directeur DF | Signer | `signee_evaluateur` |
| 3 | Nadège MOUAMBA | Se connecter → signer | `signee_evalue` |
| 4 | RH | Valider RH (conforme) | `finalisee` |

### P5.4 — Tableau d'avancement (agents inscrits)

1. Se connecter `rh@artf.cg`
2. **Évaluations** → **Tableau d'avancement** (session 2025)
3. ✅ Agents avec `inscrit_tableau: true` listés (notes ≥ 16/20)
4. ✅ Agents DG/DC/DD absents (hors grille)

### P5.5 — PDF fiche d'évaluation

1. Ouvrir une fiche `finalisee` (session 2025)
2. Cliquer **Télécharger la fiche PDF**
3. ✅ PDF généré avec nom agent, session, notes et signatures

---

## Parcours 6 — Module Discipline *(~30 min)*

> **Données seedées :** NKAYA (ARFT-00011) a un avertissement + sanction · MADZOU (ARFT-00035) a une mise à pied instruite

### P6.1 — Consultation historique disciplinaire (RH)

1. Se connecter `rh@artf.cg`
2. Ouvrir la fiche de **Rodrigue NKAYA** → Onglet **Discipline**
3. ✅ 1 avertissement affiché + 1 sanction « Avertissement écrit » prononcée
4. ✅ Conservation jusqu'au affichée

### P6.2 — Agent consulte son propre historique

1. Se connecter `rodrigue.nkaya@artf.cg` / `Nkaya@2026`
2. ✅ Accessible via **Mon profil > Discipline** (route `/discipline/moi/historique`)
3. ✅ Voit sa propre sanction · **Ne voit pas** les sanctions des autres

### P6.3 — Proposer une sanction (Chef de bureau)

1. Se connecter `carmelie.ngoubili@artf.cg` / `Ngoubili@2026` (CB D.R)
2. Aller dans **Discipline** → **Nouveau rapport**
3. Sélectionner agent : **Bertrand TSIBA** · Type : Blâme écrit
4. Décrire les faits → Soumettre
5. ✅ Sanction créée · Statut `en_attente` (rapport soumis)

### P6.4 — Instruire et prononcer (RH → DG)

1. Se connecter `rh@artf.cg` → Instruire la sanction soumise
2. ✅ Statut → `instruite` · Notes d'instruction ajoutées
3. Se connecter DG → **Prononcer**
4. ✅ Statut → `validee` · Date de décision affichée

### P6.5 — Prononcer la mise à pied en attente (MADZOU)

1. Se connecter DG
2. **Discipline** → trouver la mise à pied instruite de **Stève MADZOU** (ARFT-00035)
3. Prononcer avec 2 jours d'effet
4. ✅ Statut → `validee` · Dates d'effet visibles

---

## Parcours 7 — Affaires Sociales *(~30 min)*

> **Données seedées :** Ayants droit + Visites médicales pour tous les agents

### P7.1 — Ayants droit avec enfants (3 enfants à charge)

1. Se connecter `rh@artf.cg`
2. Ouvrir la fiche de **Nadège MOUAMBA** → Onglet **Affaires sociales**
3. ✅ Conjoint + 3 enfants listés (Inès 2014, Malo 2016, Lucie 2019)
4. ✅ Tous à charge (`est_a_charge: true`)
5. ✅ Inès (12 ans) et Malo (10 ans) éligibles Arbre de Noël

### P7.2 — Ajouter un ayant droit

1. Sur la fiche de **Fernand KIBANGOU** (ARFT-00006 · célibataire · 0 enfant)
2. Cliquer **Ajouter un ayant droit** → Type : Enfant
3. Renseigner : KIBANGOU Junior · M · 15/05/2023 · Lien : naturel reconnu
4. ✅ `201` · Enfant ajouté · `est_a_charge: true` (< 16 ans)

### P7.3 — Visite médicale

1. Aller dans **Affaires sociales > Visites médicales**
2. ✅ 112 visites listées (embauche + annuelle pour chaque agent)
3. Créer une nouvelle visite : **Consultation** pour un agent
4. ✅ `201` · Visite enregistrée

### P7.4 — Alerte visites annuelles manquantes

1. `GET /affaires-sociales/alertes/visites-annuelles-manquantes`
2. ✅ Agents sans visite annuelle dans les 12 derniers mois → liste affichée

---

## Parcours 8 — Intégration d'un nouvel agent *(~1h · Parcours complet)*

> **Contexte :** Recruter un nouvel agent fictif de A à Z

### P8.1 — Créer le dossier d'intégration (RH)

1. Se connecter `rh@artf.cg`
2. **Intégration > Nouveau dossier**
3. Renseigner : Prénom : Thomas · Nom : MAKOSSO · Genre : M · DN : 1995-08-12
4. Type d'intégration : « Recrutement externe »
5. ✅ Dossier créé en statut `brouillon` · Référence auto générée

### P8.2 — Ajouter les documents

1. Upload les pièces obligatoires (fichiers fictifs) : CV, Diplôme, Acte de naissance, Casier judiciaire
2. ✅ Chaque document : `obligatoire: true` + badge « Chargé »
3. ✅ Progression 4/X documents requis
4. Tenter de passer à l'étape suivante sans tous les documents → ✅ Erreur bloquante

### P8.3 — Circuit de validation

1. Soumettre le dossier → Statut → `soumis`
2. Passer au niveau Chef de service → approuver
3. Directeur → approuver
4. DRH → approuver
5. DG → approuver
6. ✅ Statut final → `integre` après prise de service

### P8.4 — Post-intégration (Carrière)

1. Aller dans **Carrière > Affectations** → créer affectation pour MAKOSSO → B.RCT
2. ✅ `201` · Affectation `en_attente_validation`
3. Activer l'affectation → ✅ Statut → `active`
4. **Carrière > Salaires** → Créer salaire initial → ✅ Calcul automatique grille

---

## Parcours 9 — Cloisonnement & Permissions *(~20 min · Tests négatifs)*

> Vérifier que les guards FE et API fonctionnent ensemble

| # | Acteur | Action tentée | Attendu |
|---|---|---|---|
| 9.1 | Agent TSIBA | Accéder à `/paie` | Redirection login ou `403` · Menu absent |
| 9.2 | Directeur DF | Voir les agents de la D.R | Dépend cloisonnement — uniquement D.F si activé |
| 9.3 | Directeur DF | Saisir un salaire | `403` côté API · Bouton absent dans le FE |
| 9.4 | Chef bureau | Valider congé au niveau DG | `403` · Bouton absent |
| 9.5 | RH | Prononcer une sanction (rôle DG uniquement) | `403` · Bouton « Prononcer » absent |
| 9.6 | Agent NKAYA | Voir les sanctions d'un autre agent | `403` / liste vide |
| 9.7 | Admin | Tout | Aucun `403` · Tous les boutons visibles |

---

## Parcours 10 — Notifications *(~15 min)*

> La cloche en haut à droite doit refléter les événements

| Événement | Destinataire | Notification attendue |
|---|---|---|
| Demande congé soumise | N+1 | « Demande de congé de Bertrand TSIBA à valider » |
| Congé validé DG | Agent | « Votre congé du 01/10 a été accordé » |
| Sanction instruite | DG | « Sanction instruite — à prononcer » |
| Lot paie généré | RH | « Lot Octobre 2026 généré — 50 bulletins » |
| Évaluation finalisée | Agent évalué | « Votre fiche d'évaluation 2026 est disponible » |

### Test
1. Se connecter en tant que **DG**
2. ✅ Cloche avec badge indiquant le nb de notifications non lues
3. Cliquer → ✅ Liste des notifications avec dates
4. Marquer comme lu → ✅ Badge disparaît

---

## Parcours 11 — Génération de documents PDF *(~20 min)*

> Tous les PDFs doivent être générés et téléchargeables

| Document | Endpoint | Compte | ✅ |
|---|---|---|---|
| Bulletin de salaire | `GET /salaires-agents/{id}/bulletin` | `rh` | ☐ |
| Acte d'affectation (note de service) | `GET /carriere/affectations/{id}/note-service` | `rh` | ☐ |
| Acte de nomination | `GET /carriere/nominations/{id}/acte` | `rh` | ☐ |
| Fiche d'évaluation | `GET /avancements/evaluations/{id}/pdf` | `rh` | ☐ |
| Décision de congé | `GET /conges/demandes/{id}/pdf` | `rh` | ☐ |
| Décision de sanction | `GET /discipline/{id}/pdf-decision` | `rh` | ☐ |

---

## Grille de validation globale

| Bloc | Durée | Bloquant FE | Priorité |
|---|---|---|---|
| P1 — Auth & menus | 20 min | ✅ Oui | 🔴 Critique |
| P2 — Consultation personnel | 30 min | ✅ Oui | 🔴 Critique |
| P3 — Congés (workflow complet) | 45 min | ✅ Oui | 🔴 Critique |
| P4 — Paie (lots + bulletins) | 45 min | ✅ Oui | 🔴 Critique |
| P5 — Évaluation | 60 min | Non | 🟡 Important |
| P6 — Discipline | 30 min | Non | 🟡 Important |
| P7 — Affaires sociales | 30 min | Non | 🟡 Important |
| P8 — Intégration nouvel agent | 60 min | ✅ Oui | 🔴 Critique |
| P9 — Permissions & guards | 20 min | ✅ Oui | 🔴 Critique |
| P10 — Notifications | 15 min | Non | 🟢 Complémentaire |
| P11 — PDFs | 20 min | Non | 🟢 Complémentaire |

**Total estimé :** 6h30 · **Bloquants seuls :** ~3h30

---

## Ordre recommandé

```
P1 → P9 (permissions d'abord) → P2 → P8 → P3 → P4 → P5 → P6 → P7 → P10 → P11
```

> 💡 **Conseil :** Tester P1 + P9 ensemble dès le départ pour valider que les guards FE sont bien câblés sur les permissions (et non sur les noms de rôles en dur).
