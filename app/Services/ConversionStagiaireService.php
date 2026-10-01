<?php

namespace App\Services;

use App\Enums\StatutConventionStage;
use App\Enums\StatutDossier;
use App\Interfaces\ConventionStageInterface;
use App\Interfaces\DossierIntegrationInterface;
use App\Interfaces\TypeIntegrationInterface;
use App\Models\ConventionStage;
use App\Models\DossierIntegration;
use App\Models\TypeIntegration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversionStagiaireService
{
    public function __construct(
        private readonly ConventionStageInterface $conventionRepository,
        private readonly DossierIntegrationService $dossierService,
        private readonly DossierIntegrationInterface $dossierRepository,
        private readonly TypeIntegrationInterface $typeIntegrationRepository,
    ) {}

    public function convertir(int $conventionId): DossierIntegration
    {
        return DB::transaction(function () use ($conventionId) {
            /** @var ConventionStage $convention */
            $convention = $this->conventionRepository->findById($conventionId);
            $convention->load('agent');

            abort_unless(
                $convention->statut_stage === StatutConventionStage::TERMINE,
                422,
                'Seul un stage clôturé peut être converti en dossier agent.'
            );

            abort_if(
                $convention->dossier_conversion_id !== null,
                422,
                'Ce stage a déjà été converti en dossier d\'intégration.'
            );

            abort_if(
                $this->aDossierConversionOuvert((int) $convention->agent_id),
                422,
                'Un dossier d\'intégration non clos existe déjà pour cet agent.'
            );

            $type = $this->trouverTypeRecrutement();

            $dossier = $this->dossierService->create([
                'type_integration_id' => $type->id,
                'agent_id' => $convention->agent_id,
                'demandeur_id' => Auth::id(),
                'motif' => 'Conversion stagiaire → agent (stage #'.$convention->id.')',
                'notes' => 'Dossier ouvert après clôture de la convention de stage n° '.$convention->id.'.',
            ]);

            $this->conventionRepository->update($convention->id, [
                'dossier_conversion_id' => $dossier->id,
            ]);

            return $dossier->load(['typeIntegration', 'agent', 'demandeur']);
        });
    }

    private function trouverTypeRecrutement(): TypeIntegration
    {
        $types = $this->typeIntegrationRepository->getAll();
        $type = $types->first(fn (TypeIntegration $item) => $item->nom === 'Recrutement externe');

        abort_if($type === null, 422, 'Le type d\'intégration « Recrutement externe » est introuvable.');

        return $type;
    }

    private function aDossierConversionOuvert(int $agentId): bool
    {
        return $this->dossierRepository->getAll(['agent_id' => $agentId])
            ->contains(function (DossierIntegration $dossier) {
                $statut = $dossier->statut instanceof StatutDossier
                    ? $dossier->statut
                    : StatutDossier::tryFrom((string) $dossier->statut);

                return $statut !== null && ! $statut->estTerminal();
            });
    }
}
