<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Agent\CreateRequest;
use App\Http\Requests\Agent\ModifierMatriculeRequest;
use App\Http\Requests\Agent\UpdateRequest;
use App\Http\Resources\AgentResource;
use App\Http\Resources\DossierIntegrationResource;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;

/** @property AgentService $service */
class AgentController extends BaseController
{
    public function __construct(AgentService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AgentResource::class;
    }

    protected function showRelations(): array
    {
        return ['grade', 'categorie', 'echelon', 'fonction', 'typeIntegration', 'affectationActive', 'nominationActive', 'contratActif'];
    }

    public function store(CreateRequest $request): JsonResponse
    {
        $result = $this->service->creerAvecDossier($request->validated());

        return response()->json([
            'data' => [
                'agent'   => new AgentResource($result['agent']),
                'dossier' => new DossierIntegrationResource($result['dossier']),
            ],
            'message' => 'Fiche agent créée — dossier d\'intégration initialisé automatiquement (réf. ' . $result['dossier']->reference . ')',
        ], 201);
    }

    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->service->update($id, $request->validated()), 'Agent mis à jour avec succès');
    }

    public function modifierMatricule(ModifierMatriculeRequest $request, int $id): JsonResponse
    {
        $agent = $this->service->modifierMatricule($id, $request->validated('matricule'));

        return $this->respond($agent, "Matricule modifié : {$agent->matricule}");
    }
}
