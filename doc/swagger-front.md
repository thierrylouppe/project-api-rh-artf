# Swagger — guide développeur front

UI interactive : **`/api/documentation`**  
Spec JSON : **`/docs?api-docs.json`** (ou fichier généré `storage/api-docs/api-docs.json`)

## Démarrage rapide

1. Ouvrir `http://localhost:8000/api/documentation` (ou l’URL staging).
2. Déplier **Auth → POST /api/login**.
3. Try it out avec :
   - `admin@arft.cg` / `Admin@2026`
   - ou `rh@arft.cg` / `Rh@2026`
4. Copier `data.token`.
5. Cliquer **Authorize** → coller le token (format `Bearer xxx` ou token seul selon l’UI).
6. Appeler les endpoints protégés (**Intégration — Dossiers**, etc.).

## Parcours minimal à brancher

| Étape | Endpoint |
|-------|----------|
| 1. Login | `POST /api/login` |
| 2. Types d’intégration | `GET /api/types-integrations` |
| 3. Créer un agent (+ dossier auto) | `POST /api/integration/agents` |
| 4. Documents | `POST/GET /api/integration/dossiers/{id}/documents` |
| 5. Soumettre → étude RH → valider RH | transitions sous tag **Intégration — Dossiers** |
| 6. Circuit hiérarchique | `GET .../circuit` + `POST .../validations/{id}/approuver` |
| 7. Acte / contrat / matricule | `generer-acte`, contrats, `assigner-matricule` |
| 8. Affectation / nomination / prise de service | tags dédiés |
| 9. Finaliser | `POST /api/integration/dossiers/{id}/integrer` |

## Convention JSON

Succès :
```json
{ "data": {}, "message": "..." }
```

Erreur validation (422) :
```json
{ "success": false, "message": "...", "errors": { "champ": ["..."] } }
```

Header auth : `Authorization: Bearer {token}`

## Régénération (backend)

```bash
php artisan l5-swagger:generate
```

En local, `L5_SWAGGER_GENERATE_ALWAYS=true` régénère à chaque visite de l’UI.
