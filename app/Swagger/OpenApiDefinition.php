<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Gestion RH API — ARTF',
    version: '1.0.0',
    description: <<<'MD'
API REST de gestion RH (intégration administrative des agents).

## Authentification

1. Appeler `POST /api/login` avec email / mot de passe
2. Copier le `token` retourné
3. Cliquer **Authorize** en haut à droite
4. Saisir : `Bearer {token}` (ou uniquement le token selon le client)

Comptes de test (seeders) :
- `admin@arft.cg` / `Admin@2026`
- `rh@arft.cg` / `Rh@2026`

## Convention de réponse

```json
{ "data": {}, "message": "..." }
```

Erreurs : `{ "success": false, "message": "...", "errors": {} }`
MD
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'Serveur API (variable L5_SWAGGER_CONST_HOST)')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Token Sanctum obtenu via POST /api/login'
)]
#[OA\Tag(name: 'Auth', description: 'Connexion, déconnexion, profil')]
#[OA\Tag(name: 'Référentiels — Types d\'intégration', description: 'Types d\'entrée administrative')]
#[OA\Tag(name: 'Personnel', description: 'Agents intégrés et stagiaires')]
#[OA\Tag(name: 'Intégration — Agents', description: 'Fiches agents et matricules')]
#[OA\Tag(name: 'Intégration — Dossiers', description: 'Dossiers d\'intégration et transitions de workflow')]
#[OA\Tag(name: 'Intégration — Documents', description: 'Dépôt et validation des pièces')]
#[OA\Tag(name: 'Intégration — Validations', description: 'Circuit hiérarchique')]
#[OA\Tag(name: 'Intégration — Actes', description: 'Actes administratifs')]
#[OA\Tag(name: 'Intégration — Contrats', description: 'Contrats agents')]
#[OA\Tag(name: 'Carrière — Affectations', description: 'Affectations et notes de service')]
#[OA\Tag(name: 'Carrière — Nominations', description: 'Nominations à un poste de responsabilité')]
#[OA\Tag(name: 'Intégration — Affectations', description: 'Affectations (alias déprécié)')]
#[OA\Tag(name: 'Intégration — Nominations', description: 'Nominations (alias déprécié)')]
#[OA\Tag(name: 'Intégration — Prise de service', description: 'Prise de service et finalisation')]
class OpenApiDefinition {}
