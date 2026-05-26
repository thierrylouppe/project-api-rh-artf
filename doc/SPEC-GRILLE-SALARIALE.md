# Spécification — Génération de grille salariale

Document de référence pour reproduire le module de génération de grille salariale de **gestion-rh-api** dans un autre projet (Laravel ou autre stack).

---

## 1. Objectif

Permettre la **génération automatique** d'une grille salariale complète à partir :

- des **classes** (grade + coefficient),
- d'une **valeur du point d'indice** (paramètre monétaire),
- d'une **formule de calcul** fixe par coefficient.

L'opération **remplace intégralement** la grille existante (suppression puis recréation).

---

## 2. Modèle de données

### 2.1 Table `classegrillesalariales`

Référentiel des classes salariales.

| Colonne       | Type    | Contraintes        |
|---------------|---------|--------------------|
| id            | bigint  | PK, auto-increment |
| classe        | string  | ex. "Classe I"     |
| grade         | string  | ex. "Commis"       |
| coefficient   | integer | ex. 55             |
| created_at    | timestamp | nullable         |
| updated_at    | timestamp | nullable         |

**Données initiales (10 lignes) :**

| classe      | grade                             | coefficient |
|-------------|-----------------------------------|-------------|
| Classe I    | Personnel de service              | 45          |
| Classe II   | Personnel de service spécialisé   | 50          |
| Classe III  | Commis                            | 55          |
| Classe IV   | Commis Principal                  | 60          |
| Classe V    | Contrôleur                        | 75          |
| Classe VI   | Contrôleur Principal              | 90          |
| Classe VII  | Vérificateur                      | 105         |
| Classe VIII | Inspecteur                        | 120         |
| Classe IX   | Inspecteur Principal              | 145         |
| Classe X    | Hors Classe                       | 170         |

### 2.2 Table `parametregrilles`

Paramètres globaux de la grille (présents dans le projet source, **non utilisés dans le calcul actuel**).

| Colonne              | Type    | Défaut |
|----------------------|---------|--------|
| id                   | bigint  | PK     |
| valeur_point_indice  | decimal(10,2) | 300 |
| indice_base          | integer | 445    |
| echelon_depart       | integer | 1      |
| echelon_fin          | integer | 12     |
| ecart_depart         | integer | 400    |
| created_at / updated_at | timestamp | |

**Donnée initiale recommandée :**

```json
{
  "valeur_point_indice": 300,
  "indice_base": 445,
  "echelon_depart": 1,
  "echelon_fin": 12,
  "ecart_depart": 45
}
```

> **Note projet source :** le service de génération ignore cette table et utilise des valeurs codées en dur. Pour un nouveau projet, envisager de rendre ces paramètres configurables.

### 2.3 Table `salaires`

Grille générée (résultat de l'opération).

| Colonne                    | Type          | Contraintes              |
|----------------------------|---------------|--------------------------|
| id                         | bigint        | PK                       |
| echelon                    | integer       | 1 à 12                   |
| indice                     | integer       | calculé                  |
| salaire                    | decimal(10,2) | calculé                  |
| classegrillesalariale_id   | bigint        | FK → classegrillesalariales |
| created_at / updated_at    | timestamp     |                          |

**Cardinalité attendue après génération :** 10 classes × 12 échelons = **120 lignes**.

---

## 3. Formule de calcul

Pour chaque classe `C` et chaque échelon `E` de **1 à 12** :

```
indice(C, E)  = base_indices[C.coefficient] + (E × C.coefficient)
salaire(C, E) = indice(C, E) × valeur_point_indice
```

### 3.1 Table `base_indices` (codée en dur dans le projet source)

| coefficient | indice_base |
|-------------|-------------|
| 45          | 445         |
| 50          | 540         |
| 55          | 645         |
| 60          | 760         |
| 75          | 895         |
| 90          | 1060        |
| 105         | 1255        |
| 120         | 1480        |
| 145         | 2035        |
| 170         | 2690        |

Si le coefficient d'une classe n'existe pas dans cette table, **ignorer la classe**.

### 3.2 Exemples de validation

| Classe   | coefficient | échelon | indice | salaire (point = 300) |
|----------|-------------|---------|--------|------------------------|
| Classe I | 45          | 1       | 490    | 147 000                |
| Classe I | 45          | 2       | 535    | 160 500                |
| Classe I | 45          | 12      | 985    | 295 500                |
| Classe X | 170         | 1       | 2860   | 858 000                |
| Classe X | 170         | 12      | 4730   | 1 419 000              |

Calcul Classe I, échelon 1 :
```
indice  = 445 + (1 × 45) = 490
salaire = 490 × 300 = 147 000
```

---

## 4. API REST

### 4.1 Générer la grille

```
POST /api/salaires/generation
Content-Type: application/json
Accept: application/json
```

**Corps (optionnel) :**

```json
{
  "valeur_point_indice": 300
}
```

| Champ                 | Type   | Règles              | Défaut |
|-----------------------|--------|---------------------|--------|
| valeur_point_indice   | number | nullable, numeric, min:0 | 300 |

**Comportement :**

1. Valider le body.
2. Charger toutes les classes depuis `classegrillesalariales`.
3. Calculer les 120 lignes selon la formule.
4. **Transaction DB :**
   - `DELETE FROM salaires` (toute la table)
   - `INSERT` des nouvelles lignes
5. Retourner une réponse de succès.

**Réponse 200 :**

```json
{
  "success": true,
  "message": "Grille créée avec succès.",
  "data": null
}
```

> Dans le projet source, `data` est `null` car le repository ne retourne pas les enregistrements créés. Un nouveau projet peut renvoyer la grille générée dans `data`.

**Erreurs :**

| Code | Cas                                      |
|------|------------------------------------------|
| 422  | `valeur_point_indice` invalide           |
| 500  | Erreur base de données / calcul          |

### 4.2 Consulter la grille

```
GET /api/salaires
Accept: application/json
```

**Réponse 200 (extrait) :**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "echelon": 1,
      "indice": 490,
      "salaire": "147000.00",
      "classe": {
        "id": 1,
        "classe": "Classe I",
        "grade": "Personnel de service",
        "coefficient": 45
      }
    }
  ]
}
```

### 4.3 Routes complémentaires (référentiels)

```
GET|POST|PUT|DELETE  /api/classes      → CRUD classegrillesalariales
GET|POST|PUT|DELETE  /api/parametres   → CRUD parametregrilles
```

---

## 5. Architecture recommandée (Laravel)

```
SalaireController
  └── generate(Request)     → validation + délégation
  └── index()               → liste de la grille

SalaireService
  └── create(valeurPointIndice)
        └── generateGrid()  → calcul en mémoire
        └── repository.create()

SalaireRepository
  └── create(array $salaries)  → delete all + insert
  └── getAll()                 → with('classe')
```

### 5.1 Pseudo-code du service

```php
public function generateGrid(float $valeurPointIndice): array
{
    $baseIndices = [
        45 => 445, 50 => 540, 55 => 645, 60 => 760, 75 => 895,
        90 => 1060, 105 => 1255, 120 => 1480, 145 => 2035, 170 => 2690,
    ];

    $salaries = [];

    foreach (Classegrillesalariale::all() as $classe) {
        if (!isset($baseIndices[$classe->coefficient])) {
            continue;
        }

        $indiceBase = $baseIndices[$classe->coefficient];

        for ($echelon = 1; $echelon <= 12; $echelon++) {
            $indice = $indiceBase + ($echelon * $classe->coefficient);

            $salaries[] = [
                'classegrillesalariale_id' => $classe->id,
                'echelon' => $echelon,
                'indice' => $indice,
                'salaire' => $indice * $valeurPointIndice,
            ];
        }
    }

    return $salaries;
}
```

### 5.2 Pseudo-code du repository

```php
public function create(array $salaries): void
{
    DB::transaction(function () use ($salaries) {
        Salaire::query()->delete();

        foreach ($salaries as $salary) {
            Salaire::create($salary);
        }
    });
}
```

---

## 6. Migrations Laravel (référence)

Fichiers source du projet :

- `database/migrations/2025_03_07_124753_create_classegrillesalariales_table.php`
- `database/migrations/2025_03_07_124754_create_parametregrilles_table.php`
- `database/migrations/2025_03_07_124755_create_salaries_table.php`

Seeders source :

- `database/seeders/ClassegrillesalarialeSeeder.php`
- `database/seeders/ParametregrillesalarialeSeeder.php`
- `database/seeders/SalaireSeeder.php` (alternative au POST, même logique)

---

## 7. Mise en place sur un nouveau projet

### Étape 1 — Base de données

```bash
php artisan migrate
php artisan db:seed --class=ClassegrillesalarialeSeeder
php artisan db:seed --class=ParametregrillesalarialeSeeder
```

### Étape 2 — Génération via API

```bash
curl -X POST "http://localhost/api/salaires/generation" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"valeur_point_indice": 300}'
```

### Étape 3 — Vérification

```bash
curl "http://localhost/api/salaires" -H "Accept: application/json"
```

**Critères de succès :**

- [ ] 120 lignes en base
- [ ] Classe I, échelon 1 → indice 490, salaire 147 000 (point = 300)
- [ ] Regénération avec point = 350 recalcule tous les salaires
- [ ] Aucun doublon après regénération

---

## 8. Sécurité et bonnes pratiques

| Sujet              | État projet source              | Recommandation nouveau projet        |
|--------------------|---------------------------------|--------------------------------------|
| Authentification   | Routes publiques                | `auth:sanctum` + permission RH       |
| Transaction        | Non explicite                   | Encapsuler delete + insert           |
| Retour POST        | `data: null`                    | Retourner la grille ou un résumé     |
| Paramètres         | Hardcodés                       | Lire depuis `parametregrilles`       |
| Historique         | Aucun                           | Option : versionner les grilles      |

---

## 9. Prompt IA (copier-coller)

```
Implémente un module de génération de grille salariale avec :

TABLES :
- classegrillesalariales (id, classe, grade, coefficient)
- parametregrilles (id, valeur_point_indice default 300, indice_base, echelon_depart, echelon_fin, ecart_depart)
- salaires (id, echelon, indice, salaire, classegrillesalariale_id FK)

SEED : 10 classes (coefficients 45, 50, 55, 60, 75, 90, 105, 120, 145, 170) avec grades correspondants.

ENDPOINT POST /api/salaires/generation
Body optionnel : { "valeur_point_indice": number } (défaut 300, min 0)
Logique :
  base_indices = {45:445, 50:540, 55:645, 60:760, 75:895, 90:1060, 105:1255, 120:1480, 145:2035, 170:2690}
  Pour chaque classe, échelons 1 à 12 :
    indice = base_indices[coefficient] + (echelon × coefficient)
    salaire = indice × valeur_point_indice
  Transaction : supprimer toute la table salaires, insérer 120 lignes.

ENDPOINT GET /api/salaires → liste avec classe associée.

Tests :
- Classe I échelon 1 point 300 → indice 490 salaire 147000
- Total 120 lignes après génération
- Regénération remplace l'ancienne grille
```

---

## 10. Fichiers source de référence (gestion-rh-api)

| Fichier | Rôle |
|---------|------|
| `app/Http/Controllers/API/SalaireController.php` | Endpoints index + generate |
| `app/Services/SalaireService.php` | Formule de calcul |
| `app/Repositories/SalaireRepository.php` | Persistance (delete + insert) |
| `app/Models/Salaire.php` | Modèle Eloquent |
| `app/Models/Classegrillesalariale.php` | Modèle Eloquent |
| `routes/api.php` (lignes salaires) | Déclaration des routes |
