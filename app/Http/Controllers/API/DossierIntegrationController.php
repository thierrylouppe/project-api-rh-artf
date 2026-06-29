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

    public function store(CreateRequest $request): JsonResponse
    {
        return $this->respond($this->service->create($request->validated()), 'Dossier d\'intégration créé', 201);
    }

    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Dossier mis à jour');
    }

    public function soumettre(int $id): JsonResponse
    {
        return $this->respond($this->service->soumettre($id), 'Dossier soumis pour étude RH');
    }

    public function passerEnEtudeRH(int $id): JsonResponse
    {
        return $this->respond($this->service->passerEnEtudeRH($id), 'Dossier pris en charge par les RH');
    }

    public function marquerIncomplet(TransitionRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->marquerIncomplet($id, $request->input('commentaire', '')),
            'Dossier marqué incomplet'
        );
    }

    public function marquerComplet(int $id): JsonResponse
    {
        return $this->respond($this->service->marquerComplet($id), 'Dossier marqué complet');
    }

    public function validerRH(int $id): JsonResponse
    {
        return $this->respond($this->service->validerRH($id), 'Validation RH effectuée — circuit hiérarchique initialisé');
    }

    public function rejeterRH(TransitionRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->rejeterRH($id, $request->input('commentaire', '')),
            'Dossier rejeté par les RH'
        );
    }

    public function validerDG(int $id): JsonResponse
    {
        return $this->respond($this->service->validerDG($id), 'Validation DG accordée');
    }

    public function marquerActeGenere(int $id): JsonResponse
    {
        return $this->respond($this->service->marquerActeGenere($id), 'Acte administratif généré');
    }

    public function marquerContratSigne(int $id): JsonResponse
    {
        return $this->respond($this->service->marquerContratSigne($id), 'Contrat signé');
    }

    public function suspendre(TransitionRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->suspendre($id, $request->input('commentaire', '')),
            'Dossier suspendu'
        );
    }

    public function annuler(TransitionRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->annuler($id, $request->input('commentaire', '')),
            'Dossier annulé'
        );
    }

    public function assignerMatricule(AssignerMatriculeRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->service->assignerMatricule($id, $request->input('matricule')),
            "Matricule {$request->input('matricule')} assigné avec succès"
        );
    }

    public function genererActe(int $id): JsonResponse
    {
        $result = $this->service->genererActeAdministratif($id);

        return response()->json([
            'data' => [
                'acte'              => new ActeAdministratifResource($result['acte']),
                'dossier'           => new DossierIntegrationResource($result['dossier']),
                'necessite_contrat' => $result['necessite_contrat'],
                'prochaine_etape'   => $result['necessite_contrat'] ? 'contrat_signe' : 'matricule_cree',
            ],
            'message' => $result['necessite_contrat']
                ? 'Acte généré — veuillez enregistrer la signature du contrat avant de créer le matricule'
                : 'Acte généré — passage direct à la création du matricule (pas de contrat requis)',
        ], 201);
    }

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

    public function historique(int $id): JsonResponse
    {
        $historique = $this->historiqueService->getHistorique(\App\Models\DossierIntegration::class, $id);

        return response()->json(['data' => HistoriqueIntegrationResource::collection($historique)]);
    }

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request->query());

        return response()->json(['data' => DossierIntegrationResource::collection($items)]);
    }
}
