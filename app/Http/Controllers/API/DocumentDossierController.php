<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\DocumentDossier\CreateRequest;
use App\Http\Resources\DocumentDossierResource;
use App\Http\Resources\TypeDocumentResource;
use App\Services\DocumentDossierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentDossierController extends BaseController
{
    public function __construct(DocumentDossierService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return DocumentDossierResource::class;
    }

    public function store(CreateRequest $request, int $dossierId): JsonResponse
    {
        $fichier       = $request->file('fichier');
        $cheminFichier = $fichier->store("dossiers/{$dossierId}/documents", 'local');

        $document = $this->service->create([
            'dossier_integration_id' => $dossierId,
            'type_document_id'       => $request->input('type_document_id'),
            'nom_original'           => $fichier->getClientOriginalName(),
            'chemin_fichier'         => $cheminFichier,
            'est_obligatoire'        => $request->boolean('est_obligatoire', false),
        ]);

        return $this->respond($document, 'Document ajouté au dossier', 201);
    }

    public function parDossier(int $dossierId): JsonResponse
    {
        $etat = $this->service->getEtatDocuments($dossierId);

        return response()->json([
            'data' => [
                'deposes'   => DocumentDossierResource::collection($etat['deposes']),
                'manquants' => collect($etat['manquants'])->map(fn (array $item) => [
                    'type_document'   => new TypeDocumentResource($item['type_document']),
                    'est_obligatoire' => $item['est_obligatoire'],
                ]),
                'resume' => $etat['resume'],
            ],
        ]);
    }

    public function valider(Request $request, int $id): JsonResponse
    {
        $document = $this->service->valider($id, $request->input('commentaire'));

        return $this->respond($document, 'Document validé');
    }
}
