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
use Illuminate\Support\Facades\Storage;
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

    public function activer(ActiverRequest $request, int $id): JsonResponse
    {
        $affectation = $this->service->activer($id);

        if ($request->filled('dossier_integration_id')) {
            $this->dossierService->marquerAffecte($request->integer('dossier_integration_id'));
        }

        return $this->respond($affectation, 'Affectation activée');
    }

    public function rejeter(RejeterRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->rejeter($id, $request->string('commentaire')),
            'Affectation rejetée'
        );
    }

    public function terminer(TerminerRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->terminer($id, $request->input('date_fin')),
            'Affectation terminée'
        );
    }

    /** Génère la note de service PDF pour une affectation et la retourne en téléchargement. */
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

    public function byAgent(int $agentId): JsonResponse
    {
        $affectations = $this->service->getByAgent($agentId);

        return response()->json(['data' => AffectationResource::collection($affectations)]);
    }
}
