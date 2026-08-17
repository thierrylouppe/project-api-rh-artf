# Note frontend — rôles, permissions et comptes de démo

> Date : 2026-08-16  
> Breaking : **oui pour les menus** — le métier RH n’est plus exposé aux rôles hiérarchiques.

Préfixe API : `/api`.

## 1. Principe

Les rôles sont **globaux** (pas liés à une direction / un service / un bureau).

| Famille | Rôles | Usage FE |
|---|---|---|
| Système | `admin` | Accès complet (toutes les permissions) |
| Métier RH (DRHL) | `rh` | **Seul** rôle métier RH : utilisateurs, référentiels (écriture), recrutement, contrats, salaires, reporting |
| Hiérarchie | `directeur-general`, `directeur`, `chef-service`, `chef-bureau` | Structure + agents en lecture + validations d’équipe (congés / absences / évaluations). **Pas** de menus RH |
| Self-service | `agent` | Ses demandes de congés / absences |

`directeur`, `chef-service`, `chef-bureau` s’appliquent à **toutes** les directions / services / bureaux, pas seulement la DRHL.  
Le périmètre « uniquement ma structure » n’est **pas encore** filtré côté API.

## 2. Auth — comment récupérer le rôle

`POST /login` ne renvoie **pas** les rôles (payload minimal + `token`).

Après login, appeler :

```
GET /user
Authorization: Bearer {token}
```

`data.roles[].name` = rôle à utiliser pour les menus.  
`data.roles[].permissions[].name` = droits fins (préférer les permissions plutôt que le nom du rôle pour afficher/masquer un bouton).

Un `403` = permission manquante : masquer l’action, ne pas la proposer.

## 3. Comptes de démo (seeder)

Mot de passe : respecter la casse.

| Email | Mot de passe | Rôle |
|---|---|---|
| `admin@arft.cg` | `Admin@2026` | `admin` |
| `rh@arft.cg` | `Rh@2026` | `rh` |
| `dg@arft.cg` | `Dg@2026` | `directeur-general` |
| `directeur@arft.cg` | `Directeur@2026` | `directeur` |
| `chef-service@arft.cg` | `ChefService@2026` | `chef-service` |
| `chef-bureau@arft.cg` | `ChefBureau@2026` | `chef-bureau` |
| `agent@arft.cg` | `Agent@2026` | `agent` |

## 4. Menus recommandés

| Module / écran | `admin` | `rh` | `directeur-general` | `directeur` | `chef-service` | `chef-bureau` | `agent` |
|---|---|---|---|---|---|---|---|
| Utilisateurs / rôles | oui | utilisateurs (pas rôles) | — | — | — | — | — |
| Structure org. | oui | lecture | lecture | lecture | lecture | lecture | — |
| Référentiels RH (écriture) | oui | oui | — | — | — | — | — |
| Référentiels (lecture listes) | oui | oui | oui | oui | oui | oui | oui |
| Recrutement / intégration (pilotage) | oui | oui | — | — | — | — | — |
| Contrats | oui | oui | — | — | — | — | — |
| Salaires / grille | oui | oui | — | — | — | — | — |
| Reporting RH | oui | oui | — | — | — | — | — |
| Agents (consultation) | oui | oui | oui | oui | oui | oui | — |
| Congés / absences (validation) | oui | oui | oui | oui | oui | oui | — |
| Congés / absences (créer les siens) | oui | — | — | — | — | — | oui |
| Évaluations (validation) | oui | lecture | oui | oui | — | — | — |

## 5. Permissions (noms API)

### Métier RH — `rh` (+ `admin`)

`consulter-utilisateurs`, `creer-utilisateurs`, `modifier-utilisateurs`  
`creer-referentiels`, `modifier-referentiels`  
`creer-agents`, `modifier-agents`  
`consulter-recrutement`, `creer-recrutement`, `valider-recrutement`  
`consulter-contrats`, `creer-contrats`, `modifier-contrats`  
`consulter-salaires`, `gerer-salaires`  
`consulter-reporting`

### Partagés hiérarchie + RH

`consulter-structure`, `consulter-referentiels`, `consulter-agents`  
`consulter-conges`, `valider-conges`  
`consulter-absences`, `valider-absences`  
`consulter-evaluations` (`valider-evaluations` : DG / directeur / admin uniquement)

### Agent

`consulter-referentiels`  
`consulter-conges`, `creer-conges`  
`consulter-absences`, `creer-absences`

## 6. À faire côté FE

1. Ne plus afficher salaires / contrats / recrutement / reporting / gestion utilisateurs aux rôles hiérarchiques.
2. Après `POST /login`, charger `GET /user` pour menus et guards.
3. Tester les 7 comptes ci-dessus (un par profil).
4. Un compte créé à l’intégration (email agent) n’a **pas** automatiquement un rôle Spatie tant que RH / admin ne l’assigne pas.
