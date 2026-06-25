<?php

namespace App\Services;

use App\Enums\StatutConventionStage;
use App\Interfaces\AgentInterface;
use App\Interfaces\ConventionStageInterface;
use App\Models\ConventionStage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ClotureStageService
{
    public function __construct(
        private readonly ConventionStageInterface $conventionRepository,
        private readonly AgentInterface $agentRepository,
    ) {}

    /**
     * Clôture administrative complète d'un stage.
     *
     * Enchaîne 5 automatismes :
     *  1. Contrat → resilie
     *  2. Nomination active → cloturee
     *  3. Affectation active → terminee
     *  4. Agent.statut → inactif
     *  5. ConventionStage.statut_stage → TERMINE
     */
    public function cloturer(int $conventionId, float $note, string $appreciation): ConventionStage
    {
        return DB::transaction(function () use ($conventionId, $note, $appreciation) {
            /** @var ConventionStage $convention */
            $convention = $this->conventionRepository->findById($conventionId);
            $convention->load(['agent.contratActif', 'agent.nominationActive', 'agent.affectationActive']);

            abort_unless(
                $convention->statut_stage->estActif(),
                422,
                'Seule une convention EN_COURS peut être clôturée'
            );

            $agent = $convention->agent;

            // 1. Résiliation du contrat actif
            if ($contrat = $agent->contratActif) {
                $contrat->update(['statut' => 'resilie']);
            }

            // 2. Clôture de la nomination active
            if ($nomination = $agent->nominationActive) {
                $nomination->update(['statut' => 'cloturee']);
            }

            // 3. Fin de l'affectation active
            if ($affectation = $agent->affectationActive) {
                $affectation->update(['statut' => 'terminee']);
            }

            // 4. Agent → inactif
            $this->agentRepository->update($agent->id, ['statut' => 'inactif']);

            // 5. Convention → TERMINE + évaluation
            return $this->conventionRepository->update($conventionId, [
                'statut_stage'  => StatutConventionStage::TERMINE->value,
                'note_finale'   => $note,
                'appreciation'  => $appreciation,
            ]);
        });
    }

    /**
     * Génère le PDF de l'attestation de stage pour une convention terminée.
     */
    public function genererAttestationPdf(int $conventionId): Response
    {
        /** @var ConventionStage $convention */
        $convention = $this->conventionRepository->findById($conventionId);
        $convention->load(['agent', 'tuteurInterne', 'dossier.typeIntegration']);

        $pdf = Pdf::loadView('pdf.attestation-stage', compact('convention'));

        return $pdf->stream('attestation-stage-' . $convention->agent->matricule . '.pdf');
    }
}
