# Guide de test — Salaires agents (Phase 3.B / 3.C)

> Base URL : `http://localhost:8000/api`  
> Outil recommandé : Insomnia ou Postman  
> Headers obligatoires sur les routes `/integration/*` :
> ```
> Accept: application/json
> Authorization: Bearer {token}
> ```

Prérequis :
1. Seeders exécutés (`php artisan db:seed`) — permissions `consulter-salaires` / `gerer-salaires` (rôles admin, rh)
2. Token Sanctum (toutes les routes salaires/grille sont protégées)
3. Grille générée (`POST /salaires/generation`)
4. Un agent CDI ou CDD avec catégorie + grade + échelon (parcours intégration contractuel)

> **Permissions :** lecture → `consulter-salaires` ; génération / création / clôture / avancement → `gerer-salaires`.

---

## 0. Authentification

```
POST /login
```
```json
{
  "email": "admin@arft.cg",
  "password": "Admin@2026"
}
```

---

## 1. Générer la grille (prérequis)

Sans lignes dans `salaires`, la création de salaire agent échoue (422).

```
POST /salaires/generation
```
```json
{
  "valeur_point_indice": 300
}
```

**Réponse attendue (extrait)**
```json
{
  "success": true,
  "message": "Grille générée avec succès (120 lignes).",
  "data": null,
  "total": 120,
  "valeur_point_indice": 300,
  "echelon_depart": 1,
  "echelon_fin": 12
}
```

**Contrôle Classe I / échelon 1**
```
GET /salaires
```

Chercher la ligne classe coefficient 45, échelon 1 → `salaire` = **147000**.

---

## 2. Création automatique à la création du contrat

Lors d’un `POST /integration/contrats` avec type **CDI** ou **CDD**, le salaire initial est créé automatiquement (classe / échelon de l’agent × grille).

```
POST /integration/contrats
Authorization: Bearer {token}
```
```json
{
  "agent_id": 1,
  "type_contrat_id": 1,
  "date_debut": "2026-08-01",
  "date_fin": null,
  "statut": "actif"
}
```

Puis vérifier :

```
GET /integration/agents/1/salaires/actuel
Authorization: Bearer {token}
```

**Réponse attendue (forme)**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "salaire_id": 1,
    "classegrillesalariale_id": 1,
    "echelon": 1,
    "montant_base": 147000,
    "montant_net": 147000,
    "date_debut": "2026-08-01",
    "date_fin": null,
    "statut": "actif",
    "classe": { "id": 1, "coefficient": 45, "categorie": {}, "grade": {} }
  }
}
```

> Stage (`STG`) / Consultant : **pas** de salaire agent (création ignorée, `null`).

---

## 3. Création manuelle (rattrapage)

Si un agent CDI/CDD n’a pas encore de salaire (contrat créé avant le branchement) :

```
POST /salaires-agents
```
```json
{
  "agent_id": 1,
  "contrat_id": 1,
  "date_debut": "2026-08-01"
}
```

- `contrat_id` optionnel : s’il est fourni et non CDI/CDD → **422**
- Sans `contrat_id` : crée quand même à partir de la classe / échelon de l’agent
- Un salaire **actif** existant est clôturé avant création du nouveau

---

## 4. Lister / consulter

```
GET /salaires-agents
GET /salaires-agents?agent_id=1&statut=actif
GET /salaires-agents/1
GET /integration/agents/1/salaires
Authorization: Bearer {token}
```

---

## 5. Clôturer

```
POST /salaires-agents/1/cloturer
```
```json
{
  "date_fin": "2026-08-08"
}
```

Sans `date_fin` → date du jour.  
Salaire déjà clôturé → **422**.

---

## 6. Avancer d’échelon

```
POST /integration/agents/1/salaires/avancer-echelon
Authorization: Bearer {token}
```
```json
{
  "motif": "Avancement après évaluation"
}
```

Effets :
1. Clôture le salaire actif
2. Crée un nouveau salaire actif à `echelon + 1` (montant grille), `type_changement = avancement_echelon`
3. Met à jour `agents.echelon_id`

Échelon déjà à `echelon_fin` (paramètre grille) → **422**.  
Aucun salaire actif → **422**.

---

## 6-bis. Historique des changements

Timeline chronologique (plus ancien → plus récent) avec variations :

```
GET /integration/agents/1/salaires/historique
Authorization: Bearer {token}
```

**Réponse (extrait)**
```json
{
  "data": [
    {
      "id": 1,
      "echelon": 1,
      "montant_base": 147000,
      "statut": "cloture",
      "type_changement": "initial",
      "type_changement_label": "Salaire initial",
      "motif": null,
      "echelon_precedent": null,
      "montant_precedent": null,
      "variation_echelon": null,
      "variation_montant": null
    },
    {
      "id": 2,
      "echelon": 2,
      "montant_base": 160500,
      "statut": "actif",
      "type_changement": "avancement_echelon",
      "motif": "Avancement après évaluation",
      "echelon_precedent": 1,
      "montant_precedent": 147000,
      "variation_echelon": 1,
      "variation_montant": 13500
    }
  ]
}
```

Liste simple (plus récent d’abord) toujours dispo :

```
GET /integration/agents/1/salaires
Authorization: Bearer {token}
```

---

## 7. Tâche post-intégration

Pour un dossier contractuel intégré :

```
GET /integration/dossiers/{id}/taches-post-integration
Authorization: Bearer {token}
```

Doit contenir une tâche du type :
- label : `Salaire initial (auto à la création du contrat CDI/CDD)`
- endpoint : `GET /integration/agents/{id}/salaires/actuel`
- statut : `fait` si `salaireActuel` présent

---

## 8. Bulletin PDF

Salaire actif :

```
GET /integration/agents/1/salaires/bulletin
Authorization: Bearer {token}
```

Période précise :

```
GET /salaires-agents/1/bulletin
Authorization: Bearer {token}
```

Réponse : flux PDF (`application/pdf`).

---

## 9. Backfill (seeder)

Pour les agents déjà en base avec contrat CDI/CDD actif **sans** salaire actif :

```bash
php artisan db:seed --class=SalaireAgentSeeder
```

Le seeder :
1. génère la grille si `salaires` est vide ;
2. appelle `creerSalaireInitial` pour chaque agent éligible sans salaire actif.

---

## Checklist rapide

| # | Action | OK |
|---|---|---|
| 1 | Génération grille → 120 lignes, Classe I éch.1 = 147000 | ☐ |
| 2 | Création contrat CDI → salaire actif auto | ☐ |
| 3 | Contrat stage → pas de salaire | ☐ |
| 4 | `GET .../salaires/actuel` | ☐ |
| 5 | `POST .../avancer-echelon` → échelon +1 + `type_changement` | ☐ |
| 6 | `GET .../salaires/historique` → variations montant / échelon | ☐ |
| 7 | `POST .../cloturer` | ☐ |
| 8 | `GET .../salaires/bulletin` → PDF | ☐ |
| 9 | Sans token → 401 ; sans permission → 403 | ☐ |
| 10 | Tâche post-intégration « Salaire initial » | ☐ |
