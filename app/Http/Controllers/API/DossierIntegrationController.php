<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\DossierIntegration\AssignerMatriculeRequest;
use App\Http\Requests\DossierIntegration\CreateRequest;
use App\Http\Requests\DossierIntegration\TransitionRequest;
use App\Http\Requests\DossierIntegration\UpdateRequest;
use App\Http\Resources\ActeAdministratifResource;
use App\Http\Resources\DossierIntegrationResource;
use App\Http\Resources\HistoriqueIntegrationResource;
use App\Services\DossierIntegrationService;
use App\Services\HistoriqueIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DossierIntegrationController extends BaseController
{
    public function __construct(
        DossierIntegrationService $service,
        private readonly HistoriqueIntegrationService $historiqueService,
    ) {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return DossierIntegrationResource::class;
    }

    protected function showRelations(): array
    {
        return ['typeIntegration', 'demandeur', 'agent', 'documents.typeDocument', 'validations.validateur', 'actes', 'historique.utilisateur'];
    }

    #[OA\Post(
        path: '/api/integration/dossiers',
        operationId: 'storeDossierIntegration',
        tags: ['Intégration — Dossiers'],
        summary: 'Créer un dossier d\'intégration',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Créé', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Dossier d\'intégration créé', 201);
    }

    #[OA\Put(
        path: '/api/integration/dossiers/{id}',
        operationId: 'updateDossierIntegration',
        tags: ['Intégration — Dossiers'],
        summary: 'Mettre à jour un dossier',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mis à jour', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Dossier mis à jour');
    }

    #[OA\Post(
        path: '/api/integration/dossiers/{id}/soumettre',
        operationId: 'soumettreDossierIntegration',
        tags: ['Intégration — Dossiers'],
        summary: 'Soumettre le dossier pour étude RH',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Soumis', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404')),
        ]
    )]
    public function soumettre(int $id): JsonResponse
    {
        return $this->respond($this->service->soumettre($id), 'Dossier soumis pour étude RH');
    }

    #[OA\Post(
        path: '/api/integration/dossiers/{id}/passer-en-etude-rh',
        operationId: 'passerEnEtudeRHDossier',
        tags: ['Intégration — Dossiers'],
        summary: 'Prendre en charge le dossier (RH)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'En étude RH', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function passerEnEtudeRH(int $id): JsonResponse
    {
        return $this->respond($this->service->passerEnEtudeRH($id), 'Dossier pris en charge par les RH');
    }

    #[OA\Post(
        path: '/api/integration/dossiers/{id}/marquer-incomplet',
        operationId: 'marquerIncompletDossier',
        tags: ['Intégration — Dossiers'],
        summary: 'Marquer le dossier incomplet',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/TransitionRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Marqué incomplet', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function marquerIncomplet(TransitionRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->marquerIncomplet($id, $request->input('commentaire', '')),
            'Dossier marqué incomplet'
        );
    }

    #[OA\Post(path: '/api/integration/dossiers/{id}/marquer-complet', operationId: 'marquerCompletDossier', tags: ['Intégration — Dossiers'], summary: 'Marquer le dossier complet', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function marquerComplet(int $id): JsonResponse
    {
        return $this->respond($this->service->marquerComplet($id), 'Dossier marqué complet');
    }

    #[OA\Post(path: '/api/integration/dossiers/{id}/valider-rh', operationId: 'validerRHDossier', tags: ['Intégration — Dossiers'], summary: 'Valider le dossier (RH) — initialise le circuit hiérarchique', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function validerRH(int $id): JsonResponse
    {
        return $this->respond($this->service->validerRH($id), 'Validation RH effectuée — circuit hiérarchique initialisé');
    }

    #[OA\Post(path: '/api/integration/dossiers/{id}/rejeter-rh', operationId: 'rejeterRHDossier', tags: ['Intégration — Dossiers'], summary: 'Rejeter le dossier (RH)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/TransitionRequest')), responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function rejeterRH(TransitionRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->rejeterRH($id, $request->input('commentaire', '')),
            'Dossier rejeté par les RH'
        );
    }

    #[OA\Post(path: '/api/integration/dossiers/{id}/valider-dg', operationId: 'validerDGDossier', tags: ['Intégration — Dossiers'], summary: 'Valider le dossier (DG)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function validerDG(int $id): JsonResponse
    {
        return $this->respond($this->service->validerDG($id), 'Validation DG accordée');
    }

    #[OA\Post(path: '/api/integration/dossiers/{id}/marquer-acte-genere', operationId: 'marquerActeGenereDossier', tags: ['Intégration — Dossiers'], summary: 'Marquer l\'acte comme généré (transition manuelle)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function marquerActeGenere(int $id): JsonResponse
    {
        return $this->respond($this->service->marquerActeGenere($id), 'Acte administratif généré');
    }

    #[OA\Post(path: '/api/integration/dossiers/{id}/marquer-contrat-signe', operationId: 'marquerContratSigneDossier', tags: ['Intégration — Dossiers'], summary: 'Marquer le contrat comme signé', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function marquerContratSigne(int $id): JsonResponse
    {
        return $this->respond($this->service->marquerContratSigne($id), 'Contrat signé');
    }

    #[OA\Post(path: '/api/integration/dossiers/{id}/suspendre', operationId: 'suspendreDossier', tags: ['Intégration — Dossiers'], summary: 'Suspendre le dossier', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/TransitionRequest')), responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function suspendre(TransitionRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->suspendre($id, $request->input('commentaire', '')),
            'Dossier suspendu'
        );
    }

    #[OA\Post(path: '/api/integration/dossiers/{id}/annuler', operationId: 'annulerDossier', tags: ['Intégration — Dossiers'], summary: 'Annuler le dossier', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/TransitionRequest')), responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function annuler(TransitionRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->annuler($id, $request->input('commentaire', '')),
            'Dossier annulé'
        );
    }

    #[OA\Post(path: '/api/integration/dossiers/{id}/assigner-matricule', operationId: 'assignerMatriculeDossier', tags: ['Intégration — Dossiers'], summary: 'Assigner un matricule à l\'agent du dossier', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AssignerMatriculeRequest')), responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')), new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422'))])]
    public function assignerMatricule(AssignerMatriculeRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->assignerMatricule($id, $request->input('matricule')),
            "Matricule {$request->input('matricule')} assigné avec succès"
        );
    }

    #[OA\Post(path: '/api/integration/dossiers/{id}/generer-acte', operationId: 'genererActeDossier', tags: ['Intégration — Dossiers'], summary: 'Générer l\'acte administratif du dossier', description: 'Retourne l\'acte, le dossier mis à jour, et la prochaine étape (`contrat_signe`, `matricule_cree` ou `taches_post_integration`).', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 201, description: 'Acte généré', content: new OA\JsonContent(ref: '#/components/schemas/GenererActeResponse')), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function genererActe(int $id): JsonResponse
    {
        $result = $this->service->genererActeAdministratif($id);
        $depuisIntegre = $result['dossier']->statut->value === 'INTEGRE';

        $prochaine = $depuisIntegre
            ? 'taches_post_integration'
            : ($result['necessite_contrat'] ? 'contrat_signe' : 'matricule_cree');

        $message = match (true) {
            $depuisIntegre => 'Acte généré en post-intégration',
            $result['necessite_contrat'] => 'Acte généré — veuillez enregistrer la signature du contrat avant de créer le matricule',
            default => 'Acte généré — passage direct à la création du matricule (pas de contrat requis)',
        };

        return response()->json([
            'data' => [
                'acte'              => new ActeAdministratifResource($result['acte']),
                'dossier'           => new DossierIntegrationResource($result['dossier']),
                'necessite_contrat' => $result['necessite_contrat'],
                'prochaine_etape'   => $prochaine,
            ],
            'message' => $message,
        ], 201);
    }

    #[OA\Get(path: '/api/integration/dossiers/{id}/taches-post-integration', operationId: 'tachesPostIntegrationDossier', tags: ['Intégration — Dossiers'], summary: 'Checklist des tâches post-intégration', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function tachesPostIntegration(int $id): JsonResponse
    {
        $taches = $this->service->tachesPostIntegration($id);

        $restantes = collect($taches)->where('statut', 'non_fait')->count();

        return response()->json([
            'data'   => $taches,
            'rappel' => $restantes === 0
                ? 'Toutes les tâches post-intégration sont complètes.'
                : "{$restantes} tâche(s) post-intégration en attente.",
        ]);
    }

    #[OA\Get(path: '/api/integration/dossiers/{id}/historique', operationId: 'historiqueDossier', tags: ['Intégration — Dossiers'], summary: 'Historique des transitions du dossier', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function historique(int $id): JsonResponse
    {
        $historique = $this->historiqueService->getHistorique(\App\Models\DossierIntegration::class, $id);

        return response()->json(['data' => HistoriqueIntegrationResource::collection($historique)]);
    }

    #[OA\Get(
        path: '/api/integration/dossiers',
        operationId: 'listDossiersIntegration',
        tags: ['Intégration — Dossiers'],
        summary: 'Liste des dossiers d\'intégration',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'statut', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: 'BROUILLON'),
            new OA\Parameter(name: 'type_integration_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationListResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request->query());

        return response()->json(['data' => DossierIntegrationResource::collection($items)]);
    }

    #[OA\Get(
        path: '/api/integration/dossiers/{id}',
        operationId: 'showDossierIntegration',
        tags: ['Intégration — Dossiers'],
        summary: 'Détail d\'un dossier (avec relations)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détail', content: new OA\JsonContent(ref: '#/components/schemas/DossierIntegrationResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
            new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404')),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }
}
