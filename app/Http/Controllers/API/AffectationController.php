<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Affectation\ActiverRequest;
use App\Http\Requests\Affectation\CreateRequest;
use App\Http\Requests\Affectation\GroupeeRequest;
use App\Http\Requests\Affectation\NoteServiceLotRequest;
use App\Http\Requests\Affectation\RejeterRequest;
use App\Http\Requests\Affectation\TerminerRequest;
use App\Http\Resources\AffectationResource;
use App\Services\AffectationService;
use App\Services\DossierIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use ZipArchive;

/** @property AffectationService $service */
class AffectationController extends BaseController
{
    public function __construct(
        AffectationService $service,
        private readonly DossierIntegrationService $dossierService,
    ) {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AffectationResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'superieurHierarchique', 'validations.validateur'];
    }

    #[OA\Get(path: '/api/integration/affectations', operationId: 'listAffectations', tags: ['Intégration — Affectations'], summary: 'Liste des affectations', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Liste'), new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401'))])]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(path: '/api/integration/affectations/{id}', operationId: 'showAffectation', tags: ['Intégration — Affectations'], summary: 'Détail d\'une affectation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Détail', content: new OA\JsonContent(ref: '#/components/schemas/AffectationResponse')), new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error404'))])]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    #[OA\Post(
        path: '/api/integration/affectations',
        operationId: 'storeAffectation',
        tags: ['Intégration — Affectations'],
        summary: 'Créer une affectation',
        description: 'Accepte JSON ou multipart (note_service fichier PDF/image).',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['agent_id', 'structurable_type', 'structurable_id', 'date_affectation'],
                    properties: [
                        new OA\Property(property: 'agent_id', type: 'integer'),
                        new OA\Property(property: 'structurable_type', type: 'string', example: 'App\\Models\\Direction'),
                        new OA\Property(property: 'structurable_id', type: 'integer'),
                        new OA\Property(property: 'motif', type: 'string', nullable: true),
                        new OA\Property(property: 'note_service', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'superieur_hierarchique_id', type: 'integer', nullable: true),
                        new OA\Property(property: 'date_affectation', type: 'string', format: 'date'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Créée', content: new OA\JsonContent(ref: '#/components/schemas/AffectationResponse')),
            new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function store(CreateRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('note_service')) {
            $fichier                           = $request->file('note_service');
            $agentId                           = $data['agent_id'];
            $data['note_service']              = $fichier->store("affectations/{$agentId}/notes-service", 'local');
            $data['note_service_nom_original'] = $fichier->getClientOriginalName();
        }

        $affectation = $this->service->create($data);

        $message = 'Affectation créée — circuit de validation initialisé';
        if (empty($affectation->superieur_hierarchique_id)) {
            $message .= '. Aucun supérieur hiérarchique trouvé pour cette structure.';
        }

        return $this->respond($affectation, $message, 201);
    }

    #[OA\Post(path: '/api/integration/affectations/groupee', operationId: 'storeAffectationGroupee', tags: ['Intégration — Affectations'], summary: 'Affectation groupée', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Créées'), new OA\Response(response: 422, description: 'Validation', content: new OA\JsonContent(ref: '#/components/schemas/Error422'))])]
    public function groupee(GroupeeRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('note_service')) {
            $fichier                           = $request->file('note_service');
            $data['note_service']              = $fichier->store('affectations/groupees/notes-service', 'local');
            $data['note_service_nom_original'] = $fichier->getClientOriginalName();
        }

        $affectations = $this->service->affecterGroupe($data);
        $count        = $affectations->count();

        $message      = "{$count} affectation(s) créée(s) — circuits de validation initialisés";
        $sansSuperior = $affectations->filter(fn ($a) => empty($a->superieur_hierarchique_id))->count();
        if ($sansSuperior > 0) {
            $message .= ". {$sansSuperior} agent(s) sans supérieur hiérarchique résolu pour leur structure.";
        }

        return response()->json([
            'data' => [
                'total'        => $count,
                'affectations' => AffectationResource::collection($affectations),
            ],
            'message' => $message,
        ], 201);
    }

    #[OA\Post(path: '/api/integration/affectations/{affectation}/activer', operationId: 'activerAffectation', tags: ['Intégration — Affectations'], summary: 'Activer une affectation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'affectation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'dossier_integration_id', type: 'integer', nullable: true)])), responses: [new OA\Response(response: 200, description: 'Activée', content: new OA\JsonContent(ref: '#/components/schemas/AffectationResponse'))])]
    public function activer(ActiverRequest $request, int $id): JsonResponse
    {
        $affectation = $this->service->activer($id);

        if ($request->filled('dossier_integration_id')) {
            $this->dossierService->marquerAffecte($request->integer('dossier_integration_id'));
        }

        return $this->respond($affectation, 'Affectation activée');
    }

    #[OA\Post(path: '/api/integration/affectations/{affectation}/rejeter', operationId: 'rejeterAffectation', tags: ['Intégration — Affectations'], summary: 'Rejeter une affectation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'affectation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/TransitionRequest')), responses: [new OA\Response(response: 200, description: 'Rejetée', content: new OA\JsonContent(ref: '#/components/schemas/AffectationResponse'))])]
    public function rejeter(RejeterRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->rejeter($id, $request->string('commentaire')),
            'Affectation rejetée'
        );
    }

    #[OA\Post(path: '/api/integration/affectations/{affectation}/terminer', operationId: 'terminerAffectation', tags: ['Intégration — Affectations'], summary: 'Terminer une affectation', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'affectation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'date_fin', type: 'string', format: 'date')])), responses: [new OA\Response(response: 200, description: 'Terminée', content: new OA\JsonContent(ref: '#/components/schemas/AffectationResponse'))])]
    public function terminer(TerminerRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->terminer($id, $request->input('date_fin')),
            'Affectation terminée'
        );
    }

    #[OA\Get(path: '/api/integration/affectations/{affectation}/note-service', operationId: 'noteServiceAffectation', tags: ['Intégration — Affectations'], summary: 'Télécharger la note de service (PDF)', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'affectation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Fichier PDF')])]
    public function noteService(int $id): SymfonyResponse
    {
        $path     = $this->service->genererNoteServicePdf($id);
        $fileName = "NS-AFF-" . date('Y') . "-" . str_pad($id, 4, '0', STR_PAD_LEFT) . ".pdf";

        $fullPath = Storage::disk('local')->path($path);

        return response()->download($fullPath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /** Génère les notes de service pour un lot d'affectations et les retourne dans un ZIP. */
    public function noteServiceLot(NoteServiceLotRequest $request): SymfonyResponse
    {
        $ids     = $request->validated()['affectation_ids'];
        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/notes-service-lot-' . time() . '.zip';
        $zip     = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $generees = 0;
        $erreurs  = [];

        foreach ($ids as $id) {
            try {
                $path     = $this->service->genererNoteServicePdf((int) $id);
                $fullPath = Storage::disk('local')->path($path);
                $fileName = "NS-AFF-" . date('Y') . "-" . str_pad($id, 4, '0', STR_PAD_LEFT) . ".pdf";
                $zip->addFile($fullPath, $fileName);
                $generees++;
            } catch (\Throwable $e) {
                $erreurs[] = "Affectation #{$id} : {$e->getMessage()}";
            }
        }

        $zip->close();

        $zipName = 'notes-service-affectations-' . date('Y-m-d') . '.zip';

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    #[OA\Get(path: '/api/integration/agents/{agent}/affectations', operationId: 'listAffectationsByAgent', tags: ['Intégration — Affectations'], summary: 'Affectations d\'un agent', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'agent', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Liste')])]
    public function byAgent(int $agentId): JsonResponse
    {
        $affectations = $this->service->getByAgent($agentId);

        return response()->json(['data' => AffectationResource::collection($affectations)]);
    }
}
