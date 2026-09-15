<?php

namespace App\Services;

use App\Enums\StatutEvaluation;
use App\Interfaces\CommissionPreparatoireInterface;
use App\Interfaces\EvaluationInterface;
use App\Models\CommissionPreparatoire;
use App\Models\Evaluation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EvaluationPdfService
{
    public function __construct(
        private readonly EvaluationInterface             $evaluationRepository,
        private readonly CommissionPreparatoireInterface $preparatoireRepository,
    ) {}

    public function fichePdf(int $evaluationId, User $user): Response
    {
        $fiche = $this->evaluationRepository->findById($evaluationId);
        $fiche->load([
            'agent',
            'superieur',
            'session',
            'affectationNotation.structure',
            'notes.question',
            'avisHierarchiques.signePar',
            'reclamation.agent',
        ]);

        $this->assertPeutLireFiche($user, $fiche);

        if (! $fiche->statut->peutTelechargerFichePdf()) {
            throw ValidationException::withMessages([
                'statut' => 'La fiche PDF n\'est disponible qu\'après signature de l\'agent (prise de connaissance).',
            ]);
        }

        $matricule = $fiche->agent?->matricule ?? $fiche->agent_id;
        $sessionId = $fiche->session_id;

        return Pdf::loadView('pdf.fiche-evaluation', ['fiche' => $fiche])
            ->stream("fiche-evaluation-{$sessionId}-{$matricule}.pdf");
    }

    public function synthesePdf(int $commissionId, User $user): Response
    {
        $this->assertPeutLireSynthese($user);

        /** @var CommissionPreparatoire $commission */
        $commission = $this->preparatoireRepository->findById($commissionId);
        $commission->load('session');

        if (! $commission->statut->estClose()) {
            throw ValidationException::withMessages([
                'statut' => 'La note de synthèse PDF n\'est disponible qu\'après clôture de la commission préparatoire.',
            ]);
        }

        $fiches = $this->evaluationRepository->getBySession((int) $commission->session_id)
            ->filter(fn (Evaluation $e) => $e->statut === StatutEvaluation::FINALISEE)
            ->values();

        return Pdf::loadView('pdf.note-synthese-preparatoire', [
            'commission' => $commission,
            'fiches'     => $fiches,
        ])->stream("note-synthese-session-{$commission->session_id}.pdf");
    }

    private function assertPeutLireFiche(User $user, Evaluation $fiche): void
    {
        if ($this->estRhOuDg($user)) {
            return;
        }

        $agentId = $user->agent_id ? (int) $user->agent_id : null;
        if ($agentId && ($agentId === (int) $fiche->agent_id || $agentId === (int) $fiche->superieur_id)) {
            return;
        }

        abort(403, 'Vous n\'êtes pas autorisé à télécharger cette fiche.');
    }

    private function assertPeutLireSynthese(User $user): void
    {
        if ($this->estRhOuDg($user)) {
            return;
        }

        abort(403, 'Seule la RH ou le directeur général peut télécharger la note de synthèse.');
    }

    private function estRhOuDg(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('rh')
            || $user->hasRole('directeur-general');
    }
}
