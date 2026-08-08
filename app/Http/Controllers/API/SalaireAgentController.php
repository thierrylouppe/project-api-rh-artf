<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\SalaireAgent\AvancerEchelonRequest;
use App\Http\Requests\SalaireAgent\CloturerRequest;
use App\Http\Requests\SalaireAgent\CreateRequest;
use App\Http\Resources\SalaireAgentResource;
use App\Services\SalaireAgentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SalaireAgentController extends BaseController
{
    public function __construct(SalaireAgentService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return SalaireAgentResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'salaire.classe.categorie', 'salaire.classe.grade', 'classe.categorie', 'classe.grade'];
    }

    /**
     * Crée le salaire initial à partir de la grille (classe + échelon de l'agent).
     * Body : agent_id, contrat_id? (filtre CDI/CDD), date_debut?, motif?
     */
    public function store(CreateRequest $request): JsonResponse
    {
        $salaireAgent = $this->service->creerDepuisRequest($request->validated());

        abort_if(
            $salaireAgent === null,
            422,
            'Création de salaire non applicable (réservée aux contrats CDI / CDD).'
        );

        $salaireAgent->load($this->showRelations());

        return $this->respond($salaireAgent, 'Salaire agent créé avec succès', 201);
    }

    public function byAgent(int $agentId): JsonResponse
    {
        $items = $this->service->getByAgent($agentId);

        return response()->json(['data' => SalaireAgentResource::collection($items)]);
    }

    /**
     * Historique chronologique des périodes salariales (variations échelon / montant).
     */
    public function historique(int $agentId): JsonResponse
    {
        $items = $this->service->getHistorique($agentId);

        return response()->json(['data' => SalaireAgentResource::collection($items)]);
    }

    public function actuel(int $agentId): JsonResponse
    {
        $actuel = $this->service->getActuel($agentId);

        abort_if($actuel === null, 404, 'Aucun salaire actif pour cet agent.');

        $actuel->load($this->showRelations());

        return $this->respond($actuel);
    }

    public function cloturer(CloturerRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $salaireAgent = $this->service->cloturer(
            $id,
            $data['date_fin'] ?? null,
            $data['motif'] ?? null
        );
        $salaireAgent->load($this->showRelations());

        return $this->respond($salaireAgent, 'Salaire agent clôturé');
    }

    public function avancerEchelon(AvancerEchelonRequest $request, int $agentId): JsonResponse
    {
        $salaireAgent = $this->service->avancerEchelon(
            $agentId,
            $request->validated('motif')
        );
        $salaireAgent->load($this->showRelations());

        return $this->respond($salaireAgent, 'Avancement d\'échelon effectué');
    }

    /** Bulletin PDF du salaire actif de l'agent. */
    public function bulletin(int $agentId): Response
    {
        return $this->service->genererBulletinPdf($agentId);
    }

    /** Bulletin PDF d'une période salariale précise. */
    public function bulletinById(int $id): Response
    {
        $salaireAgent = $this->service->findById($id);

        return $this->service->genererBulletinPdf((int) $salaireAgent->agent_id, $id);
    }
}
